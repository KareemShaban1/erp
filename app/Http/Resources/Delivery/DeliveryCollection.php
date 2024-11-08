<?php

namespace App\Http\Resources\Delivery;


use Illuminate\Http\Resources\Json\ResourceCollection;

class DeliveryCollection extends ResourceCollection
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
        // Wrap each item in the collection with DeliveryResource
        return $this->collection->map(function ($Delivery) use ($request) {
            // Pass the withFullData flag to the DeliveryResource
            return (new DeliveryResource($Delivery))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
