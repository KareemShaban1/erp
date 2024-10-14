<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\API\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $categories = $this->service->list($request);

        if ($categories instanceof JsonResponse) {
            return $categories;
        }

        return $categories->additional([
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
            $Brand = $this->service->store( $data);

            if ($Brand instanceof JsonResponse) {
                return $Brand;
            }

            return $this->returnJSON($Brand, __('message.Brand has been created successfully'));
    }

    /**
     * Display the specified Brand.
     */
    public function show($id)
    {

        $Brand = $this->service->show($id);

        if ($Brand instanceof JsonResponse) {
            return $Brand;
        }

        return $this->returnJSON($Brand, __('message.Brand has been created successfully'));

    }

    /**
     * Update the specified Brand in storage.
     */
    public function update(UpdateBrandRequest $request, Brand $Brand)
    {
            $Brand = $this->service->update($request,$Brand);

            if ($Brand instanceof JsonResponse) {
                return $Brand;
            }

            return $this->returnJSON($Brand, __('message.Brand has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Brand = $this->service->destroy($id);

        if ($Brand instanceof JsonResponse) {
            return $Brand;
        }

        return $this->returnJSON($Brand, __('message.Brand has been deleted successfully'));
    }

    public function restore($id)
    {
        $Brand = $this->service->restore($id);

        if ($Brand instanceof JsonResponse) {
            return $Brand;
        }

        return $this->returnJSON($Brand, __('message.Brand has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $Brand = $this->service->forceDelete($id);

        if ($Brand instanceof JsonResponse) {
            return $Brand;
        }

        return $this->returnJSON($Brand, __('message.Brand has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id',
        ]);


        $Brand = $this->service->bulkDelete($request->ids);

        if ($Brand instanceof JsonResponse) {
            return $Brand;
        }

        return $this->returnJSON($Brand, __('message.Brand has been deleted successfully.'));
    }
}
