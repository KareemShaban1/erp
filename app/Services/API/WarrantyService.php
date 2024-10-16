<?php

namespace App\Services\API;

use App\Http\Resources\Warranty\WarrantyCollection;
use App\Http\Resources\Warranty\WarrantyResource;
use App\Models\Warranty;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarrantyService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all warranties with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = Warranty::query();

            $query = $this->withTrashed($query, $request);

            $warranties = $this->withPagination($query, $request);

            return (new WarrantyCollection($warranties))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing warranties'));
        }
    }

    public function show($id) {

        try {
            $Warranty = Warranty::businessId()->find($id);

            if(!$Warranty) {
                return null;
            }
            return $Warranty;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Warranty'));
        }
    }

    /**
     * Create a new Warranty.
     */
    public function store($data)
    {

        try {

        // First, create the Warranty without the image
        $Warranty = Warranty::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'Warranty', $Warranty->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'Warranty', $Warranty->id, $fileUploader);

        // Return the created Warranty
        return new WarrantyResource($Warranty);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing Warranty'));
    }
    }

    /**
     * Update the specified Warranty.
     */
    public function update($request,$Warranty)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $Warranty->update($data);

        return new WarrantyResource($Warranty);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating Warranty'));
    }
    }

    public function destroy($id)
    {
        try {

            $Warranty = Warranty::find($id);

            if(!$Warranty) {
                return null;
            }
            $Warranty->delete();
            return $Warranty;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Warranty'));
        }
    }

    public function restore($id)
    {
        try {
            $Warranty = Warranty::withTrashed()->findOrFail($id);
            $Warranty->restore();
            return new WarrantyResource($Warranty);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring Warranty'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $Warranty = Warranty::withTrashed()
                ->findOrFail($id);

            $Warranty->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting Warranty'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = Warranty::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                Warranty::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = Warranty::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                Warranty::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting warranties'));
        }
    }
}
