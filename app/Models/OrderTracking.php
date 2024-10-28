<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTracking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['order_id','pending_at','processing_at','shipped_at','canceled_at','declined_at'];

    public function order(){
        return $this->belongsTo(Order::class);
    }
}
