<?php

namespace App\Http\Resources\ApplicationSettings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationSettingsResource extends JsonResource
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
            'key' => $this->key,
            $this->mergeWhen($this->withFullData, function () {
                return [
                    'type' => $this->type,
                    'value' => $this->value,
                    'created_at' => $this->created_at,
                    'deleted_at' => $this->deleted_at,
                ];
            }),
        ];


    }
}
