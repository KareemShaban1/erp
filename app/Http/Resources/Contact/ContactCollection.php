<?php

namespace App\Http\Resources\Contact;


use Illuminate\Http\Resources\Json\ResourceCollection;

class ContactCollection extends ResourceCollection
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
        // Wrap each item in the collection with ContactResource
        return $this->collection->map(function ($Contact) use ($request) {
            // Pass the withFullData flag to the ContactResource
            return (new ContactResource($Contact))->withFullData($this->withFullData)->toArray($request);
        })->all();
    }
}
