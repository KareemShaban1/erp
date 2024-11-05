<?php

namespace App\Http\Resources\OrderTracking;

use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\OrderTrackingItem\OrderTrackingItemCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTrackingResource extends JsonResource
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
        return [
            'id' => $this->id,
            $this->mergeWhen($this->withFullData, function () {
                return [
                  'order_id'=>$this->order_id,
                  'pending_at'=>$this->pending_at,
                  'processing_at'=>$this->processing_at,
                  'shipped_at'=>$this->shipped_at,
                  'completed_at'=>$this->completed_at,
                  'cancelled_at'=>$this->cancelled_at,
                ];
            }),
        ];


    }
}
