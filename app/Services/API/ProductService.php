<?php

namespace App\Services\API;

use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all products with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {
        try {
            $query = Product::
            with(['media','unit:id,actual_name,short_name','brand:id,name','category:id,name',
            'sub_category:id,name',
            'warranty:id,name,duration,duration_type'])
            ->where('products.type', '!=', 'modifier')
            ->businessId()->productForSales()
            ->latest();
    
            $query = $this->withTrashed($query, $request);
    
            // Apply pagination or fetch the data
            $products = $this->withPagination($query, $request);

    
            // Wrap the data in ProductCollection and apply withFullData() here
            return (new ProductCollection($products))
                ->withFullData(!($request->full_data == 'false'));
    
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing products'));
        }
    }

    public function show($id) {

        try {
            $product = Product::businessId()->find($id);

            if(!$product) {
                return null;
            }
            return $product;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Product'));
        }
    }

    /**
     * Create a new Product.
     */
    public function store($data)
    {

        try {

        // First, create the Product without the image
        $product = Product::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'Product', $product->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'Product', $product->id, $fileUploader);

        // Return the created Product
        return new ProductResource($product);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing Product'));
    }
    }

    /**
     * Update the specified Product.
     */
    public function update($request,$product)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $product->update($data);

        return new ProductResource($product);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating Product'));
    }
    }

    public function destroy($id)
    {
        try {

            $product = Product::find($id);

            if(!$product) {
                return null;
            }
            $product->delete();
            return $product;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Product'));
        }
    }

    public function restore($id)
    {
        try {
            $product = Product::withTrashed()->findOrFail($id);
            $product->restore();
            return new ProductResource($product);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring Product'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $product = Product::withTrashed()
                ->findOrFail($id);

            $product->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting Product'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = Product::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                Product::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = Product::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                Product::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting products'));
        }
    }
}
