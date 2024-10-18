<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Variation\StoreVariationRequest;
use App\Http\Requests\Variation\UpdateVariationRequest;
use App\Models\Variation;
use App\Services\API\VariationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariationController extends Controller
{


    protected $service;

    public function __construct(VariationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        $variations = $this->service->list($request);

        if ($variations instanceof JsonResponse) {
            return $variations;
        }

        return $variations->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Categories have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created Variation in storage.
     */
    public function store(StoreVariationRequest $request)
    {
            $data = $request->validated();
            $variation = $this->service->store( $data);

            if ($variation instanceof JsonResponse) {
                return $variation;
            }

            return $this->returnJSON($variation, __('message.Variation has been created successfully'));
    }

    /**
     * Display the specified Variation.
     */
    public function show($id)
    {

        $variation = $this->service->show($id);

        if ($variation instanceof JsonResponse) {
            return $variation;
        }

        return $this->returnJSON($variation, __('message.Variation has been created successfully'));

    }

    /**
     * Update the specified Variation in storage.
     */
    public function update(UpdateVariationRequest $request, Variation $variation)
    {
            $variation = $this->service->update($request,$variation);

            if ($variation instanceof JsonResponse) {
                return $variation;
            }

            return $this->returnJSON($variation, __('message.Variation has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $variation = $this->service->destroy($id);

        if ($variation instanceof JsonResponse) {
            return $variation;
        }

        return $this->returnJSON($variation, __('message.Variation has been deleted successfully'));
    }

    public function restore($id)
    {
        $variation = $this->service->restore($id);

        if ($variation instanceof JsonResponse) {
            return $variation;
        }

        return $this->returnJSON($variation, __('message.Variation has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $variation = $this->service->forceDelete($id);

        if ($variation instanceof JsonResponse) {
            return $variation;
        }

        return $this->returnJSON($variation, __('message.Variation has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id',
        ]);


        $variation = $this->service->bulkDelete($request->ids);

        if ($variation instanceof JsonResponse) {
            return $variation;
        }

        return $this->returnJSON($variation, __('message.Variation has been deleted successfully.'));
    }
}
