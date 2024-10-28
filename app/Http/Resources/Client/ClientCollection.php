<?php

namespace App\Http\Resources\Client;


use Illuminate\Http\Resources\Json\ResourceCollection;

class ClientCollection extends ResourceCollection
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
        // Wrap each item in the collection with ClientResource
        return $this->collection->map(function ($Client) use ($request) {
            // Pass the withFullData flag to the ClientResource
            return (new ClientResource($Client))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
