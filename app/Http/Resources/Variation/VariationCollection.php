<?php

namespace App\Http\Resources\Variation;


use Illuminate\Http\Resources\Json\ResourceCollection;

class VariationCollection extends ResourceCollection
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
        // Wrap each item in the collection with VariationResource
        return $this->collection->map(function ($variation) use ($request) {
            // Pass the withFullData flag to the VariationResource
            return (new VariationResource($variation))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
