<?php

namespace App\Http\Resources\BusinessLocation;


use Illuminate\Http\Resources\Json\ResourceCollection;

class BusinessLocationCollection extends ResourceCollection
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
         // Wrap each item in the collection with BusinessLocation Resource
         return $this->collection->map(function ($businessLocation) use ($request) {
            // Pass the withFullData flag to the BusinessLocation Resource
            return (new BusinessLocationResource($businessLocation))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
