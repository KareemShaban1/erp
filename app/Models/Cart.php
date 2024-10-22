<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['client_id','product_id','variation_id','quantity','price','discount','total'];

    public function client(){
        return $this->belongsTo(Contact::class)->typeCustomer();
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function variation(){
        return $this->belongsTo(Variation::class);
    }
}
