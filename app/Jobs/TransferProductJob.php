<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Client;
use App\Models\Transaction;
use App\Models\VariationLocationDetails;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class TransferProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $orderItem;
    protected $client;
    protected $fromLocationId;
    protected $toLocationId;
    protected $quantity;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)
    {
        $this->order = $order;
        $this->orderItem = $orderItem;
        $this->client = $client;
        $this->fromLocationId = $fromLocationId;
        $this->toLocationId = $toLocationId;
        $this->quantity = $quantity;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            $productUtil = new ProductUtil();
            $transactionUtil = new TransactionUtil();
    
           
            // Create sell and purchase transfer transactions
            $inputData = [
                'location_id' => $this->fromLocationId,
                'ref_no' => 'Transfer-' . uniqid(),
                'transaction_date' => now(),
                'final_total' => $this->order->total,
                'type' => 'sell_transfer',
                'business_id' => $this->client->contact->business_id ?? null,
                'created_by' => $this->client->id,
                'payment_status' => 'paid',
                'status' => 'final',
            ];
    
            $sellTransfer = Transaction::create($inputData);
    
            $inputData['type'] = 'purchase_transfer';
            $inputData['location_id'] = $this->toLocationId;
            $inputData['transfer_parent_id'] = $sellTransfer->id;
    
            $purchaseTransfer = Transaction::create($inputData);
    
            $products = [
                [
                    'product_id' => $this->orderItem->product_id,
                    'variation_id' => $this->orderItem->variation_id,
                    'quantity' => $this->quantity,
                    'unit_price' => $this->orderItem->price,
                    'unit_price_inc_tax' => $this->orderItem->price,
                    'enable_stock' => $this->orderItem->product->enable_stock,
                ],
            ];
    
            $transactionUtil->createOrUpdateSellLines($sellTransfer, $products, $this->fromLocationId);
            $purchaseTransfer->purchase_lines()->createMany($products);
    
       
            foreach ($products as $product) {

                // Log and perform quantity decrease at the 'from' location
                $productUtil->decreaseProductQuantity(
                    $product['product_id'],
                    $product['variation_id'],
                    $sellTransfer->location_id,
                    $product['quantity']
                );
            
            
                // Update quantity at the 'to' location
                $productUtil->updateProductQuantity(
                    $purchaseTransfer->location_id,
                    $product['product_id'],
                    $product['variation_id'],
                    $product['quantity']
                );
            
            
                 // Fetch current quantity available at the destination before final decrease
                 $oldQuantity = $productUtil->getProductQuantity(
                    $product['product_id'],
                    $product['variation_id'],
                    $purchaseTransfer->location_id
                );


                // Perform the final decrease at destination location based on quantity difference
                // $productUtil->decreaseProductQuantity(
                //     $product['product_id'],
                //     $product['variation_id'],
                //     $purchaseTransfer->location_id,
                //     $this->orderItem->quantity,
                // );
                

            }
            
            DB::commit();
        } catch (Exception $e) {
            dd($e);
            DB::rollBack();
            Log::error("Product transfer failed for Order ID {$this->order->id}: " . $e->getMessage());
            throw new Exception('Stock transfer failed: ' . $e->getMessage());
        }
    }


    
}
