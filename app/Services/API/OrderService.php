<?php

namespace App\Services\API;

use App\Http\Resources\Order\OrderCollection;
use App\Http\Resources\Order\OrderResource;
use App\Jobs\TransferProductJob;
use App\Models\Cart;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Services\BaseService;
use App\Traits\CheckQuantityTrait;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService extends BaseService
{
    use UploadFileTrait, HelperTrait ,CheckQuantityTrait;

    protected $productUtil;
    protected $transactionUtil;
    protected $cartService;
    protected $orderTrackingService;
    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil,
    OrderTrackingService $orderTrackingService,
    CartService $cartService)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cartService = $cartService;
        $this->orderTrackingService = $orderTrackingService;
    }
    /**
     * Get all Orders with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = Order::query();

            $query = $this->withTrashed($query, $request);

            $orders = $this->withPagination($query, $request);

            return (new OrderCollection($orders))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing Orders'));
        }
    }

    public function show($id) {

        try {
            $order = Order::findOrFail($id);

            if(!$order) {
                return null;
            }
            return new OrderResource($order);

        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Order'));
        }
    }

    /**
     * Create a new Order.
     */
    public function store()
    {
        try {
            $carts = Cart::where('client_id', Auth::id())
                ->with(['product', 'variation.variation_location_details', 'client'])
                ->get();

    
            $client = Client::findOrFail(Auth::id());
            $orderTotal = $carts->sum('total');
    
            $order = Order::create([
                'client_id' => Auth::id(),
                'sub_total' => $orderTotal,
                'total' => $orderTotal,
                'business_location_id'=>$client->business_location_id,
            ]);

            $this->orderTrackingService->store($order,'pending');

            $this->cartService->clearCart();
    
            foreach ($carts as $cart) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cart->product_id,
                    'variation_id' => $cart->variation_id,
                    'quantity' => $cart->quantity,
                    'price' => $cart->price,
                    'discount' => $cart->discount,
                    'sub_total' => $cart->total,
                ]);
    
                $this->handleQuantityTransfer($cart, $client, $order, $orderItem);

            }

            return new OrderResource($order);
    
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while storing Order'));
        }
    }
    
    /**
     * Handle quantity transfer between locations based on client needs.
     */
    protected function handleQuantityTransfer($cart, $client, $order ,$orderItem)
    {
        $requiredQuantity = $cart->quantity;
        $quantityTransferred = 0;
        $sufficientQuantity = false;
    
        foreach ($cart->variation->variation_location_details as $locationDetail) {
            $locationId = $locationDetail->location->id;
    
            if ($locationId === $client->business_location_id) {
                if ($locationDetail->qty_available >= $requiredQuantity) {
                    $sufficientQuantity = true;
                    break;
                } else {
                    $quantityTransferred = $locationDetail->qty_available;
                    $requiredQuantity -= $quantityTransferred;
                }
            }
        }
    
        if (!$sufficientQuantity && $requiredQuantity > 0) {
            foreach ($cart->variation->variation_location_details as $locationDetail) {
                $locationId = $locationDetail->location->id;
    
                if ($locationId !== $client->business_location_id && $locationDetail->qty_available > 0) {
                    $transferQuantity = min($requiredQuantity, $locationDetail->qty_available);
                    $this->transferQuantity($order,$orderItem, $client, $locationId, $client->business_location_id, $transferQuantity);
    
                    $quantityTransferred += $transferQuantity;
                    $requiredQuantity -= $transferQuantity;
    
                    if ($requiredQuantity <= 0) {
                        $sufficientQuantity = true;
                        break;
                    }
                }
            }
        }
    
        if (!$sufficientQuantity) {
            throw new \Exception('Insufficient quantity for product ' . $cart->product_id);
        }
    }

    protected function transferQuantity($order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)
    {
        // Dispatch the job with a 10-minute delay
        TransferProductJob::dispatch($order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)
            ->delay(now());

        \Log::info("TransferProductJob dispatched for Order: {$order->id}, OrderItem: {$orderItem->id}");
    }


    protected function makeSale($order , $client)
    {

        $is_direct_sale = true;

        try {
            // $input = $request->except('_token');

            // $input['is_quotation'] = 0;
            // //status is send as quotation from Add sales screen.
            // if ($input['status'] == 'quotation') {
            //     $input['status'] = 'draft';
            //     $input['is_quotation'] = 1;
            //     $input['sub_status'] = 'quotation';
            // } else if ($input['status'] == 'proforma') {
            //     $input['status'] = 'draft';
            //     $input['sub_status'] = 'proforma';
            // }


            /// transaction_table
            // business_id , location_id , type (sell) , status(final), payment_status(paid)
            // contact_id , invoice_no , transaction_date , total_before_tax , tax_amount
            // discount_type , discount_amount , created_by

            // $invoice_no = !empty($input['invoice_no']) ? $input['invoice_no'] : $this->getInvoiceNumber($business_id, $input['status'], $input['location_id'], $invoice_scheme_id, $sale_type);

            $transactionData = [
                "business_id"=>$order->business_id,
                "location_id"=>$client->location_id,
                "type"=>"sell",
                "status"=>"final",
                "payment_status"=>"paid",
                "contact_id"=>$client->contact_id,
                "transaction_date"=>now(),
                "total_before_tax"=>$order->total,
                "tax_amount"=>"0.0000",
                "discount_type"=>"",
                "discount_amount"=>"0.0000",
                "created_by"=>1,
            ];
            
            //Check Customer credit limit
            // $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);

            // if ($is_credit_limit_exeeded !== false) {
            //     $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
            //     $output = ['success' => 0,
            //                 'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount])
            //             ];
            //     if (!$is_direct_sale) {
            //         return $output;
            //     } else {
            //         return redirect()
            //             ->action('SellController@index')
            //             ->with('status', $output);
            //     }
            // }

            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');

                // $user_id = $request->session()->get('user.id');

                $discount = ['discount_type' => $input['discount_type'],
                                'discount_amount' => $input['discount_amount']
                            ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                DB::beginTransaction();

                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] =  \Carbon::now();
                } else {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }
                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }

                //Set commission agent
                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                //set selling price group id
                $price_group_id = $request->has('price_group') ? $request->input('price_group') : null;

                //If default price group for the location exists
                $price_group_id = $price_group_id == 0 && $request->has('default_price_group') ? $request->input('default_price_group') : $price_group_id;

                // $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                // if ($input['is_suspend']) {
                //     $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                // }

                //Generate reference number
                if (!empty($input['is_recurring'])) {
                    //Update reference count
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                    $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                }

                if (!empty($request->input('invoice_scheme_id'))) {
                    $input['invoice_scheme_id'] = $request->input('invoice_scheme_id');
                } 


                $input['selling_price_group_id'] = $price_group_id;


                //upload document
                // $input['document'] = $this->transactionUtil->uploadFile($request, 'sell_document', 'documents');

                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);

                //Upload Shipping documents
                // Media::uploadMedia($business_id, $transaction, $request, 'shipping_documents', false, 'shipping_document');
                

                $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);
                
                if (!$is_direct_sale) {
                    //Add change return
                    $change_return = $this->dummyPaymentLine;
                    $change_return['amount'] = $input['change_return'];
                    $change_return['is_return'] = 1;
                    $input['payment'][] = $change_return;
                }

                $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;

                if (!$transaction->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);
                }
                if($request->input('time_deliver') != ''){
                    $input['time_deliver'] = $request->input('time_deliver');
                }

                //Check for final and do some processing.
                if ($input['status'] == 'final') {
                    //update product stock
                    foreach ($input['products'] as $product) {
                        $decrease_qty = $this->productUtil
                                    ->num_uf($product['quantity']);
                        if (!empty($product['base_unit_multiplier'])) {
                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                        }

                        if ($product['enable_stock']) {
                            $this->productUtil->decreaseProductQuantity(
                                $product['product_id'],
                                $product['variation_id'],
                                $input['location_id'],
                                $decrease_qty
                            );
                        }

                        if ($product['product_type'] == 'combo') {
                            //Decrease quantity of combo as well.
                            $this->productUtil
                                ->decreaseProductQuantityCombo(
                                    $product['combo'],
                                    $input['location_id']
                                );
                        }
                    }

                    //Add payments to Cash Register
                    if (!$is_direct_sale && !$transaction->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                        $this->cashRegisterUtil->addSellPayments($transaction, $input['payment']);
                    }

                    //Update payment status
                    $payment_status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                    $transaction->payment_status = $payment_status;

                    if ($request->session()->get('business.enable_rp') == 1) {
                        $redeemed = !empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                        $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, 0, $redeemed);
                    }

                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                    $business = ['id' => $business_id,
                                    'accounting_method' => $request->session()->get('business.accounting_method'),
                                    'location_id' => $input['location_id'],
                                    'pos_settings' => $pos_settings
                                ];
                    $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

                    //Auto send notification
                    $whatsapp_link = $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                }

                if (!empty($transaction->sales_order_ids)) {
                    $this->transactionUtil->updateSalesOrderStatus($transaction->sales_order_ids);
                }

                //Set Module fields
                if (!empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }

                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                $this->transactionUtil->activityLog($transaction, 'added');

                DB::commit();

                if ($request->input('is_save_and_print') == 1) {
                    $url = $this->transactionUtil->getInvoiceUrl($transaction->id, $business_id);
                    return redirect()->to($url . '?print_on_load=true');
                }

                $msg = trans("sale.pos_sale_added");
                $receipt = '';
                $invoice_layout_id = $request->input('invoice_layout_id');
                $print_invoice = false;
                if (!$is_direct_sale) {
                    if ($input['status'] == 'draft') {
                        $msg = trans("sale.draft_added");

                        if ($input['is_quotation'] == 1) {
                            $msg = trans("lang_v1.quotation_added");
                            $print_invoice = true;
                        }
                    } elseif ($input['status'] == 'final') {
                        $print_invoice = true;
                    }
                }

                if ($transaction->is_suspend == 1 && empty($pos_settings['print_on_suspend'])) {
                    $print_invoice = false;
                }

                if (!auth()->user()->can("print_invoice")) {
                    $print_invoice = false;
                }
                
                if ($print_invoice) {
                    $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id);
                }

                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt ];

                if (!empty($whatsapp_link)) {
                    $output['whatsapp_link'] = $whatsapp_link;
                }
            } else {
                $output = ['success' => 0,
                            'msg' => trans("messages.something_went_wrong")
                        ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");
                
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }
            if (get_class($e) == \App\Exceptions\AdvanceBalanceNotAvailable::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                            'msg' => $msg
                        ];
        }

        if (!$is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } elseif ($input['status'] == 'quotation') {
                return redirect()
                    ->action('SellController@getQuotations')
                    ->with('status', $output);
            } elseif (isset($input['type']) && $input['type'] == 'sales_order') {
                return redirect()
                    ->action('SalesOrderController@index')
                    ->with('status', $output);
            } else {
                if (!empty($input['sub_type']) && $input['sub_type'] == 'repair') {
                    $redirect_url = $input['print_label'] == 1 ? action('\Modules\Repair\Http\Controllers\RepairController@printLabel', [$transaction->id]) : action('\Modules\Repair\Http\Controllers\RepairController@index');
                    return redirect($redirect_url)
                        ->with('status', $output);
                }
                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }
        }
    }
    
    /**
     * Update the specified Order.
     */
    public function update($request,$order)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $order->update($data);

        return new OrderResource($order);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating Order'));
    }
    }

    public function destroy($id)
    {
        try {

            $order = Order::find($id);

            if(!$order) {
                return null;
            }
            $order->delete();
            return $order;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Order'));
        }
    }

    public function restore($id)
    {
        try {
            $order = Order::withTrashed()->findOrFail($id);
            $order->restore();
            return new OrderResource($order);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring Order'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $order = Order::withTrashed()
                ->findOrFail($id);

            $order->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting Order'));
        }
    }


    public function checkQuantityAndLocation(){

        try {
            $client = Client::findOrFail(Auth::id());
            $carts = Cart::where('client_id', Auth::id())
                ->with(['product', 'variation.variation_location_details', 'client'])
                ->get();

                 // Check if cart is empty
        if ($carts->isEmpty()) {
            return $this->returnJSON(null, __('message.Cart is empty'));
        }

            $multiLocationMessage = false;
    
            foreach ($carts as $cart) {
                $quantity = $cart->quantity;
    
                // Check if sufficient quantity is available at client's business location
                $sufficientQuantity = $this->checkSufficientQuantity($cart->variation->variation_location_details, $client->business_location_id, $quantity);
    
                // If the required quantity is not available, set multi-location message
                if (!$sufficientQuantity) {
                    $multiLocationMessage = true;
                }

            }
    
            // Add multi-location message if applicable
            if ($multiLocationMessage) {
                return $this->returnJSON(null, __('message.Order will be shipped tomorrow due to multiple locations')); ;
            }
    
            return $this->returnJSON(null, __('message.Order will be shipped today')); ;

    
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing cart items'));
        }
    }
    
}
