<?php

namespace App\Http\Resources\BusinessLocation;

use App\Http\Resources\Discount\DiscountCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessLocationResource extends JsonResource
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
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            $this->mergeWhen($this->withFullData, function () {
                return [
                // 'landmark' => $this->landmark,
                // 'country' => $this->country,
                // 'state' => $this->state,
                // 'city' => $this->city,
                // 'zip_code' => $this->zip_code,
                // 'mobile' => $this->mobile,
                // 'alternate_number' => $this->alternate_number,
                // 'email' => $this->email,
                // 'website' => $this->website,
                // 'discounts'=>(new DiscountCollection($this->discounts))->withFullData(true),
                ];
            }),
        ];


    }
}
