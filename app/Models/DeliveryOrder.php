<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeliveryOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['order_id','delivery_id','status','payment_status',
    'assigned_at','shipped_at','delivered_at'];

    public function order(){
        return $this->belongsTo(Order::class);
    }

    public function delivery(){
        return $this->belongsTo(Delivery::class);
    }

    // // Define relationship with the Order model
    // public function orders(): BelongsToMany
    // {
    //     return $this->belongsToMany(Order::class, 'delivery_orders')
    //                 ->withPivot('status', 'assigned_at', 'delivered_at')
    //                 ->withTimestamps();
    // }

}
