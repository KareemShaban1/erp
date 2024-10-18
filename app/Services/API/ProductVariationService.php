<?php

namespace App\Services\API;

use App\Http\Resources\ProductVariation\ProductVariationCollection;
use App\Http\Resources\ProductVariation\ProductVariationResource;
use App\Models\ProductVariation;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductVariationService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all ProductVariations with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = ProductVariation::query();

            $query = $this->withTrashed($query, $request);

            $ProductVariations = $this->withPagination($query, $request);

            return (new ProductVariationCollection($ProductVariations))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing ProductVariations'));
        }
    }

    public function show($id) {

        try {
            $ProductVariation = ProductVariation::businessId()->find($id);

            if(!$ProductVariation) {
                return null;
            }
            return $ProductVariation;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing ProductVariation'));
        }
    }

    /**
     * Create a new ProductVariation.
     */
    public function store($data)
    {

        try {

        // First, create the ProductVariation without the image
        $ProductVariation = ProductVariation::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'ProductVariation', $ProductVariation->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'ProductVariation', $ProductVariation->id, $fileUploader);

        // Return the created ProductVariation
        return new ProductVariationResource($ProductVariation);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing ProductVariation'));
    }
    }

    /**
     * Update the specified ProductVariation.
     */
    public function update($request,$ProductVariation)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $ProductVariation->update($data);

        return new ProductVariationResource($ProductVariation);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating ProductVariation'));
    }
    }

    public function destroy($id)
    {
        try {

            $ProductVariation = ProductVariation::find($id);

            if(!$ProductVariation) {
                return null;
            }
            $ProductVariation->delete();
            return $ProductVariation;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting ProductVariation'));
        }
    }

    public function restore($id)
    {
        try {
            $ProductVariation = ProductVariation::withTrashed()->findOrFail($id);
            $ProductVariation->restore();
            return new ProductVariationResource($ProductVariation);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring ProductVariation'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $ProductVariation = ProductVariation::withTrashed()
                ->findOrFail($id);

            $ProductVariation->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting ProductVariation'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = ProductVariation::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                ProductVariation::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = ProductVariation::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                ProductVariation::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting ProductVariations'));
        }
    }
}
