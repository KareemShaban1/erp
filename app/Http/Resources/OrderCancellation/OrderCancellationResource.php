<?php

namespace App\Http\Resources\OrderCancellation;

use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\OrderCancellationItem\OrderCancellationItemCollection;
use App\Http\Resources\OrderCancellationTracking\OrderCancellationTrackingCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderCancellationResource extends JsonResource
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
                    'client_name' => $this->client->contact->name,
                    'order_number' => $this->order->number,
                    'status' => $this->status,
                    'reason'=>$this->reason,
                    'admin_response' => $this->admin_response,
                    'requested_at' => $this->requested_at,
                    'processed_at' => $this->processed_at,
                    'created_at' => $this->created_at,
                ];
            }),
        ];


    }
}
