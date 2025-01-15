<?php

namespace App\Services\API;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Variation;
use App\Notifications\OrderTransferCreatedNotification;
use App\Services\BaseService;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuantityTransferService extends BaseService
{
          protected $productUtil;
          protected $transactionUtil;
          protected $contactUtil;

          protected $moduleUtil;


          public function __construct(
                    ProductUtil $productUtil,
                    TransactionUtil $transactionUtil,
                    ContactUtil $contactUtil,
                    ModuleUtil $moduleUtil
          ) {
                    $this->contactUtil = $contactUtil;
                    $this->transactionUtil = $transactionUtil;
                    $this->productUtil = $productUtil;
                    $this->moduleUtil = $moduleUtil;
          }

          public function handleQuantityTransfer($cart, $client, $order, $orderItem)
          {
                    $requiredQuantity = $cart->quantity;
                    $clientLocationId = $client->business_location_id;

                    // Step 1: Check if the required quantity exists in the client's location
                    $clientLocationDetail = $cart->variation->variation_location_details
                              ->firstWhere('location.id', $clientLocationId);

                    // get available quantity in client location
                    $availableAtClientLocation = $clientLocationDetail ? $clientLocationDetail->qty_available : 0;

                    if ($availableAtClientLocation >= $requiredQuantity) {
                              // If sufficient stock exists, update stock directly
                              $this->updateStock($orderItem, $clientLocationId, $requiredQuantity);
                    } else {
                              // Step 2: Calculate deficit and transfer stock if necessary
                              $deficit = $requiredQuantity - $availableAtClientLocation;

                              foreach ($cart->variation->variation_location_details as $locationDetail) {
                                        // if location not same location of client
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
                                                            // 2
                                                            $deficit -= $transferQty;

                                                            // Break if the deficit is covered
                                                            if ($deficit <= 0)
                                                                      break;
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
          public function transferQuantity($order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)
          {
                    try {
                              DB::beginTransaction();

                              $variation = Variation::
                              where('id', $orderItem->variation_id)
                              ->where('product_id', $orderItem->product_id);
                              $total = $quantity * $variation->default_purchase_price;
                              $business_id = $client->contact->business_id;
                           
                              $inputData = [
                                        'location_id' => $fromLocationId,
                                        'order_id' => $order->id,
                                        'transaction_date' => now(),
                                        'final_total' => $total,
                                        'type' => 'sell_transfer',
                                        'business_id' => $business_id,
                                        'created_by' => 1,
                                        'shipping_charges' => $this->productUtil->num_uf($order->shipping_cost),
                                        'payment_status' => 'paid',
                                        'status' => 'in_transit',
                                        'total_before_tax' => $total,
                                        'transfer_type' => 'application_transfer'
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
                                                  'unit_price' => $variation->default_purchase_price,
                                                  'unit_price_inc_tax' => $variation->default_purchase_price,
                                                  'unite_price_before_discount'=>$variation->default_purchase_price,
                                                  'enable_stock' => $orderItem->product->enable_stock,
                                                  'item_tax' => 0,
                                                  'tax_id' => null,
                                                  'pp_without_discount' => $variation->default_purchase_price,
                                                  'purchase_price' => $variation->default_purchase_price,
                                                  'purchase_price_inc_tax' => $variation->default_purchase_price,

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

                              $this->storeTransferOrder($order, $orderItem, $quantity, $fromLocationId, $toLocationId);

                              DB::commit();

                    } catch (\Exception $e) {
                              DB::rollBack();
                              Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
                              throw new \Exception('Stock transfer failed: ' . $e->getMessage());
                    }
          }


          public function storeTransferOrder($order, $orderItem, $quantity, $fromLocationId, $toLocationId)
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

                              // Check if a transfer order already exists for the parent order
                              $transferOrder = Order::where('parent_order_id', $order->id)
                                        ->where('order_type', 'order_transfer')
                                        ->where('from_business_location_id', $fromLocationId)
                                        ->where('to_business_location_id', $toLocationId)
                                        ->first();

                              if ($transferOrder) {
                                        // Update existing transfer order
                                        $this->addTransferItemToOrder($transferOrder, $orderItem, $quantity, $subTotal);
                                        $this->updateTransferOrderTotal($transferOrder, $orderItem, $quantity);
                              } else {
                                        // Create a new transfer order
                                        $transferOrder = $this->createTransferOrder($order, $subTotal, $fromLocationId, $toLocationId);
                                        $this->addTransferItemToOrder($transferOrder, $orderItem, $quantity, $subTotal);
                              }

                              DB::commit();

                              // Notify admins and users about the order
                              $admins = $this->moduleUtil->get_admins($transferOrder->client->contact->business_id);
                              $users = $this->moduleUtil->getBusinessUsers($transferOrder->client->contact->business_id, $transferOrder);

                              \Notification::send($admins, new OrderTransferCreatedNotification($transferOrder));
                              \Notification::send($users, new OrderTransferCreatedNotification($transferOrder));

                              return [
                                        'success' => true,
                                        'message' => 'Transfer order item processed successfully.',
                                        'transfer_order' => $transferOrder,
                              ];
                    } catch (\Exception $e) {
                              DB::rollBack();

                              Log::error("Transfer Order Error: {$e->getMessage()}");

                              return [
                                        'success' => false,
                                        'message' => 'Failed to process transfer order item. Please try again.',
                              ];
                    }
          }


          private function createTransferOrder($order, $subTotal, $fromLocationId, $toLocationId)
          {

                    // Fetch client details
                    $client = Client::findOrFail($order->client_id);

                    // Create a new transfer order
                    return Order::create([
                              'parent_order_id' => $order->id,
                              'client_id' => $order->client_id,
                              'sub_total' => $subTotal,
                              'total' => $subTotal, // Adjustments for taxes or other calculations can be added here
                              'payment_method' => 'Cash on delivery', // Modify as needed
                              'order_type' => 'order_transfer', // Adjust order type to reflect the transfer
                              'business_location_id' => $client->business_location_id,
                              'from_business_location_id' => $fromLocationId,
                              'to_business_location_id' => $toLocationId
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

          private function updateTransferOrderTotal($transferOrder, $orderItem, $quantity)
          {
                    $transferOrder->sub_total += ($quantity * $orderItem->price);
                    $transferOrder->total += ($quantity * $orderItem->price);
                    $transferOrder->save();
          }


          /**
           * Update stock directly without a transfer (e.g., from the client's location).
           */
          protected function updateStock($orderItem, $locationId, $quantity)
          {
                    $this->productUtil->decreaseProductQuantity(
                              $orderItem->product_id,
                              $orderItem->variation_id,
                              $locationId,
                              $quantity
                    );
          }

}