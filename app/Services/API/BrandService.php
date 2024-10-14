<?php

namespace App\Services\API;

use App\Http\Resources\Brand\BrandCollection;
use App\Http\Resources\Brand\BrandResource;
use App\Models\Brand;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrandService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all categories with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = Brand::businessId();

            $query = $this->withTrashed($query, $request);

            $categories = $this->withPagination($query, $request);

            return (new BrandCollection($categories))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing categories'));
        }
    }

    public function show($id) {

        try {
            $Brand = Brand::businessId()->find($id);

            if(!$Brand) {
                return null;
            }
            return $Brand;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Brand'));
        }
    }

    /**
     * Create a new Brand.
     */
    public function store($data)
    {

        try {

        // First, create the Brand without the image
        $Brand = Brand::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'Brand', $Brand->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'Brand', $Brand->id, $fileUploader);

        // Return the created Brand
        return new BrandResource($Brand);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing Brand'));
    }
    }

    /**
     * Update the specified Brand.
     */
    public function update($request,$Brand)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $Brand->update($data);

        return new BrandResource($Brand);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating Brand'));
    }
    }

    public function destroy($id)
    {
        try {

            $Brand = Brand::find($id);

            if(!$Brand) {
                return null;
            }
            $Brand->delete();
            return $Brand;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Brand'));
        }
    }

    public function restore($id)
    {
        try {
            $Brand = Brand::withTrashed()->findOrFail($id);
            $Brand->restore();
            return new BrandResource($Brand);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring Brand'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $Brand = Brand::withTrashed()
                ->findOrFail($id);

            $Brand->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting Brand'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = Brand::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                Brand::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = Brand::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                Brand::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting categories'));
        }
    }
}
