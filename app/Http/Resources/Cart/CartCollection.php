<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CartCollection extends ResourceCollection
{
    public $withFullData = false;
    public $totals = ['total_price' => 0, 'total_discount' => 0];
    public $locationMessage = null;

    public function withFullData($fullData)
    {
        $this->withFullData = $fullData;
        return $this;
    }

    public function setTotals($totalPrice, $totalDiscount,$totalAfterDiscount)
    {
        $this->totals = [
            'total_price' => $totalPrice, 
            'total_discount' => $totalDiscount,
            'total_after_discount' => $totalAfterDiscount
    ];
        return $this;
    }

    public function setLocationMessage($message)
    {
        $this->locationMessage = $message;
        return $this;
    }

    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map(function ($cart) use ($request) {
                return (new CartResource($cart))
                    ->withFullData($this->withFullData)
                    ->toArray($request);
            })->all(),
            'full_data' => $this->withFullData,
            'totals' => $this->totals,
            'location_message' => $this->locationMessage,
        ];
    }
}

