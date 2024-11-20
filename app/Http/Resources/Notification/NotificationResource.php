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
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
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
                    'data' => $this->transformData($this->data),
                    'read_at' => $this->read_at,
                    'created_at'=> $this->created_at,
                ];
            }),
        ];
    }

    /**
     * Decode and transform the JSON "data" column.
     *
     * @param  string|array  $data
     * @return array
     */
    protected function transformData($data)
    {
        // Decode if $data is a JSON-encoded string
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : [];
        }

        // Return as-is if $data is already an array
        return (array) $data;
    }
}
