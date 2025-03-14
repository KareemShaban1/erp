<?php

namespace App\Services\ApplicationDashboard;

use App\Http\Resources\OrderCancellation\OrderCancellationResource;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderTracking;
use App\Models\Transaction;
use App\Models\TransactionSellLine;
use App\Notifications\OrderCancellationCreatedNotification;
use App\Services\API\CancellationTransferQuantityService;
use App\Services\API\OrderService;
use App\Services\BaseService;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class OrderCancellationService extends BaseService
{
          /**
           * All Utils instance.
           *
           */
          protected $moduleUtil;
          protected $orderService;
          protected $productUtil;
          protected $transactionUtil;
          protected $transferQuantityService;

          public function __construct(
                    ModuleUtil $moduleUtil,
                    ProductUtil $productUtil,
                    OrderService $orderService,
                    CancellationTransferQuantityService $transferQuantityService,
                    TransactionUtil $transactionUtil

          ) {
                    $this->moduleUtil = $moduleUtil;
                    $this->orderService = $orderService;
                    $this->productUtil = $productUtil;
                    $this->transactionUtil = $transactionUtil;
                    $this->transferQuantityService = $transferQuantityService;
          }
          // public function makeOrderCancellation($data)
          // {
          //           $data['client_id'] = Auth::id();
          //           $data['status'] = 'approved';
          //           $data['requested_at'] = now();

          //           \Log::info('data',[$data]);

          //           try {
          //                     // Find the order by ID and ensure it exists
          //                     $order = Order::find($data['order_id']);
          //                     $orderTracking = OrderTracking::where('order_id', $data['order_id'])->first();
          //                     if (!$order) {
          //                               return $this->returnJSON(null, __('message.Order not found'), 404);
          //                     }

          //                     $order_transfer = Order::where('parent_order_id', $order->id)
          //                               ->where('order_type', 'order_transfer')
          //                               ->whereIn('order_status', ['pending', 'processing'])
          //                               ->get();

          //                     // Check if the order status allows cancellation
          //                     if (in_array($order->order_status, ['pending' , 'processing'])) {
          //                               // Set order status to 'cancelled' and save
          //                               $order->order_status = 'cancelled';
          //                               $order->payment_status = 'failed';
          //                               $orderTracking->cancelled_at = now();


          //                               // decrease quantity of order items from location
          //                               foreach ($order->orderItems as $item) {
          //                                         $this->productUtil->updateProductQuantity(
          //                                                   $order->business_location_id,
          //                                                   $item->product_id,
          //                                                   $item->variation_id,
          //                                                   $item->quantity
          //                                         );
          //                               }

          //                               // if there is transfer from location to location based on
          //                               // this order re transfer it again
          //                               if ($order_transfer) {
          //                                         foreach ($order_transfer as $transfer) {
          //                                                   foreach ($transfer->orderItems as $item) {
          //                                                             $this->transferQuantityService->transferQuantityForCancellation(
          //                                                                       $transfer,
          //                                                                       $item,
          //                                                                       $transfer->client,
          //                                                                       $transfer->to_business_location_id,
          //                                                                       $transfer->from_business_location_id,
          //                                                                       $item->quantity
          //                                                             );
          //                                                   }
          //                                                   $transfer->order_status = 'cancelled';
          //                                                   $transfer->save();
          //                                         }
          //                               }

          //                               $business_id = $order->client->contact->business->id;

          //                               $parent_sell_transaction = Transaction::
          //                                         where('order_id', $order->id)
          //                                         ->where('type', 'sell')
          //                                         ->first();
          //                               \Log::info('parent_sell_transaction', [$parent_sell_transaction]);
          //                               $products = [];
          //                               foreach ($order->orderItems as $item) {
          //                                         $transaction_sell_line = TransactionSellLine::
          //                                                   where('product_id', $item->product_id)
          //                                                   ->where('transaction_id', $parent_sell_transaction->id)
          //                                                   ->first();
          //                                         \Log::info('transaction_sell_line', [$transaction_sell_line]);

          //                                         $products[] = [
          //                                                   'sell_line_id' => $transaction_sell_line->id, // Adjust this field name to match your schema
          //                                                   'quantity' => $item->quantity,
          //                                                   'unit_price_inc_tax' => $item->price, // Include price if applicable
          //                                         ];

          //                                         $transferOrder = Order::where('id', $item->order_id)
          //                                                   ->first();
          //                                         $input = [
          //                                                   'transaction_id' => $parent_sell_transaction->id,
          //                                                   'order_id' => $transferOrder->id,
          //                                                   // 'invoice_no' => null,
          //                                                   // 'transaction_date' => Carbon::now(),
          //                                                   'products' => $products,
          //                                                   "discount_type" => null,
          //                                                   "discount_amount" => $item->discount,
          //                                                   "tax_id" => null,
          //                                                   "tax_amount" => "0",
          //                                                   "tax_percent" => "0",
          //                                         ];


          //                                         // add sell return for this cancelled order
          //                                         $this->transactionUtil->addSellReturnForCancellation($input, $business_id, 1);
          //                               }

          //                               $order->save();
          //                               $orderTracking->save();
          //                     } else {
          //                               // Return a response indicating the status cannot be changed
          //                               return $this->returnJSON(null, __('message.Order status is :status, it can\'t be changed', ['status' => $order->order_status]));
          //                     }

          //                     // Create the OrderCancellation record
          //                     $orderCancellation = OrderCancellation::create($data);


          //                     // Notify admins and users about the order
          //                     $admins = $this->moduleUtil->get_admins($order->client->contact->business_id);
          //                     $users = $this->moduleUtil->getBusinessUsers($order->client->contact->business_id, $order);

          //                     Notification::send($admins, new OrderCancellationCreatedNotification($order));
          //                     Notification::send($users, new OrderCancellationCreatedNotification($order));


          //                     // Return the created OrderCancellation as a resource
          //                     return new OrderCancellationResource($orderCancellation);

          //           } catch (\Exception $e) {
          //                     \Log::error('Error in makeOrderCancellation', [
          //                               'error' => $e->getMessage(),
          //                               'stack_trace' => $e->getTraceAsString(),
          //                               'data' => $data
          //                     ]);

          //                     return $this->handleException($e, __('message.Error occurred while storing OrderCancellation'));
          //           }

          // }


          public function makeOrderCancellation($data)
          {
                    $data['client_id'] = Auth::id();
                    $data['status'] = 'approved';
                    $data['requested_at'] = now();

                    \Log::info('data', [$data]);

                    try {
                              // Find the order by ID and ensure it exists
                              $order = Order::find($data['order_id']);
                              $orderTracking = OrderTracking::where('order_id', $data['order_id'])->first();
                              if (!$order) {
                                        return $this->returnJSON(null, __('message.Order not found'), 404);
                              }

                              $order_transfer = Order::where('parent_order_id', $order->id)
                                        ->where('order_type', 'order_transfer')
                                        ->whereIn('order_status', ['pending', 'processing'])
                                        ->get();

                              // Check if the order status allows cancellation
                              if (in_array($order->order_status, ['pending', 'processing'])) {
                                        // Set order status to 'cancelled' and save
                                        $order->order_status = 'cancelled';
                                        $order->payment_status = 'failed';
                                        $orderTracking->cancelled_at = now();

                                        // Decrease quantity of order items from location
                                        foreach ($order->orderItems as $item) {
                                                  $this->productUtil->updateProductQuantity(
                                                            $order->business_location_id,
                                                            $item->product_id,
                                                            $item->variation_id,
                                                            $item->quantity
                                                  );
                                        }

                                        // Handle transfer orders
                                        if ($order_transfer) {
                                                  foreach ($order_transfer as $transfer) {
                                                            foreach ($transfer->orderItems as $item) {
                                                                      $this->transferQuantityService->transferQuantityForCancellation(
                                                                                $transfer,
                                                                                $item,
                                                                                $transfer->client,
                                                                                $transfer->to_business_location_id,
                                                                                $transfer->from_business_location_id,
                                                                                $item->quantity
                                                                      );
                                                            }
                                                            $transfer->order_status = 'cancelled';
                                                            $transfer->save();
                                                  }
                                        }

                                        $business_id = $order->client->contact->business->id;
                                        $parent_sell_transaction = Transaction::where('order_id', $order->id)
                                                  ->where('type', 'sell')
                                                  ->first();
                                        \Log::info('parent_sell_transaction', [$parent_sell_transaction]);

                                        // Collect products once and process later
                                        $products = [];
                                        foreach ($order->orderItems as $item) {
                                                  $transaction_sell_line = TransactionSellLine::where('product_id', $item->product_id)
                                                            ->where('transaction_id', $parent_sell_transaction->id)
                                                            ->first();

                                                  \Log::info('transaction_sell_line', [$transaction_sell_line]);

                                                  $products[] = [
                                                            'sell_line_id' => $transaction_sell_line->id, // Adjust this field name to match your schema
                                                            'quantity' => $item->quantity,
                                                            'unit_price_inc_tax' => $item->price, // Include price if applicable
                                                  ];
                                        }

                                        // Now call addSellReturnForCancellation once, passing all products
                                        if (!empty($products)) {
                                                  $input = [
                                                            'transaction_id' => $parent_sell_transaction->id,
                                                            'order_id' => $order->id,
                                                            'products' => $products,
                                                            "discount_type" => null,
                                                            "discount_amount" => $order->orderItems->sum('discount'), // Sum discount for all items
                                                            "tax_id" => null,
                                                            "tax_amount" => "0",
                                                            "tax_percent" => "0",
                                                  ];

                                                  $this->transactionUtil->addSellReturnForCancellation($input, $business_id, 1);
                                        }

                                        $order->save();
                                        $orderTracking->save();
                              } else {
                                        return $this->returnJSON(null, __('message.Order status is :status, it can\'t be changed', ['status' => $order->order_status]));
                              }

                              // Create the OrderCancellation record
                              $orderCancellation = OrderCancellation::create($data);

                              // Notify admins and users about the order
                              $admins = $this->moduleUtil->get_admins($order->client->contact->business_id);
                              $users = $this->moduleUtil->getBusinessUsers($order->client->contact->business_id, $order);

                              Notification::send($admins, new OrderCancellationCreatedNotification($order));
                              Notification::send($users, new OrderCancellationCreatedNotification($order));

                              return new OrderCancellationResource($orderCancellation);
                    } catch (\Exception $e) {
                              \Log::error('Error in makeOrderCancellation', [
                                        'error' => $e->getMessage(),
                                        'stack_trace' => $e->getTraceAsString(),
                                        'data' => $data
                              ]);

                              return $this->handleException($e, __('message.Error occurred while storing OrderCancellation'));
                    }
          }

}