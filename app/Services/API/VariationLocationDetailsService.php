<?php

namespace App\Services\API;

use App\Http\Resources\VariationLocationDetails\VariationLocationDetailsCollection;
use App\Http\Resources\VariationLocationDetails\VariationLocationDetailsResource;
use App\Models\VariationLocationDetails;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VariationLocationDetailsService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all brands with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = VariationLocationDetails::query();

            $query = $this->withTrashed($query, $request);

            $brands = $this->withPagination($query, $request);

            return (new VariationLocationDetailsCollection($brands))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing brands'));
        }
    }

    public function show($id) {

        try {
            $VariationLocationDetails = VariationLocationDetails::businessId()->find($id);

            if(!$VariationLocationDetails) {
                return null;
            }
            return $VariationLocationDetails;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing VariationLocationDetails'));
        }
    }

    /**
     * Create a new VariationLocationDetails.
     */
    public function store($data)
    {

        try {

        // First, create the VariationLocationDetails without the image
        $VariationLocationDetails = VariationLocationDetails::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'VariationLocationDetails', $VariationLocationDetails->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'VariationLocationDetails', $VariationLocationDetails->id, $fileUploader);

        // Return the created VariationLocationDetails
        return new VariationLocationDetailsResource($VariationLocationDetails);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing VariationLocationDetails'));
    }
    }

    /**
     * Update the specified VariationLocationDetails.
     */
    public function update($request,$VariationLocationDetails)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $VariationLocationDetails->update($data);

        return new VariationLocationDetailsResource($VariationLocationDetails);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating VariationLocationDetails'));
    }
    }

    public function destroy($id)
    {
        try {

            $VariationLocationDetails = VariationLocationDetails::find($id);

            if(!$VariationLocationDetails) {
                return null;
            }
            $VariationLocationDetails->delete();
            return $VariationLocationDetails;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting VariationLocationDetails'));
        }
    }

    public function restore($id)
    {
        try {
            $VariationLocationDetails = VariationLocationDetails::withTrashed()->findOrFail($id);
            $VariationLocationDetails->restore();
            return new VariationLocationDetailsResource($VariationLocationDetails);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring VariationLocationDetails'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $VariationLocationDetails = VariationLocationDetails::withTrashed()
                ->findOrFail($id);

            $VariationLocationDetails->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting VariationLocationDetails'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = VariationLocationDetails::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                VariationLocationDetails::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = VariationLocationDetails::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                VariationLocationDetails::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting brands'));
        }
    }
}
