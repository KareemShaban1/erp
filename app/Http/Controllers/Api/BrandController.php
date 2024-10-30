<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\API\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrandController extends Controller
{


    protected $service;

    public function __construct(BrandService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        Log::info('Reached CartController index method');

        $brands = $this->service->list($request);

        if ($brands instanceof JsonResponse) {
            return $brands;
        }

        return $brands->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Categories have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created Brand in storage.
     */
    public function store(StoreBrandRequest $request)
    {
            $data = $request->validated();
            $brand = $this->service->store( $data);

            if ($brand instanceof JsonResponse) {
                return $brand;
            }

            return $this->returnJSON($brand, __('message.Brand has been created successfully'));
    }

    /**
     * Display the specified Brand.
     */
    public function show($id)
    {

        $brand = $this->service->show($id);

        if ($brand instanceof JsonResponse) {
            return $brand;
        }

        return $this->returnJSON($brand, __('message.Brand has been created successfully'));

    }

    /**
     * Update the specified Brand in storage.
     */
    public function update(UpdateBrandRequest $request, Brand $brand)
    {
            $brand = $this->service->update($request,$brand);

            if ($brand instanceof JsonResponse) {
                return $brand;
            }

            return $this->returnJSON($brand, __('message.Brand has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $brand = $this->service->destroy($id);

        if ($brand instanceof JsonResponse) {
            return $brand;
        }

        return $this->returnJSON($brand, __('message.Brand has been deleted successfully'));
    }

    public function restore($id)
    {
        $brand = $this->service->restore($id);

        if ($brand instanceof JsonResponse) {
            return $brand;
        }

        return $this->returnJSON($brand, __('message.Brand has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $brand = $this->service->forceDelete($id);

        if ($brand instanceof JsonResponse) {
            return $brand;
        }

        return $this->returnJSON($brand, __('message.Brand has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id',
        ]);


        $brand = $this->service->bulkDelete($request->ids);

        if ($brand instanceof JsonResponse) {
            return $brand;
        }

        return $this->returnJSON($brand, __('message.Brand has been deleted successfully.'));
    }
}
