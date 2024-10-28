<?php

namespace App\Http\Resources\Banner;


use Illuminate\Http\Resources\Json\ResourceCollection;

class BannerCollection extends ResourceCollection
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
        // Wrap each item in the collection with BannerResource
        return $this->collection->map(function ($Banner) use ($request) {
            // Pass the withFullData flag to the BannerResource
            return (new BannerResource($Banner))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
