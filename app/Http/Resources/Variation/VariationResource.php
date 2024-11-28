<?php

namespace App\Http\Resources\Variation;

use App\Http\Resources\Discount\DiscountCollection;
use App\Http\Resources\Media\MediaCollection;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\ProductVariation\ProductVariationResource;
use App\Http\Resources\VariationLocationDetails\VariationLocationDetailsCollection;
use App\Http\Resources\VariationValue\VariationValueResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariationResource extends JsonResource
{
    protected bool $withFullData = true;
    protected bool $isDiscount = true;
    protected bool $isProduct = true;


    // Add the setter for the isDiscount flag
    public function withFullData(bool $withFullData, bool $isDiscount = true, bool $isProduct = true): self
    {
        $this->withFullData = $withFullData;
        $this->isDiscount = $isDiscount;
        $this->isProduct = $isProduct;

        return $this;
    }

    /**
     * @param $request The incoming HTTP request.
     * @return array<int|string, mixed>  The transformed array representation of the Variation resource.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            $this->mergeWhen($this->withFullData, function () {
                return [
                    'discounts'=> (new DiscountCollection($this->discounts))->withFullData(true),
                    'total_qty_available' => intval($this->total_qty_available),
                    'default_sell_price' => $this->default_sell_price,
                    'sell_price_inc_tax' => $this->sell_price_inc_tax,
                    'variation_template' => (new ProductVariationResource($this->product_variation))->withFullData(false),
                    'variation_template_value' => (new VariationValueResource($this->variation_value))->withFullData(false),
                    'media' => (new MediaCollection($this->media))->withFullData(false),
                    'locations' => (new VariationLocationDetailsCollection($this->variation_location_details))->withFullData(true),
                ];
            }),
        ];
    }
}
