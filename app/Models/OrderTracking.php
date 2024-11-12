<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTracking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['order_id','pending_at','processing_at','shipped_at','canceled_at','completed_at'];

      /**
     * Convert pending_at to a timestamp if it is set.
     *
     * @return int|null
     */
    public function getPendingAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->timestamp : null;
    }

    /**
     * Convert processing_at to a timestamp if it is set.
     *
     * @return int|null
     */
    public function getProcessingAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->timestamp : null;
    }

    /**
     * Convert shipped_at to a timestamp if it is set.
     *
     * @return int|null
     */
    public function getShippedAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->timestamp : null;
    }

    /**
     * Convert canceled_at to a timestamp if it is set.
     *
     * @return int|null
     */
    public function getCanceledAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->timestamp : null;
    }

    /**
     * Convert completed_at to a timestamp if it is set.
     *
     * @return int|null
     */
    public function getCompletedAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->timestamp : null;
    }

    public function order(){
        return $this->belongsTo(Order::class);
    }
}
