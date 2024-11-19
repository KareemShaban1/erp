<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            $this->mergeWhen($this->withFullData, function () {
                return [
                    'type' => $this->type,
                    'notifiable_type' => $this->notifiable_type,
                    'notifiable_id' => $this->notifiable_id,
                    'data' => $this->data,
                    'read_at' => $this->read_at,
                ];
            }),
        ];


    }
}
