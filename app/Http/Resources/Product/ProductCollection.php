<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    private bool $withFullData = true;
    // private bool $isVariation = true;

    /**
     * Set whether to return full data or not.
     *
     * @param bool $withFullData
     * @return self
     */
    public function withFullData(bool $withFullData
    // , bool $isVariation = true
    ): self
    {
        $this->withFullData = $withFullData;
        // $this->isVariation = $isVariation;

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
        // Wrap each item in the collection with ProductResource
        return $this->collection->map(function ($product) use ($request) {
            // Pass the withFullData flag to the ProductResource
            return (new ProductResource($product))->withFullData($this->withFullData
            // ,$this->isVariation
            )->toArray($request);
        })->all();
    }
}
