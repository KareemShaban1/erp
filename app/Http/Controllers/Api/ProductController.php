<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Services\API\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{


    protected $service;

    public function __construct(ProductService $service)
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
     * Store a newly created Product in storage.
     */
    public function store(StoreProductRequest $request)
    {
            $data = $request->validated();
            $Product = $this->service->store( $data);

            if ($Product instanceof JsonResponse) {
                return $Product;
            }

            return $this->returnJSON($Product, __('message.Product has been created successfully'));
    }

    /**
     * Display the specified Product.
     */
    public function show($id)
    {

        $Product = $this->service->show($id);

        if ($Product instanceof JsonResponse) {
            return $Product;
        }

        return $this->returnJSON($Product, __('message.Product has been created successfully'));

    }

    /**
     * Update the specified Product in storage.
     */
    public function update(UpdateProductRequest $request, Product $Product)
    {
            $Product = $this->service->update($request,$Product);

            if ($Product instanceof JsonResponse) {
                return $Product;
            }

            return $this->returnJSON($Product, __('message.Product has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Product = $this->service->destroy($id);

        if ($Product instanceof JsonResponse) {
            return $Product;
        }

        return $this->returnJSON($Product, __('message.Product has been deleted successfully'));
    }

    public function restore($id)
    {
        $Product = $this->service->restore($id);

        if ($Product instanceof JsonResponse) {
            return $Product;
        }

        return $this->returnJSON($Product, __('message.Product has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $Product = $this->service->forceDelete($id);

        if ($Product instanceof JsonResponse) {
            return $Product;
        }

        return $this->returnJSON($Product, __('message.Product has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id',
        ]);


        $Product = $this->service->bulkDelete($request->ids);

        if ($Product instanceof JsonResponse) {
            return $Product;
        }

        return $this->returnJSON($Product, __('message.Product has been deleted successfully.'));
    }
}
