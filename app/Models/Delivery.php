<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Delivery extends Authenticatable
{
    use HasFactory;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['contact_id','business_location_id','email_address',
    'password','location','status','account_status'];

    public function business_location(){
        return $this->belongsTo(BusinessLocation::class);
    }

    public function contact(){
        return $this->belongsTo(Contact::class);
    }

    // Define relationship with the Order model
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'delivery_orders')
                    ->withPivot('status', 'assigned_at', 'delivered_at')
                    ->withTimestamps();
    }
    

}
