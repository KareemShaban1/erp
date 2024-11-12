<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OrderTracking extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'pending_at', 'processing_at', 'shipped_at', 'canceled_at', 'completed_at'];

    public function getPendingAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->toISOString() : null;
    }

    public function getProcessingAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->toISOString() : null;
    }

    public function getShippedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->toISOString() : null;
    }

    public function getCanceledAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->toISOString() : null;
    }

    public function getCompletedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->toISOString() : null;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
