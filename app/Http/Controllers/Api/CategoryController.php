<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\API\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{


    protected $service;

    public function __construct(CategoryService $service)
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
     * Store a newly created category in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
            $data = $request->validated();
            $category = $this->service->store( $data);

            if ($category instanceof JsonResponse) {
                return $category;
            }

            return $this->returnJSON($category, __('message.Category has been created successfully'));
    }

    /**
     * Display the specified category.
     */
    public function show($id)
    {

        $category = $this->service->show($id);

        if ($category instanceof JsonResponse) {
            return $category;
        }

        return $this->returnJSON($category, __('message.Category has been created successfully'));

    }

    /**
     * Update the specified category in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
            $category = $this->service->update($request,$category);

            if ($category instanceof JsonResponse) {
                return $category;
            }

            return $this->returnJSON($category, __('message.Category has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = $this->service->destroy($id);

        if ($category instanceof JsonResponse) {
            return $category;
        }

        return $this->returnJSON($category, __('message.Category has been deleted successfully'));
    }

    public function restore($id)
    {
        $category = $this->service->restore($id);

        if ($category instanceof JsonResponse) {
            return $category;
        }

        return $this->returnJSON($category, __('message.Category has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $category = $this->service->forceDelete($id);

        if ($category instanceof JsonResponse) {
            return $category;
        }

        return $this->returnJSON($category, __('message.Category has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id',
        ]);


        $category = $this->service->bulkDelete($request->ids);

        if ($category instanceof JsonResponse) {
            return $category;
        }

        return $this->returnJSON($category, __('message.Category has been deleted successfully.'));
    }
}
