<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderRefund extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['order_id','client_id','order_item_id','amount','status','reason','admin_response','requested_at','processed_at'];

    public function order(){
        return $this->belongsTo(Order::class,'order_id');
    }

    public function client(){
        return $this->belongsTo(Client::class,'client_id');
    }

    public function order_item(){
        return $this->belongsTo(OrderItem::class,'order_item_id');
    }

}
