<?php

namespace App\Services\API;

use App\Http\Resources\Variation\VariationCollection;
use App\Http\Resources\Variation\VariationResource;
use App\Models\Variation;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VariationService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all Variations with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = Variation::query();

            $query = $this->withTrashed($query, $request);

            $Variations = $this->withPagination($query, $request);

            return (new VariationCollection($Variations))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing Variations'));
        }
    }

    public function show($id) {

        try {
            $Variation = Variation::businessId()->find($id);

            if(!$Variation) {
                return null;
            }
            return $Variation;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Variation'));
        }
    }

    /**
     * Create a new Variation.
     */
    public function store($data)
    {

        try {

        // First, create the Variation without the image
        $Variation = Variation::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'Variation', $Variation->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'Variation', $Variation->id, $fileUploader);

        // Return the created Variation
        return new VariationResource($Variation);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing Variation'));
    }
    }

    /**
     * Update the specified Variation.
     */
    public function update($request,$Variation)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $Variation->update($data);

        return new VariationResource($Variation);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating Variation'));
    }
    }

    public function destroy($id)
    {
        try {

            $Variation = Variation::find($id);

            if(!$Variation) {
                return null;
            }
            $Variation->delete();
            return $Variation;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Variation'));
        }
    }

    public function restore($id)
    {
        try {
            $Variation = Variation::withTrashed()->findOrFail($id);
            $Variation->restore();
            return new VariationResource($Variation);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring Variation'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $Variation = Variation::withTrashed()
                ->findOrFail($id);

            $Variation->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting Variation'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = Variation::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                Variation::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = Variation::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                Variation::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Variations'));
        }
    }
}
