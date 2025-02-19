<?php

namespace App\Http\Resources\OrderRefund;

use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\OrderItem\OrderItemResource;
use App\Http\Resources\OrderRefundItem\OrderRefundItemCollection;
use App\Http\Resources\OrderRefundTracking\OrderRefundTrackingCollection;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Variation\VariationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRefundResource extends JsonResource
{
    protected bool $withFullData = true;

    public function withFullData(bool $withFullData): self
    {
        $this->withFullData = $withFullData;

        return $this;
    }
    /**
     * @param $request The incoming HTTP request.
     * @return array<int|string, mixed>  The transformed array representation of the LaDivision collection.
     */
    public function toArray($request)
    {
        // 'order_id','client_id','product_id','variation_id','amount','status','reason','admin_response','requested_at','processed_at'
        return [
            'id' => $this->id,
            'reason'=>$this->reason,
            'admin_response' => $this->admin_response,
            'status' => $this->status,
            'amount' => $this->amount,
            'product_id'=>$this->order_item->product->id,
            'product_name'=>$this->order_item->product->name,
            'variation_id'=>$this->order_item->variation->id,
            'variation_name'=>$this->order_item->variation->name,
            $this->mergeWhen($this->withFullData, function () {
                return [
                    'order_id' => $this->order_id,
                    'client_name' => $this->client->contact->name,
                    'order_number' => $this->order->number,
                    'order_item'=> new OrderItemResource($this->order_item),
                    'requested_at' => $this->requested_at,
                    'processed_at' => $this->processed_at,
                    'created_at' => $this->created_at,
                ];
            }),
        ];


    }
}
