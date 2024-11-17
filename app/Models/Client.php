<?php

namespace App\Models;

use App\Scopes\BusinessIdScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class Client  extends Authenticatable
{
    use HasFactory;
    use HasApiTokens;
    use Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['contact_id','business_location_id','email_address',
    'password','location','client_type','latitude','longitude','fcm_token','account_status'];

    public function business_location(){
        return $this->belongsTo(BusinessLocation::class);
    }

    public function contact(){
        return $this->belongsTo(Contact::class);
    }

    /**
     * Scope a query to filter by business ID.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBusinessId($query)
    {
        if (Auth::check() && Auth::user() instanceof Client) {
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
