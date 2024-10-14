<?php

namespace App\Http\Resources\Unit;


use Illuminate\Http\Resources\Json\ResourceCollection;

class UnitCollection extends ResourceCollection
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
        return $this->collection
            // ->map->withFullData($this->withFullData)
            ->map->toArray($request)
            ->all();
    }
}
