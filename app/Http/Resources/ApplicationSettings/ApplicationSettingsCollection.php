<?php

namespace App\Http\Resources\ApplicationSettings;


use Illuminate\Http\Resources\Json\ResourceCollection;

class ApplicationSettingsCollection extends ResourceCollection
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
        // Wrap each item in the collection with ApplicationSettingsResource
        return $this->collection->map(function ($ApplicationSettings) use ($request) {
            // Pass the withFullData flag to the ApplicationSettingsResource
            return (new ApplicationSettingsResource($ApplicationSettings))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
