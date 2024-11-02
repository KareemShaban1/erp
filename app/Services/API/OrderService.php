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
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService extends BaseService
{
    use UploadFileTrait, HelperTrait, CheckQuantityTrait;

    protected $productUtil;
    protected $transactionUtil;
    protected $contactUtil;
    protected $cartService;
    protected $orderTrackingService;
    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(
        ProductUtil $productUtil,
        TransactionUtil $transactionUtil,
        ContactUtil $contactUtil,
        OrderTrackingService $orderTrackingService,
        CartService $cartService
    ) {
        $this->contactUtil = $contactUtil;
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

    public function show($id)
    {

        try {
            $order = Order::findOrFail($id);

            if (!$order) {
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

            // Check if cart is empty
            if ($carts->isEmpty()) {
                return $this->returnJSON([], __('message.Cart is empty'));
            }


            $client = Client::findOrFail(Auth::id());
            $orderTotal = $carts->sum('total');

            $order = Order::create([
                'client_id' => Auth::id(),
                'sub_total' => $orderTotal,
                'total' => $orderTotal,
                'business_location_id' => $client->business_location_id,
            ]);

            $this->orderTrackingService->store($order, 'pending');

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

            \Log::info("makeSale method is about to be called.");
            $saleResponse = $this->makeSale($order, $client, $carts);
            \Log::info("makeSale method has been called.");


            return new OrderResource($order);

        } catch (\Exception $e) {
            \Log::error("Error in store method: " . $e->getMessage());
            return $this->handleException($e, __('message.Error happened while storing Order'));
        }
    }

    /**
     * Handle quantity transfer between locations based on client needs.
     */
    protected function handleQuantityTransfer($cart, $client, $order, $orderItem)
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
                    $this->transferQuantity($order, $orderItem, $client, $locationId, $client->business_location_id, $transferQuantity);

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


    protected function makeSale($order, $client, $carts)
    {
        $is_direct_sale = true;

        try {
            $transactionData = [
                "business_id" => $client->contact->business_id,
                "location_id" => $client->business_location_id,
                "type" => "sell",
                "status" => "final",
                "payment_status" => "paid",
                "contact_id" => $client->contact_id,
                "transaction_date" => now(),
                "total_before_tax" => $order->total,
                "tax_amount" => "0.0000",
                "created_by" => 1,
            ];
            $cartsArray = $carts->map(function ($cart) {
                // Calculate the unit price including tax if necessary, adjust based on your tax rules.
                // $unit_price_inc_tax = $cart->price + ($cart->price * $cart->tax_rate / 100); // Example tax calculation
                return [
                    'unit_price_inc_tax' =>  $cart->price,
                    'quantity' => $cart->quantity,
                    'modifier_price' => $cart->modifier_price ?? [], // Ensure it has a default array if no modifier exists
                    'modifier_quantity' => $cart->modifier_quantity ?? [], // Same for modifier quantity
                ];
            })->toArray();

            // Pass the transformed carts array to calculateInvoiceTotal
            $discount = [
                'discount_type' => 'fixed', // or 'percentage' based on your discount logic
                'discount_amount' => 0, // Example fixed discount amount
            ];
            $tax_id = 1; // Replace with your actual tax ID if applicable

            $invoice_total = $this->productUtil->calculateInvoiceTotal($cartsArray, $tax_id, $discount);

            $invoice_total['total_before_tax'] = $invoice_total['total_before_tax'] ?? 0;
            
            $transactionData['invoice_total'] = $invoice_total;

            $business_id = $client->contact->business_id;
            $user_id = 1;

            DB::beginTransaction();

            $transactionData['transaction_date'] = Carbon::now();

            $contact_id = $client->contact_id;
            $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
            $customerGroupId = (empty($cg) || empty($cg->id)) ? null : $cg->id;

            $transaction = $this->transactionUtil->createSellTransaction($business_id, $transactionData, $invoice_total, $user_id);

            // Log::info($transaction);
            // Create or update sell lines using $carts instead of $input['products']
            $products = $carts->map(function ($cart) {
                return [
                    'product_id' => $cart->product_id,
                    'variation_id' => $cart->variation_id,
                    'quantity' => $cart->quantity,
                    'price' => $cart->price,
                    'discount' => $cart->discount,
                    'enable_stock'=>1
                ];
            })->toArray();

            $sellLines =  $this->transactionUtil->createOrUpdateSellLines($transaction, $products, $client->business_location_id);

            Log::info($sellLines);

            if (!$transaction->is_suspend && !empty($transactionData['payment']) && !$is_direct_sale) {
                $this->transactionUtil->createOrUpdatePaymentLines($transaction, $transactionData['payment']);
            }

            if ($transactionData['status'] == 'final') {
                foreach ($products as $product) {
                    $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                    if (!empty($product['base_unit_multiplier'])) {
                        $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                    }

                   
                    if ($product['enable_stock']) {
                        Log::info($products);
                        Log::info($decrease_qty);
                        Log::info($client->business_location_id);
                        $this->productUtil->decreaseProductQuantity(
                            $product['product_id'],
                            $product['variation_id'],
                            $client->business_location_id,
                            $decrease_qty
                        );
                    }
                }

                $payment_status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                $transaction->payment_status = $payment_status;

                $business = [
                    'id' => $business_id,
                    'accounting_method' => session()->get('business.accounting_method'),
                    'location_id' => $client->business_location_id,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

                // $whatsapp_link = $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
            }

            // Media::uploadMedia($business_id, $transaction, request(), 'documents');
            $this->transactionUtil->activityLog($transaction, 'added');

            DB::commit();

            return [
                'success' => true,
                'message' => trans("sale.pos_sale_added"),
                'transaction' => $transaction,
            ];

        } catch (\Exception $e) {
            \Log::error("Error in makeSale: " . $e->getMessage());
            DB::rollBack();
            return $this->handleException($e, __('message.Error happened while making sale'));
        }
    }

    /**
     * Update the specified Order.
     */
    public function update($request, $order)
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

            if (!$order) {
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


    public function checkQuantityAndLocation()
    {

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
                return $this->returnJSON(null, __('message.Order will be shipped tomorrow due to multiple locations'));
                ;
            }

            return $this->returnJSON(null, __('message.Order will be shipped today'));
            ;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing cart items'));
        }
    }

}
