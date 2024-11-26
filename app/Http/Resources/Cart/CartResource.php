<?php

namespace App\Http\Resources\Cart;

use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Variation\VariationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
                    'client'=>$this->client,
                    'product' => (new ProductResource( $this->product))->withFullData(true),
                    // 'variation' => (new VariationResource( $this->variation))->withFullData(true),
                    'quantity'=>$this->quantity,
                    'price'=>$this->price,
                    'total'=>$this->total,
                    'discount'=>$this->discount,
                ];
            }),
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];


    }
}
