<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariationLocationDetails extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['product_id','product_variation_id','variation_id','location_id','qty_available'];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function location(){
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function variation(){
        return $this->belongsTo(Variation::class, 'variation_id');
    }

    public function product_variation(){
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

}
