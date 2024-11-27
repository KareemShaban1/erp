<?php

namespace App\Http\Resources\DiscountModule;

use Illuminate\Http\Resources\Json\ResourceCollection;

class VariationModuleCollection extends ResourceCollection
{
    private bool $withFullData = true;


    // Pass the isDiscount flag here
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
        // Wrap each item in the collection with VariationResource
        return $this->collection->map(function ($variation) use ($request) {
            return (new VariationModuleResource($variation))
                ->withFullData($this->withFullData) // Pass the isDiscount flag
                ->toArray($request);
        })->all();
    }
}
