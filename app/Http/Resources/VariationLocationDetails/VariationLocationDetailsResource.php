<?php

namespace App\Http\Resources\VariationLocationDetails;

use App\Http\Resources\BusinessLocation\BusinessLocationCollection;
use App\Http\Resources\BusinessLocation\BusinessLocationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariationLocationDetailsResource extends JsonResource
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

        // 'product_id','product_variation_id','variation_id','location_id','qty_available'
        return [
            'id' => $this->id,
            'qty_available' => $this->qty_available,
            'location' =>  (new BusinessLocationResource($this->location))->withFullData(true),
        ];


    }
}
