<?php

namespace App\Traits;

use App\Exceptions\InvalidScopeException;
use App\Models\LaExtension;
use App\Modules\ClientUser\Models\ClientUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait Filter
{
    /**
     * filterProperties array cases may be as following:
     * case 1: 'property'
     * case 2: 'property' => method
     * case 3: 'property' => [group of columns]
     * case 4: 'property' => [column => method]
     * case 5: 'property' => [relationship => [column => method]]
     * case 6: 'property' => [relationship => [group of columns]]
     * =================================================
     * @param $query
     * @return void
     */
    public function scopeFilter($query)
    {
        try {
            $model = new static;
            foreach ($model->filterProperties ?? [] as $property => $operations) {
                if ($property != null && request()->has($property) && (request($property) != null && request($property) != "null")) {
                    if ($operations == null) {
                        $this->case1($query, $property);
                    } elseif (is_string($operations)) {
                        $this->case2($query, $operations, $property);
                    } elseif (is_array($operations)) {
                        if (count($operations) > 1) {
                            $this->case3($query, $operations, $property);
                        } else {
                            $key = array_keys($operations)[0]; // 'la_organization' || column
                            $value = array_values($operations)[0]; // ['id' => 'where'] or ['name_en', 'name_ar'] || method

                            if (!is_array($value) && !method_exists($model, $value)) { // 'property' => [column => method]
                                $this->case4($query, $value, $key, $property);
                            } else { // 'la_organization' => ['id' => 'where'] or ['name_en', 'name_ar']
                                $relationshipMethod = $model->$key();
                                if (is_array($value) && count($value) == 1) { // 'la_organization' => ['id' => 'where']
                                    $column = array_keys($value)[0];
                                    $method = array_values($value)[0];
                                    $this->case5($column, $method, $relationshipMethod, $query, $key, $property);
                                } else { // 'la_organization' => ['name_en', 'name_ar']
                                    $this->case6($relationshipMethod, $query, $key, $property, $value);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * case 1: 'property'
     * @param $query
     * @param $property
     * @return void
     */
    public function case1($query, $property): void
    {
        $query->where($property, request($property));
    }

    /**
     * case 2: 'property' => method
     * @param $query
     * @param $method
     * @param $property
     * @return void
     */
    public function case2($query, $method, $property): void
    {
        if ($method == 'whereIn') {
            $query->$method($property, explode(',', request($property)));
        } else {
            $query->where($property, request($property));
        }
    }

    /**
     * case 3: 'property' => [group of columns]
     * @param $query
     * @param $columns
     * @param $property
     * @return void
     */
    public function case3($query, $columns, $property): void
    {
        $query->where(function ($query) use ($columns, $property) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'LIKE', '%' . request($property) . '%');
            }
        });
    }

    /**
     * case 4: 'property' => [column => method]
     * @param $query
     * @param $method
     * @param $column
     * @param $property
     * @return void
     */
    public function case4($query, $method, $column, $property): void
    {
        if ($method == 'whereIn') {
            $query->$method($column, explode(',', request($property)));
        } else {
            $query->$method($column, request($property));
        }
    }

    /**
     * case 5: 'property' => [relationship => column]
     * case 6: 'property' => [relationship => [column => method]]
     * @param $value
     * @param $relationshipMethod
     * @param $query
     * @param $key
     * @param $property
     * @return void
     */
    public function case5($column, $method, $relationshipMethod, $query, $key, $property): void
    {
        if ($relationshipMethod instanceof BelongsToMany) { //pivot tables
            $query->whereHas($key, function ($query) use ($property, $column, $method) {
                $this->case4($query, $method, $column, $property);
            });
        } else {
            $query->whereHas($key, function ($query) use ($key, $property, $column, $method) {
                $this->case4($query, $method, Str::plural($key) . "." . $column, $property);
            });
        }
    }

    /**
     * case 7: 'property' => [relationship => [group of columns]]
     * @param $relationshipMethod
     * @param $query
     * @param $key
     * @param $property
     * @param $value
     * @return void
     */
    public function case6($relationshipMethod, $query, $key, $property, $value): void
    {
        if ($relationshipMethod instanceof BelongsToMany) { //pivot tables
            $query->whereHas($key, function ($query) use ($property, $value) {
                $query->where(function ($query) use ($property, $value) {
                    foreach ($value as $column) {
                        $query->orWhere($column, 'LIKE', '%' . request($property) . '%');
                    }
                });
            });
        } else {
            $query->whereHas($key, function ($query) use ($key, $property, $value) {
                $query->where(function ($query) use ($key, $property, $value) {
                    foreach ($value as $column) {
                        $query->orWhere(Str::plural($key) . "." . $column, 'LIKE', '%' . request($property) . '%');
                    }
                });
            });
        }
    }
}
