<?php

namespace App\Services\API;

use App\Models\Transaction;
use App\Services\BaseService;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;

class CancellationTransferQuantityService extends BaseService {


          protected $productUtil;
          protected $moduleUtil;
          protected $transactionUtil;
          protected $contactUtil;
          protected $cartService;
          protected $orderTrackingService;
         
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
     * Transfers a specified quantity from one location to another.
     */
    public function transferQuantityForCancellation($order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)
    {
        try {
            DB::beginTransaction();

           

            $business_id = $client->contact->business_id;

            \Log::info('data',[
                $fromLocationId , $toLocationId,$quantity
            ]);

            $inputData = [
                'location_id' => $fromLocationId,
                'order_id' => $order->parent_order_id,
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

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
            throw new \Exception('Stock transfer failed: ' . $e->getMessage());
        }
    }
}