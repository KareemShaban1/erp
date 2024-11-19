<?php

namespace App\Http\Resources\Notification;


use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationCollection extends ResourceCollection
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
        // Wrap each item in the collection with NotificationResource
        return $this->collection->map(function ($Notification) use ($request) {
            // Pass the withFullData flag to the NotificationResource
            return (new NotificationResource($Notification))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
