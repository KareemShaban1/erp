<?php

namespace App\Http\Resources\Discount;

use App\Http\Resources\Variation\VariationCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    protected bool $withFullData = true;

    public function withFullData(bool $withFullData): self
    {
        $this->withFullData = $withFullData;

        return $this;
    }

    /**
     * Calculate discounted prices for each associated variation.
     *
     * @return array
     */
    protected function calculateDiscountedPrices(): array
    {
        return $this->variations->map(function ($variation) {
            $basePrice = $variation->default_sell_price;

            if ($this->discount_type === 'percentage') {
                $discountedPrice = $basePrice - ($basePrice * ($this->discount_amount / 100));
            } elseif ($this->discount_type === 'fixed') {
                $discountedPrice = $basePrice - $this->discount_amount;
            } else {
                $discountedPrice = $basePrice;
            }

            return [
                'variation_id' => $variation->id,
                'original_price' => $basePrice,
                'discount_amount' => $this->discount_amount,
                'price_after_discount' => max($discountedPrice, 0),
            ];
        })->toArray();
    }

    /**
     * @param $request The incoming HTTP request.
     * @return array<int|string, mixed> The transformed array representation of the Discount collection.
     */
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'discounted_prices' => $this->calculateDiscountedPrices(),
            $this->mergeWhen($this->withFullData, function () {
                return [
                    'discount_type' => $this->discount_type,
                    'starts_at' => $this->starts_at,
                    'ends_at' => $this->ends_at,
                    'is_active' => $this->is_active,
                    // 'variations'=>$this->variations,
                    // 'variations'=>(new VariationCollection( $this->variations))
                    // ->withFullData(true,false,true),
                    // Additional fields if needed
                ];
            }),
        ];
    }
}
