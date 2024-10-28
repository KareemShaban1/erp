<?php

namespace App\Services\ApplicationDashboard;

use App\Http\Resources\Category\CategoryResource;
use App\Models\Category;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all categories with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            // $query = Category::with('sub_categories')->productType()->latest();

            // $query = $this->withTrashed($query, $request);

            // $categories = $this->withPagination($query, $request);

            // return $categories;

            $query = Category::with('sub_categories')->productType()->latest()
            ->select('id', 'name', 'parent_id');

        if ($search = $request->input('search.value')) {
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhereHas('parent', function ($q) use ($search) {
                      $q->where('name', 'LIKE', "%{$search}%");
                  });
        }

        $filteredRecords = $query->count();

        if ($request->has('order')) {
            $columns = ['id', 'name', 'parent.name'];
            $columnIndex = $request->input('order.0.column');
            $column = $columns[$columnIndex];
            $dir = $request->input('order.0.dir');

            if ($column === 'parent.name') {
                $query->join('categories as parents', 'categories.parent_id', '=', 'parents.id')
                      ->orderBy('parents.name', $dir);
            } else {
                $query->orderBy($column, $dir);
            }
        } else {
            $query->orderBy('id', 'asc');
        }

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $categories = $query->offset($start)->limit($length)->get();

        return [
            'filteredRecords' => $filteredRecords,
            'categories' => $categories
        ];

            // return (new CategoryCollection($categories))
            // ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing categories'));
        }
    }

    public function show($id) {

        try {
            $category = Category::businessId()->find($id);

            if(!$category) {
                return null;
            }
            return $category;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing category'));
        }
    }

    /**
     * Create a new category.
     */
    public function store($data)
    {

        try {

        // First, create the category without the image
        $category = Category::create($data);

        // Handle the main image and gallery uploads in a single helper function
            // $this->handleImages($request, 'image', 'Category', $category->id, $fileUploader);
            // $this->handleImages($request, 'gallery', 'Category', $category->id, $fileUploader);

        // Return the created category
        return new CategoryResource($category);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing category'));
    }
    }

    /**
     * Update the specified category.
     */
    public function update($request,$category)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $category->update($data);

        return new CategoryResource($category);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating category'));
    }
    }

    public function destroy($id)
    {
        try {

            $category = category::find($id);

            if(!$category) {
                return null;
            }
            $category->delete();
            return $category;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting category'));
        }
    }

    public function restore($id)
    {
        try {
            $category = category::withTrashed()->findOrFail($id);
            $category->restore();
            return new CategoryResource($category);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring category'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $category = category::withTrashed()
                ->findOrFail($id);

            $category->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting category'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = category::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                category::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = category::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                category::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting categories'));
        }
    }
}
