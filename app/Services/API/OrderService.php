<?php

namespace App\Services\API;

use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\Order\OrderCollection;
use App\Http\Resources\Order\OrderResource;
use App\Jobs\TransferProductJob;
use App\Models\ApplicationSettings;
use App\Models\Cart;
use App\Models\Client;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Notifications\OrderCreatedNotification;
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
    protected $moduleUtil;
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
        ModuleUtil $moduleUtil,
        OrderTrackingService $orderTrackingService,
        CartService $cartService
    ) {
        $this->contactUtil = $contactUtil;
        $this->moduleUtil = $moduleUtil;
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

            $client = Client::find(Auth::id());
            $query = Order::where('client_id', $client->id);

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
            $orderDelivery = DeliveryOrder::where('order_id', $order->id)->first();

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
            DB::beginTransaction();

            $carts = Cart::where('client_id', Auth::id())
                ->with(['product', 'variation.variation_location_details', 'client'])
                ->get();

            // Check if the cart is empty
            if ($carts->isEmpty()) {
                return $this->returnJSON([], __('message.Cart is empty'));
            }

            $client = Client::findOrFail(Auth::id());
            $orderTotal = $carts->sum('total');

            // Create the order
            $order = Order::create([
                'client_id' => Auth::id(),
                'sub_total' => $orderTotal,
                'total' => $orderTotal,
                'payment_method' => 'Cash on delivery',
                'order_type' => 'order',
                'business_location_id' => $client->business_location_id,
            ]);

            $this->orderTrackingService->store($order, 'pending');
            $this->cartService->clearCart();

            // Process each cart item
            foreach ($carts as $cart) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cart->product_id,
                    'variation_id' => $cart->variation_id,
                    'quantity' => $cart->quantity,
                    'price' => $cart->price,
                    'discount' => $cart->discount ?? 0,
                    'sub_total' => $cart->total,
                ]);

                // Handle stock transfer and updates
                $this->handleQuantityTransfer($cart, $client, $order, $orderItem);
            }

            // Create sale record
            $saleResponse = $this->makeSale($order, $client, $carts);

            DB::commit();

            // Notify admins about the order
            $admins = $this->moduleUtil->get_admins($client->contact->business_id);
            \Notification::send($admins, new OrderCreatedNotification($order));

            return new OrderResource($order);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error in store method: " . $e->getMessage());
            return $this->handleException($e, __('message.Error happened while storing Order'));
        }
    }

    protected function handleQuantityTransfer($cart, $client, $order, $orderItem)
    {
        $requiredQuantity = $cart->quantity;
        $clientLocationId = $client->business_location_id;
    
        // Step 1: Check if the required quantity exists in the client's location
        $clientLocationDetail = $cart->variation->variation_location_details
            ->firstWhere('location.id', $clientLocationId);
    
        $availableAtClientLocation = $clientLocationDetail ? $clientLocationDetail->qty_available : 0;
    
        if ($availableAtClientLocation >= $requiredQuantity) {
            // If sufficient stock exists, update stock directly
            $this->updateStock($orderItem, $clientLocationId, $requiredQuantity);
        } else {
            // Step 2: Calculate deficit and transfer stock if necessary
            $deficit = $requiredQuantity - $availableAtClientLocation;
    
            foreach ($cart->variation->variation_location_details as $locationDetail) {
                if ($locationDetail->location->id !== $clientLocationId && $deficit > 0) {
                    $availableQty = $locationDetail->qty_available;
    
                    if ($availableQty > 0) {
                        $transferQty = min($deficit, $availableQty);
    
                        // Perform the stock transfer
                        $this->transferQuantity(
                            $order,
                            $orderItem,
                            $client,
                            $locationDetail->location->id,
                            $clientLocationId,
                            $transferQty
                        );
    
                        $deficit -= $transferQty;
    
                        // Break if the deficit is covered
                        if ($deficit <= 0) break;
                    }
                }
            }
    
            // Step 3: Finalize by updating stock at the client's location
            $this->updateStock($orderItem, $clientLocationId, $requiredQuantity);
        }
    }
    

    /**
     * Transfers a specified quantity from one location to another.
     */
    protected function transferQuantity($order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)
    {
        try {
            DB::beginTransaction();

            $business_id = $client->contact->business_id;

            $inputData = [
                'location_id' => $fromLocationId,
                'transaction_date' => now(),
                'final_total' => $order->total,
                'type' => 'sell_transfer',
                'business_id' => $business_id,
                'created_by' => 1,
                'shipping_charges' => $this->productUtil->num_uf($order->shipping_cost),
                'payment_status' => 'paid',
                'status' => 'in_transit',
                'total_before_tax' => $order->total,
                'transfer_type'=>'application_transfer'
            ];

            // Generate reference number
            $refCount = $this->productUtil->setAndGetReferenceCount('stock_transfer', $business_id);
            $inputData['ref_no'] = $this->productUtil->generateReferenceNumber('stock_transfer', $refCount, $business_id);

            $sellTransfer = Transaction::create($inputData);
            $inputData['type'] = 'purchase_transfer';
            $inputData['location_id'] = $toLocationId;
            $inputData['transfer_parent_id'] = $sellTransfer->id;
            $inputData['status'] = 'in_transit';

            $purchaseTransfer = Transaction::create($inputData);

            $products = [
                [
                    'product_id' => $orderItem->product_id,
                    'variation_id' => $orderItem->variation_id,
                    'quantity' => $quantity,
                    'unit_price' => $orderItem->price,
                    'unit_price_inc_tax' => $orderItem->price,
                    'enable_stock' => $orderItem->product->enable_stock,
                    'item_tax' => 0,
                    'tax_id' => null,
                ]
            ];

            $this->transactionUtil->createOrUpdateSellLines($sellTransfer, $products, $fromLocationId);
            $purchaseTransfer->purchase_lines()->createMany($products);

            foreach ($products as $product) {
                $this->productUtil->decreaseProductQuantity(
                    $product['product_id'],
                    $product['variation_id'],
                    $sellTransfer->location_id,
                    $product['quantity']
                );

                $this->productUtil->updateProductQuantity(
                    $purchaseTransfer->location_id,
                    $product['product_id'],
                    $product['variation_id'],
                    $product['quantity']
                );
            }


            \Log::info("transfer quantity",[$quantity]);
            $this->storeTransferOrder($order,$orderItem,$quantity,$fromLocationId,$toLocationId);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
            throw new \Exception('Stock transfer failed: ' . $e->getMessage());
        }
    }

    /**
     * Update stock directly without a transfer (e.g., from the client's location).
     */
    protected function updateStock($orderItem, $locationId, $quantity)
    {
        \Log::info("update stock");
        $this->productUtil->decreaseProductQuantity(
            $orderItem->product_id,
            $orderItem->variation_id,
            $locationId,
            $quantity
        );
    }

    protected function makeSale($order, $client, $carts)
    {
        $is_direct_sale = true;

        try {
            $transactionData = [
                "business_id" => $client->contact->business_id,
                "location_id" => $client->business_location_id,
                'final_total' => $order->total,
                "type" => "sell",
                "status" => "final",
                'payment_status' => 'paid',
                "contact_id" => $client->contact_id,
                "transaction_date" => now(),
                "total_before_tax" => $order->total,
                "tax_amount" => "0.0000",
                "created_by" => 1,
                'discount_amount' => 0,

            ];
            $cartsArray = $carts->map(function ($cart) {
                // Calculate the unit price including tax if necessary, adjust based on your tax rules.
                // $unit_price_inc_tax = $cart->price + ($cart->price * $cart->tax_rate / 100); // Example tax calculation
                return [
                    'unit_price_inc_tax' => $cart->price,
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
            $tax_id = 1;

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

            // Create or update sell lines using $carts instead of $input['products']
            $products = $carts->map(function ($cart) {
                return [
                    'product_id' => $cart->product_id,
                    'variation_id' => $cart->variation_id,
                    'quantity' => $cart->quantity,
                    'price' => $cart->price,
                    'discount' => $cart->discount,
                    'enable_stock' => 1,
                    'unit_price' => $cart->price,
                    'item_tax' => 0,
                    'tax_id' => null,
                    'unit_price_inc_tax' => $cart->price,

                ];
            })->toArray();

            $sellLines = $this->transactionUtil->createOrUpdateSellLines($transaction, $products, $client->business_location_id);

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


                    // if ($product['enable_stock']) {
                    //     Log::info($products);
                    //     Log::info($decrease_qty);
                    //     Log::info($client->business_location_id);
                    //     $this->productUtil->decreaseProductQuantity(
                    //         $product['product_id'],
                    //         $product['variation_id'],
                    //         $client->business_location_id,
                    //         $decrease_qty
                    //     );
                    // }
                }

                $payment_status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                $transaction->payment_status = $payment_status;

                $business = [
                    'id' => $business_id,
                    'accounting_method' => session()->get('business.accounting_method'),
                    'location_id' => $client->business_location_id,
                ];
                // $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

                // $whatsapp_link = $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
            }

            // Media::uploadMedia($business_id, $transaction, request(), 'documents');
            $this->transactionUtil->activityLog($transaction, 'added',null,
            ['order_number'=>$order->number,'client'=>$client->contact->name]);

            DB::commit();

            return [
                'success' => true,
                'message' => trans("sale.pos_sale_added"),
                'transaction' => $transaction,
            ];

        } catch (\Exception $e) {
            \Log::error("Error in makeSale: " . $e->getMessage() . " Line:" . $e->getLine());
            DB::rollBack();
            return $this->handleException($e, __('message.Error happened while making sale'));
        }
    }



    // protected function transferQuantity($order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)
    // {
    //     // Dispatch the job with a 10-minute delay
    //     TransferProductJob::dispatch($order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)
    //         ->delay(now());

    //     \Log::info("TransferProductJob dispatched for Order: {$order->id}, OrderItem: {$orderItem->id}");
    // }


    public function storeRefundOrder($order, $items)
    {
        try {
            DB::beginTransaction();

            // Ensure $items is a collection to use pluck
            if (is_array($items)) {
                $items = collect($items); // Convert array to collection
            }

            // Fetch the order items using the provided item IDs
            $orderItems = OrderItem::whereIn('id', $items->pluck('id'))->get();

            // Check if order items exist
            if ($orderItems->isEmpty()) {
                throw new \Exception('No valid order items found for refund.');
            }

            $client = Client::findOrFail($order->client_id);
            $subTotal = 0;

            // Map refund amounts to order items
            $itemsWithRefund = $items->keyBy('id');

            // Calculate the subtotal for the refund order
            foreach ($orderItems as $orderItem) {
                if (!isset($itemsWithRefund[$orderItem->id]['refund_amount'])) {
                    throw new \Exception("Refund amount is missing for item ID {$orderItem->id}");
                }

                $refundAmount = $itemsWithRefund[$orderItem->id]['refund_amount'];

                // Validate refund amount
                if ($refundAmount > $orderItem->quantity) {
                    throw new \Exception("Refund amount exceeds available quantity for item ID {$orderItem->id}");
                }

                // Add to subtotal
                $subTotal += $refundAmount * $orderItem->price;
            }

            $orderTotal = $subTotal; // Adjustments for taxes or other calculations can be added here

            // Create the refund order
            $newRefundOrder = Order::create([
                'parent_order_id' => $order->id,
                'client_id' => $order->client_id,
                'sub_total' => $subTotal,
                'total' => $orderTotal,
                'payment_method' => 'Cash on delivery',
                'order_type' => 'order_refund',
                'business_location_id' => $client->business_location_id,
            ]);

            // Track the refund order status
            $this->orderTrackingService->store($newRefundOrder, 'pending');

            // Create refund order items
            foreach ($orderItems as $orderItem) {
                $refundAmount = $itemsWithRefund[$orderItem->id]['refund_amount'];

                OrderItem::create([
                    'order_id' => $newRefundOrder->id,
                    'product_id' => $orderItem->product_id,
                    'variation_id' => $orderItem->variation_id,
                    'quantity' => $refundAmount, // Use refund amount here
                    'price' => $orderItem->price,
                    'discount' => $orderItem->discount ?? 0,
                    'sub_total' => $refundAmount * $orderItem->price, // Calculate based on refund amount
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Refund order created successfully.',
                'refund_order' => $newRefundOrder,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error("Refund Order Error: {$e->getMessage()}");

            return [
                'success' => false,
                'message' => 'Failed to create refund order. Please try again.',
            ];
        }
    }


    public function storeRefundOrderItem($order, $orderRefund)
    {
        try {
            DB::beginTransaction();

            // Check for an existing parent refund order
            $existOrder = Order::where('parent_order_id', $order->id)->first();

            // Fetch the specific order item using the provided item ID
            $orderItem = OrderItem::find($orderRefund->order_item_id);

            // Check if the order item exists
            if (!$orderItem) {
                throw new \Exception("Order item with ID  not found for refund.");
            }

            // Validate refund amount
            $refundAmount = $orderRefund->amount ?? null;
            if (is_null($refundAmount)) {
                throw new \Exception("Refund amount is missing for item ID {$orderItem->id}.");
            }

            if ($refundAmount > $orderItem->quantity) {
                throw new \Exception("Refund amount exceeds available quantity for item ID {$orderItem->id}.");
            }

            // Calculate subtotal for the refund order
            $subTotal = $refundAmount * $orderItem->price;

            if ($existOrder) {
                // Add refund item to the existing parent order
                OrderItem::create([
                    'order_id' => $existOrder->id,
                    'parent_order_id' => $existOrder->parent_order_id,
                    'product_id' => $orderItem->product_id,
                    'variation_id' => $orderItem->variation_id,
                    'quantity' => $refundAmount,
                    'price' => $orderItem->price,
                    'discount' => $orderItem->discount ?? 0,
                    'sub_total' => $subTotal,
                ]);

                // Update the parent order's totals
                $existOrder->sub_total += $subTotal;
                $existOrder->total += $subTotal; // Adjustments for taxes or other calculations can be added here
                $existOrder->save();

            } else {
                // Fetch client details
                $client = Client::findOrFail($order->client_id);

                // Create a new refund order
                $parentOrder = Order::create([
                    'parent_order_id' => $order->id,
                    'client_id' => $order->client_id,
                    'sub_total' => $subTotal,
                    'total' => $subTotal, // Adjustments for taxes or other calculations can be added here
                    'payment_method' => 'Cash on delivery',
                    'order_type' => 'order_refund',
                    'business_location_id' => $client->business_location_id,
                ]);

                // Track the refund order status
                $this->orderTrackingService->store($parentOrder, 'pending');

                // Add the refund item to the newly created order
                OrderItem::create([
                    'order_id' => $parentOrder->id,
                    'product_id' => $orderItem->product_id,
                    'variation_id' => $orderItem->variation_id,
                    'quantity' => $refundAmount,
                    'price' => $orderItem->price,
                    'discount' => $orderItem->discount ?? 0,
                    'sub_total' => $subTotal,
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Refund order item processed successfully.',
                'refund_order' => $parentOrder,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error("Refund Order Error: {$e->getMessage()}");

            return [
                'success' => false,
                'message' => 'Failed to process refund order item. Please try again.',
            ];
        }
    }

    public function 
    storeTransferOrder($order, $orderItem, $quantity,$fromLocationId,$toLocationId)
    {
        DB::beginTransaction();
    
        try {
            // Validate order item existence
            $orderItem = OrderItem::find($orderItem->id);
            if (!$orderItem) {
                throw new \Exception("Order item with ID {$orderItem->id} not found.");
            }
    
            // Validate quantity
            if ($quantity <= 0 || $quantity > $orderItem->quantity) {
                throw new \Exception("Invalid quantity. It must be greater than zero and not exceed available stock.");
            }
    
            // Calculate subtotal for the transfer
            $subTotal = $quantity * $orderItem->price;
    
            \Log::info('sub_total',[$quantity * $orderItem->price]);
            // Check if a transfer order already exists for the parent order
            $transferOrder = Order::where('parent_order_id', $order->id)
            ->where('order_type','order_transfer')->first();
            
            if ($transferOrder) {
                // Update existing transfer order
                $this->addTransferItemToOrder($transferOrder, $orderItem, $quantity, $subTotal);
            } else {
                // Create a new transfer order
                $transferOrder = $this->createTransferOrder($order, $subTotal,$fromLocationId,$toLocationId);
                $this->addTransferItemToOrder($transferOrder, $orderItem, $quantity, $subTotal);
            }
    
            DB::commit();
    
            return [
                'success' => true,
                'message' => 'Transfer order item processed successfully.',
                'transfer_order' => $transferOrder,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
    
            \Log::error("Transfer Order Error: {$e->getMessage()}");
    
            return [
                'success' => false,
                'message' => 'Failed to process transfer order item. Please try again.',
            ];
        }
    }
    
    private function createTransferOrder($order, $subTotal,$fromLocationId,$toLocationId)
    {

        // Fetch client details
        $client = Client::findOrFail($order->client_id);
    
        \Log::info('inside sub total',[$subTotal]);
        // Create a new transfer order
        return Order::create([
            'parent_order_id' => $order->id,
            'client_id' => $order->client_id,
            'sub_total' => $subTotal,
            'total' => $subTotal, // Adjustments for taxes or other calculations can be added here
            'payment_method' => 'Cash on delivery', // Modify as needed
            'order_type' => 'order_transfer', // Adjust order type to reflect the transfer
            'business_location_id' => $client->business_location_id,
            'from_business_location_id'=> $fromLocationId,
            'to_business_location_id'=>$toLocationId
        ]);
    }
    
    private function addTransferItemToOrder($order, $orderItem, $quantity, $subTotal)
    {
        // Add transfer item to the order
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $orderItem->product_id,
            'variation_id' => $orderItem->variation_id,
            'quantity' => $quantity,
            'price' => $orderItem->price,
            'discount' => $orderItem->discount ?? 0,
            'sub_total' => $subTotal,
        ]);

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
            // return $order;

            return new OrderResource($order);

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

            // Get the authenticated client
            $client = Client::findOrFail(Auth::id());

            // Retrieve application settings for messages
            $settingTodayMessages = ApplicationSettings::where('key', 'order_message_today')->value('value');
            $settingTomorrowMessages = ApplicationSettings::where('key', 'order_message_tomorrow')->value('value');

            // Validate application settings
            if (!$settingTodayMessages || !$settingTomorrowMessages) {
                return $this->returnJSON(
                    new ClientResource($client),
                    __('message.Application settings are missing')
                );
            }


            // Retrieve cart items with necessary relations
            $carts = Cart::where('client_id', Auth::id())
                ->with(['product', 'variation.variation_location_details'])
                ->get();

            // Check if cart is empty
            if ($carts->isEmpty()) {
                return $this->returnJSON(null, __('message.Cart is empty'));
            }

            // Check product quantities
            $multiLocationMessage = $this->hasInsufficientQuantities($carts, $client->business_location_id);

            // Return appropriate response based on multi-location status
            $message = $multiLocationMessage ? $settingTomorrowMessages : $settingTodayMessages;

            return $this->returnJSON(new ClientResource($client), $message);
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('Error in checkQuantityAndLocation: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->handleException($e, __('message.Error happened while listing cart items'));
        }
    }

    /**
     * Check if any cart item has insufficient quantities at the specified location.
     *
     * @param \Illuminate\Support\Collection $carts
     * @param int $businessLocationId
     * @return bool
     */
    private function hasInsufficientQuantities($carts, $businessLocationId)
    {
        foreach ($carts as $cart) {
            $quantity = $cart->quantity;
            $locationDetails = $cart->variation->variation_location_details;

            // Check if sufficient quantity is available at the specified location
            if (!$this->checkSufficientQuantity($locationDetails, $businessLocationId, $quantity)) {
                return true; // Multi-location message is required
            }
        }

        return false; // All items have sufficient quantities
    }


}
