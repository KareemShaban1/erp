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
    protected $fillable = ['order_id', 'pending_at', 'processing_at', 'shipped_at', 'canceled_at', 'completed_at'];

    /**
     * Convert pending_at to 'Y-m-d H:i:s' format if it is set.
     *
     * @return string|null
     */
    public function getPendingAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    /**
     * Convert processing_at to 'Y-m-d H:i:s' format if it is set.
     *
     * @return string|null
     */
    public function getProcessingAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    /**
     * Convert shipped_at to 'Y-m-d H:i:s' format if it is set.
     *
     * @return string|null
     */
    public function getShippedAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    /**
     * Convert canceled_at to 'Y-m-d H:i:s' format if it is set.
     *
     * @return string|null
     */
    public function getCanceledAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    /**
     * Convert completed_at to 'Y-m-d H:i:s' format if it is set.
     *
     * @return string|null
     */
    public function getCompletedAtAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
