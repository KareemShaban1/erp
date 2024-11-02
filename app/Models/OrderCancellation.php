<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCancellation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['order_id','client_id','status','reason','admin_response','requested_at','processed_at'];

    public function client(){
        return $this->belongsTo(Client::class,'client_id');
    }

    public function order(){
        return $this->belongsTo(Order::class,'order_id');
    }

}
