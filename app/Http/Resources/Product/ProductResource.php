<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\Media\MediaCollection;
use App\Http\Resources\Variation\VariationCollection;
use App\Http\Resources\Variation\VariationResource;
use App\Http\Resources\VariationLocationDetails\VariationLocationDetailsCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    protected bool $withFullData = true;

    /**
     * Set whether to return full data or not.
     * 
     * @param bool $withFullData
     * @return self
     */
    public function withFullData(bool $withFullData): self
    {
        $this->withFullData = $withFullData;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */

    //  'name','business_id','type','unit_id','sub_unit_ids','brand_id',
    // 'category_id','sub_category_id','tax','tax_type','enable_stock','alert_quantity','sku',
    // 'barcode_type','expiry_period','expiry_period_type','enable_sr_no','weight',
    // 'product_custom_field1','product_custom_field2','product_custom_field3','product_custom_field4',
    // 'image','product_description','created_by','warranty_id','is_inactive','not_for_selling'
    public function toArray($request): array
    {
        // Basic data to always return
        $data = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        // Conditionally merge the full data if the flag is set to true
        if ($this->withFullData) {
            $variations = $this->variations;

            $current_stock = $variations->sum(function ($variation) {
                return $variation->variation_location_details->sum('qty_available');
            }); 
            $data = array_merge($data, [
                'description' => $this->product_description,
                'type' => $this->type,
                'business_id'=> $this->business_id,
                'brand' => $this->brand ?? null,
                'unit' => $this->unit ?? null,
                'category' => $this->category ?? null,
                'warranty' => $this->warranty ?? null,
                'sub_category' => $this->sub_category->name ?? null,
                'tax' => $this->product_tax->amount ?? null,
                // 'selling_price'=>$this->variations[0]->default_sell_price,
                'current_stock' => $current_stock,
                // 'max_price' => $this->max_price,
                // 'min_price' => $this->min_price,
                // 'max_purchase_price' => $this->max_purchase_price,
                // 'min_purchase_price' => $this->min_purchase_price,
                'image_url' => $this->image_url,
                'media' => (new MediaCollection($this->media))->withFullData(false),
                'variations' => (new VariationCollection($this->variations))->withFullData(true),
                // 'created_at' => $this->created_at, 
                // 'deleted_at' => $this->deleted_at,
            ]);
        }

        return $data;
    }
}
