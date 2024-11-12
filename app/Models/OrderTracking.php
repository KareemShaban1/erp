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

    /**
     * Ensure timestamps are converted to UNIX format in the response.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        // Convert dates to UNIX timestamps
        $array['pending_at'] = $this->pending_at;
        $array['processing_at'] = $this->processing_at;
        $array['shipped_at'] = $this->shipped_at;
        $array['canceled_at'] = $this->canceled_at;
        $array['completed_at'] = $this->completed_at;

        return $array;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
