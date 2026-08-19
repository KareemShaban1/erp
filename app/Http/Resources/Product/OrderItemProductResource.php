<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\Brand\BrandResource;
use App\Http\Resources\Media\MediaCollection;
use App\Http\Resources\Variation\VariationCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemProductResource extends JsonResource
{
    protected bool $withFullData = true;

    public function withFullData(bool $withFullData): self
    {
        $this->withFullData = $withFullData;
        return $this;
    }

    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        if ($this->withFullData) {
            $variations = $this->variations->map(function ($variation) {
                $variation->variation_location_details = $variation->variation_location_details->filter(function ($details) {
                    return $details->location->is_active == 1;
                });
                return $variation;
            });

            $variations = $variations->sortByDesc(function ($variation) {
                return $variation->variation_location_details->sum('qty_available');
            });

            $current_stock = $variations->sum(function ($variation) {
                return $variation->variation_location_details->sum('qty_available');
            });

            $data = array_merge($data, [
                'description' => $this->product_description,
                'active_in_app' => $this->active_in_app,
                'type' => $this->type,
                'business_id' => $this->business_id,
                'brand' => (new BrandResource($this->brand))->withFullData(false),
                'tax' => $this->product_tax->amount ?? null,
                'current_stock' => $current_stock,
                'image_url' => $this->image_url,
                'media' => (new MediaCollection($this->media))->withFullData(false),
                'variations' => (new VariationCollection($variations))->withFullData(true),
            ]);
        }

        return $data;
    }
}
