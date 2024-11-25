<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    // protected $dates = ['starts_at', 'ends_at'];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

/**
     * The variations that belong to the discount.
     */
    public function variations()
    {
        return $this->belongsToMany(\App\Models\Variation::class, 'discount_variations', 'discount_id', 'variation_id');
    }

    public function location(){
        return $this->belongsTo(\App\Models\BusinessLocation::class, 'location_id');
    }

/**
     * Accessor to get the discounted price for a given variation.
     *
     * @param Variation $variation
     * @return float
     */
    public function getDiscountedPriceAttribute(Variation $variation): float
    {
        $originalPrice = $variation->default_sell_price;
        $discountAmount = $this->discount_amount;

        if ($this->discount_type === 'fixed') {
            return max(0, $originalPrice - $discountAmount);
        } elseif ($this->discount_type === 'percentage') {
            $discountedPrice = $originalPrice - ($originalPrice * ($discountAmount / 100));
            return max(0, $discountedPrice);
        }

        return $originalPrice;
    }

    public function scopeBusinessId(){
        return $this->where('business_id', auth()->user()->contact->business_id);
    }
}
