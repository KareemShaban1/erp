<?php

namespace App\Http\Resources\Client;

use App\Http\Resources\BusinessLocation\BusinessLocationResource;
use App\Http\Resources\Contact\ContactResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    protected bool $withFullData = true;

    public function withFullData(bool $withFullData): self
    {
        $this->withFullData = $withFullData;

        return $this;
    }
    /**
     * @param $request The incoming HTTP request.
     * @return array<int|string, mixed>  The transformed array representation of the LaDivision collection.
     */

    //  'contact_id','business_location_id','email_address','password','location','client_type'
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email_address' => $this->email_address,
            $this->mergeWhen($this->withFullData, function () {
                return [
                    'location' => $this->location,
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                    'client_type' => $this->client_type,
                    'contact' => (new ContactResource($this->contact))->withFullData(true),
                    'business_location' => (new BusinessLocationResource($this->business_location))->withFullData(true),
                    'created_at' => $this->created_at,
                    'deleted_at' => $this->deleted_at,
                ];
            }),
        ];


    }
}
