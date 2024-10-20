<?php

namespace App\Http\Resources\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
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
            'display_url' => $this->display_url,
            $this->mergeWhen($this->withFullData, function () {
                return [
                    'file_name' => $this->file_name,
                    'description' => $this->description,
                    'business_id' => $this->business_id,
                'uploaded_by' => $this->uploaded_by,
                'created_at' => $this->created_at,
                'deleted_at' => $this->deleted_at,
                ];
            }),
          
        ];


    }
}
