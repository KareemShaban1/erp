<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Order extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_uuid','number','client_id','business_location_id','payment_method','order_status','payment_status','shipping_cost','sub_total','total'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Generate UUID for order_uuid if it's not already set
            $order->order_uuid = (string) Str::uuid();

            // Generate a unique order number, e.g., a timestamp-based format
            $order->number = 'ORD-' . strtoupper(Str::random(6)) . '-' . time();
        });
    }

    public function client(){
        return $this->belongsTo(Client::class,'client_id','id');
    }


    public function orderItems(){
        return $this->hasMany(OrderItem::class,'order_id','id');
    }

    public function orderTracking(){
        return $this->hasMany(OrderTracking::class,'order_id','id');
    }

    public function orderRefunds(){
        return $this->hasMany(OrderRefund::class,'order_id','id');
    }
}
