<?php

namespace App\Services\API;

use App\Http\Resources\Unit\UnitCollection;
use App\Http\Resources\Unit\UnitResource;
use App\Models\Unit;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnitService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all units with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = Unit::query();

            $query = $this->withTrashed($query, $request);

            $units = $this->withPagination($query, $request);

            return (new UnitCollection($units))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing units'));
        }
    }

    public function show($id) {

        try {
            $Unit = Unit::businessId()->find($id);

            if(!$Unit) {
                return null;
            }
            return $Unit;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Unit'));
        }
    }

    /**
     * Create a new Unit.
     */
    public function store($data)
    {

        try {

        // First, create the Unit without the image
        $Unit = Unit::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'Unit', $Unit->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'Unit', $Unit->id, $fileUploader);

        // Return the created Unit
        return new UnitResource($Unit);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing Unit'));
    }
    }

    /**
     * Update the specified Unit.
     */
    public function update($request,$Unit)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $Unit->update($data);

        return new UnitResource($Unit);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating Unit'));
    }
    }

    public function destroy($id)
    {
        try {

            $Unit = Unit::find($id);

            if(!$Unit) {
                return null;
            }
            $Unit->delete();
            return $Unit;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Unit'));
        }
    }

    public function restore($id)
    {
        try {
            $Unit = Unit::withTrashed()->findOrFail($id);
            $Unit->restore();
            return new UnitResource($Unit);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring Unit'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $Unit = Unit::withTrashed()
                ->findOrFail($id);

            $Unit->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting Unit'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = Unit::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                Unit::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = Unit::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                Unit::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting units'));
        }
    }
}
