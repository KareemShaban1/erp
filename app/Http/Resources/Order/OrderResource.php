<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\OrderCancellation\OrderCancellationResource;
use App\Http\Resources\OrderItem\OrderItemCollection;
use App\Http\Resources\OrderRefund\OrderRefundCollection;
use App\Http\Resources\OrderTracking\OrderTrackingResource;
use App\Models\ApplicationSettings;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderRefund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    protected bool $withFullData = true;

    public function withFullData(bool $withFullData): self
    {
        $clone = clone $this;
        $clone->withFullData = $withFullData;
        return $clone;
    }

    /**
     * @param $request The incoming HTTP request.
     * @return array<int|string, mixed> The transformed array representation of the order.
     */
    public function toArray($request)
    {
        // Fetch customer service settings in a single query
        $settings = ApplicationSettings::whereIn('key', ['customer_service_phone', 'customer_service_whatsapp'])
            ->pluck('value', 'key');

        $customer_service_phone = $settings['customer_service_phone'] ?? '';
        $customer_service_whatsapp = $settings['customer_service_whatsapp'] ?? '';

        // Fetch parent order and refunds (if any)
        $refunds = collect();
        $parentOrder = Order::where('id', $this->parent_order_id)
            ->where('order_type', 'order')
            ->first();

        if ($parentOrder) {
            $refunds = OrderRefund::whereIn('order_item_id', $parentOrder->orderItems->pluck('id'))->get();
        }

        // Base response array
        $response = [
            'id' => $this->id,
            'order_uuid' => $this->order_uuid,
            'number' => $this->number,
            'customer_service_phone' => $customer_service_phone,
            'customer_service_whatsapp' => $customer_service_whatsapp,
            'assigned_delivery' => DeliveryOrder::where('order_id', $this->id)->exists(),
            'created_at' => $this->created_at,
        ];

        // Add full data only if enabled
        if ($this->withFullData) {
            $response = array_merge($response, [
                'refund_items' => (new OrderRefundCollection($refunds))->withFullData(false),
                'client' => (new ClientResource($this->client))->withFullData(true),
                'order_items' => (new OrderItemCollection($this->orderItems))->withFullData(true),
                'order_tracking' => (new OrderTrackingResource($this->orderTracking))->withFullData(true),
                'payment_method' => $this->payment_method,
                'order_status' => ucfirst($this->order_status),
                'order_type' => $this->order_type,
                'payment_status' => ucfirst($this->payment_status),
                'shipping_cost' => $this->shipping_cost,
                'sub_total' => (string) $this->sub_total,
                'total_discount' => (string) $this->orderItems->sum('discount'),
                'total' => (string) $this->total,
                'order_cancellation' => (new OrderCancellationResource($this->orderCancellation))->withFullData(true),
                'order_refunds' => (new OrderRefundCollection($this->orderRefunds))->withFullData(true),
            ]);
        }

        return $response;
    }
}
