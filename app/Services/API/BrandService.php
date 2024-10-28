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
     * Get all brands with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = Brand::query();

            $query = $this->withTrashed($query, $request);

            $brands = $this->withPagination($query, $request);

            return (new BrandCollection($brands))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing brands'));
        }
    }

    public function show($id) {

        try {
            $brand = Brand::businessId()->find($id);

            if(!$brand) {
                return null;
            }
            return $brand;


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
        $brand = Brand::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'Brand', $brand->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'Brand', $brand->id, $fileUploader);

        // Return the created Brand
        return new BrandResource($brand);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing Brand'));
    }
    }

    /**
     * Update the specified Brand.
     */
    public function update($request,$brand)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $brand->update($data);

        return new BrandResource($brand);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating Brand'));
    }
    }

    public function destroy($id)
    {
        try {

            $brand = Brand::find($id);

            if(!$brand) {
                return null;
            }
            $brand->delete();
            return $brand;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Brand'));
        }
    }

    public function restore($id)
    {
        try {
            $brand = Brand::withTrashed()->findOrFail($id);
            $brand->restore();
            return new BrandResource($brand);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring Brand'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $brand = Brand::withTrashed()
                ->findOrFail($id);

            $brand->forceDelete();
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
            return $this->handleException($e, __('message.Error happened while deleting brands'));
        }
    }
}
