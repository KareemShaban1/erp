<?php

namespace App\Http\Resources\ProductVariation;


use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductVariationCollection extends ResourceCollection
{
    private bool $withFullData = true;

    public function withFullData($withFullData): self
    {
        $this->withFullData = $withFullData;
        return $this;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param mixed $request
     * @return array
     */
    public function toArray($request): array
    {
        // Wrap each item in the collection with ProductVariationResource
        return $this->collection->map(function ($variation) use ($request) {
            // Pass the withFullData flag to the ProductVariationResource
            return (new ProductVariationResource($variation))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
