<?php

namespace App\Http\Resources\Category;


use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
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
         // Wrap each item in the collection with categoryResource
         return $this->collection->map(function ($category) use ($request) {
            // Pass the withFullData flag to the categoryResource
            return (new CategoryResource($category))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
