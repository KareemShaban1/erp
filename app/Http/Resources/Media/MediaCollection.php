<?php

namespace App\Http\Resources\Media;


use Illuminate\Http\Resources\Json\ResourceCollection;

class MediaCollection extends ResourceCollection
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
        // Wrap each item in the collection with MediaResource
        return $this->collection->map(function ($Media) use ($request) {
            // Pass the withFullData flag to the MediaResource
            return (new MediaResource($Media))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
