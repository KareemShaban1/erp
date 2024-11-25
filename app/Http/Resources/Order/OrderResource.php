<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\OrderCancellation\OrderCancellationResource;
use App\Http\Resources\OrderItem\OrderItemCollection;
use App\Http\Resources\OrderRefund\OrderRefundCollection;
use App\Http\Resources\OrderTracking\OrderTrackingCollection;
use App\Http\Resources\OrderTracking\OrderTrackingResource;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_uuid' => $this->order_uuid,
            'number' => $this->number,
            $this->mergeWhen($this->withFullData, function () {
                return [
                    'client' => (new ClientResource($this->client))->withFullData(true),
                    'order_items'=>(new OrderItemCollection( $this->orderItems))->withFullData(true),
                    'order_tracking'=>(new OrderTrackingResource( $this->orderTracking))->withFullData(true),
                    'payment_method' => $this->payment_method,
                    'order_status' => ucfirst($this->order_status),
                    'order_type' => $this->order_type,
                    'payment_status' => ucfirst($this->payment_status),
                    'shipping_cost' => $this->shipping_cost,
                    'sub_total' => $this->sub_total,
                    'total_discount' => (string) $this->orderItems->sum('discount'),
                    'total' => $this->total,
                    'order_cancellation'=>(new OrderCancellationResource( $this->orderCancellation))->withFullData(true),
                    'order_refunds'=>(new OrderRefundCollection( $this->orderRefunds))->withFullData(true),
                    'assigned_delivery' => DeliveryOrder::where('order_id', $this->id)->exists(),
                    'created_at' => $this->created_at,
                ];
            }),
        ];


    }
}
