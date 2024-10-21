<?php

namespace App\Http\Resources\Cart;


use Illuminate\Http\Resources\Json\ResourceCollection;

class CartCollection extends ResourceCollection
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
        // Wrap each item in the collection with CartResource
        return $this->collection->map(function ($Cart) use ($request) {
            // Pass the withFullData flag to the CartResource
            return (new CartResource($Cart))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
