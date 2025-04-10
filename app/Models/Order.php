<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'order_uuid',
        'number',
        'client_id',
        'parent_order_id',
        'business_location_id',
        'payment_method',
        'order_type',
        'order_status',
        'payment_status',
        'shipping_cost',
        'sub_total',
        'total',
        'from_business_location_id',
        'to_business_location_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Generate UUID for order_uuid if it's not already set
            $order->order_uuid = (string) Str::uuid();

            // Generate order number based on the current date and a random string
            $order->number = 'ORD-' . now()->format('Y-m-d') . '-' . strtoupper(Str::random(3));
        });
    }

    public function relatedOrders()
    {
        return $this->hasMany(Order::class, 'parent_order_id');
    }

    public function parentOrder(){
        return $this->belongsTo(Order::class, 'parent_order_id');
    }


    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id')
            ->with(['business_location', 'contact']);
        // return $this->belongsTo(Client::class, 'client_id')
        //     ->select(['id', 'contact_id','fcm_token','business_location_id'])
        //     ->with(['business_location','contact' => function ($query) {
        //         $query->select('id', 'name', 'first_name', 'last_name','business_id');
        //     }]);
    }


    public function businessLocation()
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id', 'id');
    }

    public function fromBusinessLocation()
    {
        return $this->belongsTo(BusinessLocation::class, 'from_business_location_id', 'id');
    }

    public function toBusinessLocation()
    {
        return $this->belongsTo(BusinessLocation::class, 'to_business_location_id', 'id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id')
            ->with(['product', 'variation']);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'order_id', 'id');
    }

    public function orderTracking()
    {
        return $this->hasOne(OrderTracking::class, 'order_id', 'id');
    }

    public function orderRefunds()
    {
        return $this->hasMany(OrderRefund::class, 'order_id', 'id');
    }

    public function orderCancellation()
    {
        return $this->hasOne(OrderCancellation::class, 'order_id', 'id');
    }

    // Define relationship with the Delivery model
    public function deliveries(): BelongsToMany
    {
        return $this->belongsToMany(Delivery::class, 'delivery_orders')
            ->with('contact')
            ->withPivot('status', 'assigned_at', 'delivered_at')
            ->withTimestamps();
    }

    public function delivery()
    {
        return $this->hasOneThrough(
            Delivery::class,           // Final related model
            DeliveryOrder::class,      // Intermediate model (junction table)
            'order_id',                // Foreign key on the delivery_orders table
            'id',                      // Foreign key on the deliveries table
            'id',                      // Local key on the orders table
            'delivery_id'              // Local key on the delivery_orders table
        )->with('contact');
    }

    public function getHasDeliveryAttribute()
    {
        return $this->deliveries->isNotEmpty();
    }

    public function scopeByBusinessLocation($query, $businessLocationId)
    {
        return $query->where('business_location_id', $businessLocationId);
    }
}
