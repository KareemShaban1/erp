<?php

namespace App\Services\API;

use App\Http\Resources\BusinessLocation\BusinessLocationCollection;
use App\Http\Resources\BusinessLocation\BusinessLocationResource;
use App\Models\BusinessLocation;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessLocationService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all BusinessLocations with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = BusinessLocation::active();

            $query = $this->withTrashed($query, $request);

            $BusinessLocations = $this->withPagination($query, $request);

            return (new BusinessLocationCollection($BusinessLocations))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing BusinessLocations'));
        }
    }

    public function show($id) {

        try {
            $BusinessLocation = BusinessLocation::businessId()->find($id);

            if(!$BusinessLocation) {
                return null;
            }
            return $BusinessLocation;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing BusinessLocation'));
        }
    }

    /**
     * Create a new BusinessLocation.
     */
    public function store($data)
    {

        try {

        // First, create the BusinessLocation without the image
        $BusinessLocation = BusinessLocation::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'BusinessLocation', $BusinessLocation->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'BusinessLocation', $BusinessLocation->id, $fileUploader);

        // Return the created BusinessLocation
        return new BusinessLocationResource($BusinessLocation);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing BusinessLocation'));
    }
    }

    /**
     * Update the specified BusinessLocation.
     */
    public function update($request,$BusinessLocation)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $BusinessLocation->update($data);

        return new BusinessLocationResource($BusinessLocation);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating BusinessLocation'));
    }
    }

    public function destroy($id)
    {
        try {

            $BusinessLocation = BusinessLocation::find($id);

            if(!$BusinessLocation) {
                return null;
            }
            $BusinessLocation->delete();
            return $BusinessLocation;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting BusinessLocation'));
        }
    }

    public function restore($id)
    {
        try {
            $BusinessLocation = BusinessLocation::withTrashed()->findOrFail($id);
            $BusinessLocation->restore();
            return new BusinessLocationResource($BusinessLocation);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring BusinessLocation'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $BusinessLocation = BusinessLocation::withTrashed()
                ->findOrFail($id);

            $BusinessLocation->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting BusinessLocation'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = BusinessLocation::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                BusinessLocation::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = BusinessLocation::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                BusinessLocation::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting BusinessLocations'));
        }
    }
}
