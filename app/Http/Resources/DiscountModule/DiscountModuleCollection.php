<?php

namespace App\Http\Resources\DiscountModule;


use Illuminate\Http\Resources\Json\ResourceCollection;

class DiscountModuleCollection extends ResourceCollection
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
        // Wrap each item in the collection with DiscountModuleResource
        return $this->collection->map(function ($DiscountModule) use ($request) {
            // Pass the withFullData flag to the DiscountModuleResource
            return (new DiscountModuleResource($DiscountModule))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
