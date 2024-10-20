<?php

namespace App\Http\Resources\VariationValue;


use Illuminate\Http\Resources\Json\ResourceCollection;

class VariationValueCollection extends ResourceCollection
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
        // Wrap each item in the collection with VariationValueResource
        return $this->collection->map(function ($VariationValue) use ($request) {
            // Pass the withFullData flag to the VariationValueResource
            return (new VariationValueResource($VariationValue))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
