<?php

namespace App\Http\Resources\Variation;

use Illuminate\Http\Resources\Json\ResourceCollection;

class VariationCollection extends ResourceCollection
{
    private bool $withFullData = true;
    private bool $isDiscount = true;
    private bool $isProduct = true;

    // Pass the isDiscount flag here
    public function withFullData($withFullData, $isDiscount = true, $isProduct = true): self
    {
        $this->withFullData = $withFullData;
        $this->isDiscount = $isDiscount;
        $this->isProduct = $isProduct;

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
        // Wrap each item in the collection with VariationResource
        return $this->collection->map(function ($variation) use ($request) {
            return (new VariationResource($variation))
                ->withFullData($this->withFullData, $this->isDiscount,$this->isProduct) // Pass the isDiscount flag
                ->toArray($request);
        })->all();
    }
}
