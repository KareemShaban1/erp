<?php

namespace App\Http\Resources\Variation;

use App\Http\Resources\Discount\DiscountCollection;
use App\Http\Resources\Media\MediaCollection;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\ProductVariation\ProductVariationResource;
use App\Http\Resources\VariationLocationDetails\VariationLocationDetailsCollection;
use App\Http\Resources\VariationValue\VariationValueResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VariationResource extends JsonResource
{
    protected bool $withFullData = true;
    protected bool $isDiscount = true;
    protected bool $isProduct = true;


    // Add the setter for the isDiscount flag
    public function withFullData(bool $withFullData, bool $isDiscount = true, bool $isProduct = true): self
    {
        $this->withFullData = $withFullData;
        $this->isDiscount = $isDiscount;
        $this->isProduct = $isProduct;

        return $this;
    }

    /**
     * @param $request The incoming HTTP request.
     * @return array<int|string, mixed>  The transformed array representation of the Variation resource.
     */
    public function toArray($request)
    {

        $client = Auth::user();

        // Ensure the client exists and has necessary relationships
        if (!$client || !isset($client->contact->customer_group->selling_price_group)) {
            $selling_group_id = null;
            $selling_group_name = null;
        } else {
            $selling_group_id = $client->contact->customer_group->selling_price_group->id;
            $selling_group_name = $client->contact->customer_group->selling_price_group->name;

        }


        // Fetch selling price if the selling group ID is available
        $variation_selling_price = $selling_group_id 
            ? DB::table('variation_group_prices')
                ->where('price_group_id', $selling_group_id)
                ->where('variation_id', $this->id)
                ->first()
            : null;
            return [
                'id' => $this->id,
                'name' => $this->name,
                $this->mergeWhen($this->withFullData, function () use ($variation_selling_price, $selling_group_name) {
                    return [
                        'discounts' => (new DiscountCollection($this->discounts))->withFullData(true),
                        'total_qty_available' => intval($this->total_qty_available),
                        'combo_variations' => $this->combo_variations,
                        'default_sell_price' => $this->default_sell_price,
                        'sell_price_inc_tax' => $this->sell_price_inc_tax,
                        'client_selling_group' => $selling_group_name,
                        'client_selling_price' => optional($variation_selling_price)->price_inc_tax,
                        'variation_template' => (new ProductVariationResource($this->product_variation))->withFullData(false),
                        'variation_template_value' => (new VariationValueResource($this->variation_value))->withFullData(false),
                        'media' => (new MediaCollection($this->media))->withFullData(false),
                        'locations' => (new VariationLocationDetailsCollection($this->variation_location_details))->withFullData(true),
                    ];
                }),
            ];
    }
}
