<?php

namespace App\Http\Resources\Variation;

use App\Http\Resources\Media\MediaCollection;
use App\Http\Resources\ProductVariation\ProductVariationResource;
use App\Http\Resources\VariationLocationDetails\VariationLocationDetailsCollection;
use App\Http\Resources\VariationValue\VariationValueResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariationResource extends JsonResource
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
            'name' => $this->name,
            $this->mergeWhen($this->withFullData, function () {
                return [
                'default_purchase_price' => $this->default_purchase_price,
                'dpp_inc_tax' => $this->dpp_inc_tax,
                'profit_percent' => $this->profit_percent,
                'default_sell_price' => $this->default_sell_price,
                'sell_price_inc_tax' => $this->sell_price_inc_tax,
                'combo_variations' => $this->combo_variations,
                'variation_template'=>(new ProductVariationResource($this->product_variation))->withFullData(false),
                'variation_template_value' => (new VariationValueResource($this->variation_value))->withFullData(false),
                'media' => (new MediaCollection($this->media))->withFullData(false),
                'locations'=>(new VariationLocationDetailsCollection($this->variation_location_details))->withFullData(true),
                ];
            }),
            // 'created_at' => $this->created_at,
            // 'deleted_at' => $this->deleted_at,
        ];


    }
}
