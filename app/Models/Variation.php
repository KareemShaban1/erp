<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variation extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'variations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name','product_id','sub_sku','product_variation_id','woocommerce_variation_id',
    'variation_value_id','default_purchase_price','dpp_inc_tax','profit_percent','default_sell_price',
    'sell_price_inc_tax','combo_variations'];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'combo_variations' => 'array',
    ];

    protected $appends = ['code','total_qty_available'];

    
    public function product_variation()
    {
        return $this->belongsTo(\App\Models\ProductVariation::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function variation_value()
    {
        return $this->belongsTo(\App\Models\VariationValueTemplate::class,'variation_value_id');
    }

    /**
     * Get the sell lines associated with the variation.
     */
    public function sell_lines()
    {
        return $this->hasMany(\App\Models\TransactionSellLine::class);
    }

    /**
     * Get the location wise details of the the variation.
     */
    public function variation_location_details()
    {
        return $this->hasMany(\App\Models\VariationLocationDetails::class);
    }

    /**
     * Get Selling price group prices.
     */
    public function group_prices()
    {
        return $this->hasMany(\App\Models\VariationGroupPrice::class, 'variation_id');
    }

    public function media()
    {
        return $this->morphMany(\App\Models\Media::class, 'model');
    }

    /**
     * Define the discounts relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function discounts()
    {
        return $this->belongsToMany(\App\Models\Discount::class, 'discount_variations', 'variation_id', 'discount_id');
    }
    


    public function getFullNameAttribute()
    {
        $name = $this->product->name;
        if ($this->product->type == 'variable') {
            $name .= ' - ' . $this->product_variation->name . ' - ' . $this->name;
        }
        $name .= ' (' . $this->sub_sku . ')';

        return $name;
    }


     /**
     * Accessor for the code attribute.
     *
     * @return string|null
     */
    public function getCodeAttribute()
    {
        // Check if the variation value exists and its name matches
        if ($this->variation_value && $this->variation_value->name === $this->name) {
            return $this->variation_value->code; // Return the code if conditions are met
        }

        return null; // Return null if the condition is not met
    }

    public function getTotalQtyAvailableAttribute()
{
    // Sum the qty_available of all related VariationLocationDetails records
    return $this->variation_location_details()->sum('qty_available');
}
}
