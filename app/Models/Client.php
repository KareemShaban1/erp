<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Client  extends Authenticatable
{
    use HasFactory;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['contact_id','business_location_id','email_address','password','location','client_type'];

    public function business_location(){
        return $this->belongsTo(BusinessLocation::class);
    }

    public function contact(){
        return $this->belongsTo(Contact::class);
    }
}
