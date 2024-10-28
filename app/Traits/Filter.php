<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filter
{
    /**
     * Scope a query to filter data based on the provided filters.
     *
     * @param Builder $query
     * @param array|Request $filters
     * @return Builder
     */
    public function scopeFilter(Builder $query, $filters)
    {
         
        // If filters is an instance of Request, get its input
        $filters = $filters instanceof Request ? $filters->all() : $filters;

        // Loop through each filter and apply it to the query
        foreach ($filters as $field => $value) {
            if (method_exists($this, 'filter' . ucfirst($field))) {
                // Use custom filter method if defined (e.g., filterStatus)
                $this->{'filter' . ucfirst($field)}($query, $value);
            } elseif ($this->isFillable($field) || in_array($field, $this->appends)) {
                // Apply a simple where clause if the field is fillable or in appends
                $query->where($field, $value);
            }
        }

        return $query;
    }

    /**
     * Example custom filter for 'status' field.
     *
     * @param Builder $query
     * @param mixed $value
     * @return void
     */
    protected function filterStatus(Builder $query, $value)
    {
        $query->where('status', $value);
    }
}
