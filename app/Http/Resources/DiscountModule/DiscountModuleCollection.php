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
        $today = now(); // Get the current date and time

        // Filter discounts based on the end date
        $filteredCollection = $this->collection->filter(function ($Discount) use ($today) {
            return is_null($Discount->ends_at) || $Discount->ends_at > $today;
        });

        // Wrap each item in the filtered collection with DiscountResource
        return $filteredCollection->map(function ($Discount) use ($request) {
            return (new DiscountModuleResource($Discount))
                ->withFullData($this->withFullData)
                ->toArray($request);
        })->all();
    }
}
