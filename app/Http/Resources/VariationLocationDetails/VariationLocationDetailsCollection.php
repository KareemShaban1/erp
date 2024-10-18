<?php

namespace App\Http\Resources\VariationLocationDetails;


use Illuminate\Http\Resources\Json\ResourceCollection;

class VariationLocationDetailsCollection extends ResourceCollection
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
        // Wrap each item in the collection with VariationLocationDetailsResource
        return $this->collection->map(function ($variation) use ($request) {
            // Pass the withFullData flag to the VariationLocationDetailsResource
            return (new VariationLocationDetailsResource($variation))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
