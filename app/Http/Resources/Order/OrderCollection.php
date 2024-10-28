<?php

namespace App\Http\Resources\Order;


use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
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
        // Wrap each item in the collection with OrderResource
        return $this->collection->map(function ($brand) use ($request) {
            // Pass the withFullData flag to the OrderResource
            return (new OrderResource($brand))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
