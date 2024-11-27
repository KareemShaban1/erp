<?php

namespace App\Http\Resources\DiscountModule;

use App\Http\Resources\Discount\DiscountCollection;
use App\Http\Resources\Media\MediaCollection;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\ProductVariation\ProductVariationResource;
use App\Http\Resources\VariationLocationDetails\VariationLocationDetailsCollection;
use App\Http\Resources\VariationValue\VariationValueResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariationModuleResource extends JsonResource
{
    protected bool $withFullData = true;

    // Add the setter for the isDiscount flag
    public function withFullData(bool $withFullData): self
    {
        $this->withFullData = $withFullData;
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
                    'total_qty_available' => intval($this->total_qty_available),
                    'default_sell_price' => $this->default_sell_price,
                    'sell_price_inc_tax' => $this->sell_price_inc_tax,
                    'variation_template' => (new ProductVariationResource($this->product_variation))->withFullData(false),
                    'variation_template_value' => (new VariationValueResource($this->variation_value))->withFullData(false),
                    'media' => (new MediaCollection($this->media))->withFullData(false),
                    'locations' => (new VariationLocationDetailsCollection($this->variation_location_details))->withFullData(true),
                    'product' => (new ProductModuleResource($this->product))->withFullData(true),

                    // Conditionally include discounts
                    // 'discounts' => $this->mergeWhen($this->isDiscount, function () {
                    //     return (new DiscountCollection($this->discounts))->withFullData(true);
                    // }),
                    // 'product' => (new ProductResource($this->product))->withFullData(true),
                    // 'product' => $this->mergeWhen($this->isProduct, function () {
                    //     return (new ProductResource($this->product))->withFullData(true,false);
                    // }),
                ];
            }),
        ];
    }
}
