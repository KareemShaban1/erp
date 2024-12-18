<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
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
    'password','location','status','account_status','user_id','fcm_token'];

    public function business_location(){
        return $this->belongsTo(BusinessLocation::class);
    }

    public function contact(){
        return $this->belongsTo(Contact::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }

    // Define relationship with the Order model
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'delivery_orders')
                    ->withPivot('status', 'assigned_at', 'delivered_at')
                    ->withTimestamps();
    }
    

      /**
     * Scope a query to filter by business ID.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBusinessId($query)
    {
        if (Auth::check() && Auth::user() instanceof Delivery) {
            $business_id = Auth::user()->contact->business_id ?? null;
            if ($business_id) {
                $query->whereHas('contact', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                });
            }
        }
    
        return $query;
    }

}
