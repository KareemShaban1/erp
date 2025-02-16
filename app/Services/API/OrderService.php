<?php

namespace App\Services\API;

use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\Order\OrderCollection;
use App\Http\Resources\Order\OrderResource;
use App\Models\ApplicationSettings;
use App\Models\Cart;
use App\Models\Client;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Notifications\OrderCreatedNotification;
use App\Notifications\OrderRefundCreatedNotification;
use App\Services\BaseService;
use App\Traits\CheckQuantityTrait;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use App\Utils\BusinessUtil;
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
    protected $businessUtil;

    protected $quantityTransferService;

    public function __construct(
        ProductUtil $productUtil,
        TransactionUtil $transactionUtil,
        ContactUtil $contactUtil,
        ModuleUtil $moduleUtil,
        OrderTrackingService $orderTrackingService,
        CartService $cartService,
        BusinessUtil $businessUtil,
        QuantityTransferService $quantityTransferService
    ) {
        $this->contactUtil = $contactUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cartService = $cartService;
        $this->orderTrackingService = $orderTrackingService;
        $this->businessUtil = $businessUtil;
        $this->quantityTransferService = $quantityTransferService;
    }
    /**
     * Get all Orders with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {
            $client = Client::find(Auth::id());
            $query = Order::where('client_id', $client->id)
                ->where('order_type', 'order')->latest();

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
            $order = Order::find($id);

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

            $orderShippingCost =
                ApplicationSettings::where('key', 'order_shipping_cost')
                    ->value('value');
            if ($orderShippingCost) {
                $orderTotal += $orderShippingCost;
            }


            // Create the order
            $order = Order::create([
                'client_id' => Auth::id(),
                'sub_total' => $orderTotal,
                'total' => $orderTotal,
                'payment_method' => 'Cash on delivery',
                'order_type' => 'order',
                'shipping_cost' => $orderShippingCost ?? 0,
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
                $this->quantityTransferService->handleQuantityTransfer($cart, $client, $order, $orderItem);
            }

            // Create sale record
            $this->makeSell($order, $client, $carts);

            DB::commit();

            // Notify admins and users about the order
            $admins = $this->moduleUtil->get_admins($client->contact->business_id);
            $users = $this->moduleUtil->getBusinessUsers($client->contact->business_id, $order);

            \Notification::send($admins, new OrderCreatedNotification($order));
            \Notification::send($users, new OrderCreatedNotification($order));

            return new OrderResource($order);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error in store method: " . $e->getMessage());
            return $this->handleException($e, __('message.Error happened while storing Order'));
        }
    }

    protected function makeSell($order, $client, $carts)
    {
        $is_direct_sale = true;

        try {
            $transactionData = [
                "business_id" => $client->contact->business_id,
                "location_id" => $client->business_location_id,
                "order_id" => $order->id,
                'final_total' => $order->total,
                "type" => "sell",
                "status" => "final",
                'payment_status' => 'paid',
                "contact_id" => $client->contact_id,
                "transaction_date" => now(),
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

            // $invoice_total['total_before_tax'] = $invoice_total['total_before_tax'] ?? 0;

            $invoice_total['total_before_tax'] = $order->total;

            $transactionData['invoice_total'] = $invoice_total;

            $business_id = $client->contact->business_id;
            $user_id = 1;

            DB::beginTransaction();

            $transactionData['transaction_date'] = Carbon::now();

            $contact_id = $client->contact_id;

            $transaction = $this->transactionUtil->createSellTransaction($business_id, $transactionData, $invoice_total, $user_id);

            // Create or update sell lines using $carts instead of $input['products']
            $products = $carts->map(function ($cart) {
                return [
                    'product_id' => $cart->product_id,
                    'variation_id' => $cart->variation_id,
                    'quantity' => $cart->quantity,
                    'price' => $cart->price,
                    'discount' => $cart->discount,
                    'line_discount_type' => $cart->discount_type,
                    'line_discount_amount' => $cart->discount,
                    'enable_stock' => 1,
                    'unit_price' => $cart->price,
                    'item_tax' => 0,
                    'tax_id' => null,
                    'unit_price_inc_tax' => $cart->price,

                ];
            })->toArray();

            $sellLines = $this->transactionUtil->createOrUpdateSellLines($transaction, $products, $client->business_location_id);


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

                $business_details = $this->businessUtil->getDetails($business_id);
                $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                $business = [
                    'id' => $business_id,
                    'accounting_method' => 'fifo',
                    'location_id' => $client->business_location_id,
                    'pos_settings' => $pos_settings,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

            }

            // Media::uploadMedia($business_id, $transaction, request(), 'documents');
            $this->transactionUtil->activityLog(
                $transaction,
                'added',
                null,
                ['order_number' => $order->number, 'client' => $client->contact->name]
            );

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



    // public function storeRefundOrder($parentOrder, $items)
    // {
    //     try {
    //         DB::beginTransaction();

    //         // Ensure $items is a collection to use pluck
    //         if (is_array($items)) {
    //             $items = collect($items); // Convert array to collection
    //         }

    //         // Fetch the order items using the provided item IDs
    //         $orderItems = OrderItem::whereIn('id', $items->pluck('id'))->get();

    //         // Check if order items exist
    //         if ($orderItems->isEmpty()) {
    //             throw new \Exception('No valid order items found for refund.');
    //         }

    //         $client = Client::findOrFail($parentOrder->client_id);
    //         $subTotal = 0;

    //         // Map refund amounts to order items
    //         $itemsWithRefund = $items->keyBy('id');

    //         // Calculate the subtotal for the refund order
    //         foreach ($orderItems as $orderItem) {
    //             if (!isset($itemsWithRefund[$orderItem->id]['refund_amount'])) {
    //                 throw new \Exception("Refund amount is missing for item ID {$orderItem->id}");
    //             }

    //             $refundAmount = $itemsWithRefund[$orderItem->id]['refund_amount'];

    //             // Validate refund amount
    //             if ($refundAmount > $orderItem->quantity) {
    //                 throw new \Exception("Refund amount exceeds available quantity for item ID {$orderItem->id}");
    //             }

    //             // Add to subtotal
    //             $subTotal += $refundAmount * $orderItem->price;
    //         }

    //         $orderTotal = $subTotal; // Adjustments for taxes or other calculations can be added here

    //         $existRefundOrder = Order::
    //             where('parent_order_id', $parentOrder->id)
    //             ->where('client_id', $parentOrder->client_id)
    //             ->where('order_type', 'order_refund')
    //             ->whereNotIn('order_status', ['shipped', 'completed', 'cancelled'])
    //             ->first();
    //         if ($existRefundOrder) {

    //             // Add refund item to the existing parent order
    //             OrderItem::create([
    //                 'order_id' => $existRefundOrder->id,
    //                 'parent_order_id' => $existRefundOrder->parent_order_id,
    //                 'product_id' => $orderItem->product_id,
    //                 'variation_id' => $orderItem->variation_id,
    //                 'quantity' => $refundAmount,
    //                 'price' => $orderItem->price,
    //                 'discount' => $orderItem->discount ?? 0,
    //                 'sub_total' => $subTotal,
    //             ]);

    //             // Update the parent order's totals
    //             $existRefundOrder->sub_total += $subTotal;
    //             $existRefundOrder->total += $subTotal;
    //             $existRefundOrder->save();
    //         } else {
    //             // Create the refund order
    //             $newRefundOrder = Order::create([
    //                 'parent_order_id' => $parentOrder->id,
    //                 'client_id' => $parentOrder->client_id,
    //                 'sub_total' => $subTotal,
    //                 'total' => $orderTotal,
    //                 'payment_method' => 'Cash on delivery',
    //                 'order_type' => 'order_refund',
    //                 'business_location_id' => $client->business_location_id,
    //             ]);

    //             // Track the refund order status
    //             $this->orderTrackingService->store($newRefundOrder, 'pending');

    //             // Create refund order items
    //             foreach ($orderItems as $orderItem) {
    //                 $refundAmount = $itemsWithRefund[$orderItem->id]['refund_amount'];

    //                 OrderItem::create([
    //                     'order_id' => $newRefundOrder->id,
    //                     'product_id' => $orderItem->product_id,
    //                     'variation_id' => $orderItem->variation_id,
    //                     'quantity' => $refundAmount, // Use refund amount here
    //                     'price' => $orderItem->price,
    //                     'discount' => $orderItem->discount ?? 0,
    //                     'sub_total' => $refundAmount * $orderItem->price, // Calculate based on refund amount
    //                 ]);
    //             }

    //             // Notify admins and users about the order
    //             $admins = $this->moduleUtil->get_admins($client->contact->business_id);
    //             $users = $this->moduleUtil->getBusinessUsers($client->contact->business_id, $newRefundOrder);

    //             \Notification::send($admins, new OrderRefundCreatedNotification($newRefundOrder));
    //             \Notification::send($users, new OrderRefundCreatedNotification($newRefundOrder));

    //         }

    //         DB::commit();


    //         return [
    //             'success' => true,
    //             'message' => 'Refund order created successfully.',
    //             'refund_order' => $newRefundOrder,
    //         ];
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         \Log::error("Refund Order Error: {$e->getMessage()}");

    //         return [
    //             'success' => false,
    //             'message' => 'Failed to create refund order. Please try again.',
    //         ];
    //     }
    // }


    private function createRefundOrderItems($orderId, $orderItems, $itemsWithRefund)
    {
        foreach ($orderItems as $orderItem) {
            // Ensure the item exists in the refund request
            if (!isset($itemsWithRefund[$orderItem->id])) {
                continue;
            }

            $refundAmount = $itemsWithRefund[$orderItem->id]['refund_amount'];

            OrderItem::create([
                'order_id' => $orderId,
                'product_id' => $orderItem->product_id,
                'variation_id' => $orderItem->variation_id,
                'quantity' => $refundAmount,
                'price' => $orderItem->price,
                'discount' => $orderItem->discount ?? 0,
                'sub_total' => $refundAmount * $orderItem->price,
            ]);
        }
    }


    public function storeRefundOrder($parentOrder, $items)
    {
        try {
            DB::beginTransaction();

            // Convert $items to a collection if needed
            $items = collect($items);

            $orderItems = OrderItem::whereIn('id', $items->pluck('id'))->get();
            if ($orderItems->isEmpty()) {
                throw new \Exception('No valid order items found for refund.');
            }

            $client = Client::findOrFail($parentOrder->client_id);
            $itemsWithRefund = $items->keyBy('id');
            $subTotal = 0;

            foreach ($orderItems as $orderItem) {
                $refundAmount = $itemsWithRefund[$orderItem->id]['refund_amount'];
                if ($refundAmount > $orderItem->quantity) {
                    throw new \Exception("Refund amount exceeds available quantity for item ID {$orderItem->id}");
                }
                $subTotal += $refundAmount * $orderItem->price;
            }

            $existRefundOrder = Order::where('parent_order_id', $parentOrder->id)
                ->where('client_id', $parentOrder->client_id)
                ->where('order_type', 'order_refund')
                ->whereNotIn('order_status', ['shipped', 'completed', 'cancelled'])
                ->first();



            if ($existRefundOrder) {
                $this->createRefundOrderItems($existRefundOrder->id, $orderItems, $itemsWithRefund);

                $existRefundOrder->sub_total += $subTotal;
                $existRefundOrder->total += $subTotal;
                $existRefundOrder->save();
            } else {
                $refundOrderShippingCost =
                    ApplicationSettings::where('key', 'refund_order_shipping_cost')
                        ->value('value');
                if ($refundOrderShippingCost) {
                    $subTotal += $refundOrderShippingCost;
                }
                $newRefundOrder = Order::create([
                    'parent_order_id' => $parentOrder->id,
                    'client_id' => $parentOrder->client_id,
                    'sub_total' => $subTotal,
                    'total' => $subTotal,
                    'shipping_cost' => $refundOrderShippingCost ?? 0,
                    'payment_method' => 'Cash on delivery',
                    'order_type' => 'order_refund',
                    'business_location_id' => $client->business_location_id,
                ]);

                $this->orderTrackingService->store($newRefundOrder, 'pending');
                // $this->createRefundOrderItems($newRefundOrder->id, $orderItems, $itemsWithRefund);
                $this->createRefundOrderItems(
                    $newRefundOrder->id,
                    $orderItems->whereIn('id', $itemsWithRefund->keys()), // Filter to only refund items
                    $itemsWithRefund
                );


                $admins = $this->moduleUtil->get_admins($client->contact->business_id);
                $users = $this->moduleUtil->getBusinessUsers($client->contact->business_id, $newRefundOrder);

                \Notification::send($admins, new OrderRefundCreatedNotification($newRefundOrder));
                \Notification::send($users, new OrderRefundCreatedNotification($newRefundOrder));
            }

            DB::commit();

            return ['success' => true, 'message' => 'Refund order created successfully.'];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Refund Order Error: {$e->getMessage()}");
            return ['success' => false, 'message' => 'Failed to create refund order. Please try again.'];
        }
    }








    // public function storeRefundOrderItem($parentOrder, $orderRefund)
    // {
    //     try {
    //         DB::beginTransaction();

    //         // Check for an existing parent refund order
    //         $existRefundOrder = Order::
    //             where('parent_order_id', $parentOrder->id)
    //             ->where('client_id', $parentOrder->client_id)
    //             ->where('order_type', 'order_refund')
    //             ->whereNotIn('order_status', ['shipped', 'completed', 'cancelled'])
    //             ->first();

    //         // Fetch the specific order item using the provided item ID
    //         $orderItem = OrderItem::find($orderRefund->order_item_id);


    //         // Check if the order item exists
    //         if (!$orderItem) {
    //             throw new \Exception("Order item with ID  not found for refund.");
    //         }

    //         // Validate refund amount
    //         $refundAmount = $orderRefund->amount ?? null;
    //         if (is_null($refundAmount)) {
    //             throw new \Exception("Refund amount is missing for item ID {$orderItem->id}.");
    //         }

    //         if ($refundAmount > $orderItem->quantity) {
    //             throw new \Exception("Refund amount exceeds available quantity for item ID {$orderItem->id}.");
    //         }

    //         // Calculate subtotal for the refund order
    //         $subTotal = $refundAmount * $orderItem->price;

    //         \Log::info('refund_data', [$existRefundOrder, $orderItem, $subTotal]);


    //         if ($existRefundOrder) {
    //             // Add refund item to the existing parent order
    //             OrderItem::create([
    //                 'order_id' => $existRefundOrder->id,
    //                 'parent_order_id' => $existRefundOrder->parent_order_id,
    //                 'product_id' => $orderItem->product_id,
    //                 'variation_id' => $orderItem->variation_id,
    //                 'quantity' => $refundAmount,
    //                 'price' => $orderItem->price,
    //                 'discount' => $orderItem->discount ?? 0,
    //                 'sub_total' => $subTotal,
    //             ]);

    //             // Update the parent order's totals
    //             $existRefundOrder->sub_total += $subTotal;
    //             $existRefundOrder->total += $subTotal;
    //             $existRefundOrder->save();

    //         } else {
    //             // Fetch client details
    //             $client = Client::findOrFail($parentOrder->client_id);

    //             // Create a new refund order
    //             $newRefundOrder = Order::create([
    //                 'parent_order_id' => $parentOrder->id,
    //                 'client_id' => $parentOrder->client_id,
    //                 'sub_total' => $subTotal,
    //                 'total' => $subTotal,
    //                 'payment_method' => 'Cash on delivery',
    //                 'order_type' => 'order_refund',
    //                 'business_location_id' => $client->business_location_id,
    //             ]);

    //             // Track the refund order status
    //             $this->orderTrackingService->store($newRefundOrder, 'pending');

    //             // Add the refund item to the newly created order
    //             OrderItem::create([
    //                 'order_id' => $newRefundOrder->id,
    //                 'product_id' => $orderItem->product_id,
    //                 'variation_id' => $orderItem->variation_id,
    //                 'quantity' => $refundAmount,
    //                 'price' => $orderItem->price,
    //                 'discount' => $orderItem->discount ?? 0,
    //                 'sub_total' => $subTotal,
    //             ]);

    //             \Log::info('new_refund', [$newRefundOrder]);

    //         }



    //         DB::commit();

    //         return [
    //             'success' => true,
    //             'message' => 'Refund order item processed successfully.',
    //             'refund_order' => $parentOrder,
    //         ];
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         \Log::info('refund_error', [$e]);
    //         \Log::error("Refund Order Error: {$e->getMessage()}");

    //         return [
    //             'success' => false,
    //             'message' => 'Failed to process refund order item. Please try again.',
    //         ];
    //     }
    // }


    public function storeRefundOrderItem($parentOrder, $orderRefund)
    {
        try {
            DB::beginTransaction();

            // Validate refund data
            if (is_null($orderRefund->order_item_id)) {
                throw new \Exception("Order item ID is missing in the refund data.");
            }

            $orderItem = OrderItem::find($orderRefund->order_item_id);

            \Log::info('orderRefund', [$orderRefund]);

            if (!$orderItem) {
                throw new \Exception("Order item with ID {$orderRefund->order_item_id} not found for refund.");
            }

            $refundAmount = $orderRefund->amount ?? null;
            if (is_null($refundAmount) || $refundAmount <= 0) {
                throw new \Exception("Refund amount must be greater than zero for item ID {$orderItem->id}.");
            }

            if ($refundAmount > $orderItem->quantity) {
                throw new \Exception("Refund amount exceeds available quantity for item ID {$orderItem->id}.");
            }

            $subTotal = $refundAmount * $orderItem->price;

            $existRefundOrder = Order::where('parent_order_id', $parentOrder->id)
                ->where('client_id', $parentOrder->client_id)
                ->where('order_type', 'order_refund')
                ->whereNotIn('order_status', ['shipped', 'completed', 'cancelled'])
                ->first();



            if ($existRefundOrder) {
                // Add refund item to existing order
                OrderItem::create([
                    'order_id' => $existRefundOrder->id,
                    'parent_order_id' => $existRefundOrder->parent_order_id,
                    'product_id' => $orderItem->product_id,
                    'variation_id' => $orderItem->variation_id,
                    'quantity' => $refundAmount,
                    'price' => $orderItem->price,
                    'discount' => $orderItem->discount ?? 0,
                    'sub_total' => $subTotal,
                ]);

                // Update existing order totals
                $existRefundOrder->sub_total += $subTotal;
                $existRefundOrder->total += $subTotal;
                $existRefundOrder->save();

                \Log::info('existRefundOrder', [$existRefundOrder]);

            } else {
                // Create new refund order
                $client = Client::findOrFail($parentOrder->client_id);

                $refundOrderShippingCost =
                    ApplicationSettings::where('key', 'refund_order_shipping_cost')
                        ->value('value');
                if ($refundOrderShippingCost) {
                    $subTotal += $refundOrderShippingCost;
                }
                $newRefundOrder = Order::create([
                    'parent_order_id' => $parentOrder->id,
                    'client_id' => $parentOrder->client_id,
                    'sub_total' => $subTotal,
                    'total' => $subTotal,
                    'shipping_cost' => $refundOrderShippingCost ?? 0,
                    'payment_method' => 'Cash on delivery',
                    'order_type' => 'order_refund',
                    'business_location_id' => $client->business_location_id,
                ]);



                $this->orderTrackingService->store($newRefundOrder, 'pending');

                // Add refund item to the new order
                OrderItem::create([
                    'order_id' => $newRefundOrder->id,
                    'product_id' => $orderItem->product_id,
                    'variation_id' => $orderItem->variation_id,
                    'quantity' => $refundAmount,
                    'price' => $orderItem->price,
                    'discount' => $orderItem->discount ?? 0,
                    'sub_total' => $subTotal,
                ]);

                \Log::info('newRefundOrder', [$newRefundOrder]);

            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Refund order item processed successfully.',
                'refund_order' => $existRefundOrder ?? $newRefundOrder,
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

    public function removeOrderRefundItem($request)
    {
        // Find the refund order
        $orderRefund = Order::where('id', $request->order_id)
            ->where('order_type', 'order_refund')
            ->first();

        if (!$orderRefund) {
            return $this->returnJSON(null, __('message.Order not found'), false, 400);
        }

        // Find the order item
        $orderItem = OrderItem::where('id', $request->order_item_id)
            ->where('order_id', $request->order_id)
            ->first();

        if (!$orderItem) {
            return $this->returnJSON(null, __('message.Order item not found'), false, 400);
        }

        // Delete the order item
        $orderItem->delete();

        // Retrieve remaining order items
        $remainingOrderItems = OrderItem::where('order_id', $orderRefund->id)->get();

        // Calculate the new total
        $itemPrice = 0; // Initialize before using

        foreach ($remainingOrderItems as $item) {
            $itemPrice += $item->price;
        }

        // Update refund order totals
        $orderRefund->sub_total = $itemPrice;
        $orderRefund->total = $itemPrice;
        $orderRefund->save();

        return new OrderResource($orderRefund);

        // return $this->returnJSON($orderRefund, __('message.Order refund Updated Successfully'));
    }



}
