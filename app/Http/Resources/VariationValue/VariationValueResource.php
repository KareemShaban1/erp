<?php

namespace App\Http\Resources\VariationValue;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariationValueResource extends JsonResource
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
            'code' => $this->code,
            $this->mergeWhen($this->withFullData, function () {
                return [
                    'variation_template_id' => $this->variation_template_id,
                    'created_at' => $this->created_at,
                    'deleted_at' => $this->deleted_at,
                ];
            }),

        ];


    }
}
