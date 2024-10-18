<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariation\StoreProductVariationRequest;
use App\Http\Requests\ProductVariation\UpdateProductVariationRequest;
use App\Models\ProductVariation;
use App\Services\API\ProductVariationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariationController extends Controller
{


    protected $service;

    public function __construct(ProductVariationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        $ProductVariations = $this->service->list($request);

        if ($ProductVariations instanceof JsonResponse) {
            return $ProductVariations;
        }

        return $ProductVariations->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Categories have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created ProductVariation in storage.
     */
    public function store(StoreProductVariationRequest $request)
    {
            $data = $request->validated();
            $ProductVariation = $this->service->store( $data);

            if ($ProductVariation instanceof JsonResponse) {
                return $ProductVariation;
            }

            return $this->returnJSON($ProductVariation, __('message.ProductVariation has been created successfully'));
    }

    /**
     * Display the specified ProductVariation.
     */
    public function show($id)
    {

        $ProductVariation = $this->service->show($id);

        if ($ProductVariation instanceof JsonResponse) {
            return $ProductVariation;
        }

        return $this->returnJSON($ProductVariation, __('message.ProductVariation has been created successfully'));

    }

    /**
     * Update the specified ProductVariation in storage.
     */
    public function update(UpdateProductVariationRequest $request, ProductVariation $ProductVariation)
    {
            $ProductVariation = $this->service->update($request,$ProductVariation);

            if ($ProductVariation instanceof JsonResponse) {
                return $ProductVariation;
            }

            return $this->returnJSON($ProductVariation, __('message.ProductVariation has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $ProductVariation = $this->service->destroy($id);

        if ($ProductVariation instanceof JsonResponse) {
            return $ProductVariation;
        }

        return $this->returnJSON($ProductVariation, __('message.ProductVariation has been deleted successfully'));
    }

    public function restore($id)
    {
        $ProductVariation = $this->service->restore($id);

        if ($ProductVariation instanceof JsonResponse) {
            return $ProductVariation;
        }

        return $this->returnJSON($ProductVariation, __('message.ProductVariation has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $ProductVariation = $this->service->forceDelete($id);

        if ($ProductVariation instanceof JsonResponse) {
            return $ProductVariation;
        }

        return $this->returnJSON($ProductVariation, __('message.ProductVariation has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id',
        ]);


        $ProductVariation = $this->service->bulkDelete($request->ids);

        if ($ProductVariation instanceof JsonResponse) {
            return $ProductVariation;
        }

        return $this->returnJSON($ProductVariation, __('message.ProductVariation has been deleted successfully.'));
    }
}
