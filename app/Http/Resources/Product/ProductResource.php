<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\Brand\BrandResource;
use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\Media\MediaCollection;
use App\Http\Resources\Unit\UnitResource;
use App\Http\Resources\Variation\VariationCollection;
use App\Http\Resources\Variation\VariationResource;
use App\Http\Resources\VariationLocationDetails\VariationLocationDetailsCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    protected bool $withFullData = true;

    // protected bool $isVariation = true;

    /**
     * Set whether to return full data or not.
     * 
     * @param bool $withFullData
     * @return self
     */
    public function withFullData(bool $withFullData 
    // , bool $isVariation
    ): self
    {
        $this->withFullData = $withFullData;

        // $this->isVariation = $isVariation;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        // Basic data to always return
        $data = [
            'id' => $this->id,
            'name' => $this->name,
        ];
    
        // Conditionally merge the full data if the flag is set to true
        if ($this->withFullData) {
            $variations = $this->variations;
    
            $current_stock = $variations->sum(function ($variation) {
                return $variation->variation_location_details->sum('qty_available');
            });
    
            // If current_stock < 0, return an empty array
            if ($current_stock < 0) {
                return [];
            }
    
            // Otherwise, return full data
            $data = array_merge($data, [
                'description' => $this->product_description,
                'active_in_app'=>$this->active_in_app,
                'type' => $this->type,
                'business_id' => $this->business_id,
                'brand' => (new BrandResource($this->brand))->withFullData(false),
                'tax' => $this->product_tax->amount ?? null,
                'current_stock' => $current_stock,
                'image_url' => $this->image_url,
                'media' => (new MediaCollection($this->media))->withFullData(false),
                'variations' => (new VariationCollection($this->variations))->withFullData(true),
            ]);
        }
    
        return $data;
    }
    
}
