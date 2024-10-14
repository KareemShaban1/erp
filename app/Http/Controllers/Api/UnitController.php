<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\API\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{


    protected $service;

    public function __construct(UnitService $service)
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
     * Store a newly created Unit in storage.
     */
    public function store(StoreUnitRequest $request)
    {
            $data = $request->validated();
            $Unit = $this->service->store( $data);

            if ($Unit instanceof JsonResponse) {
                return $Unit;
            }

            return $this->returnJSON($Unit, __('message.Unit has been created successfully'));
    }

    /**
     * Display the specified Unit.
     */
    public function show($id)
    {

        $Unit = $this->service->show($id);

        if ($Unit instanceof JsonResponse) {
            return $Unit;
        }

        return $this->returnJSON($Unit, __('message.Unit has been created successfully'));

    }

    /**
     * Update the specified Unit in storage.
     */
    public function update(UpdateUnitRequest $request, Unit $Unit)
    {
            $Unit = $this->service->update($request,$Unit);

            if ($Unit instanceof JsonResponse) {
                return $Unit;
            }

            return $this->returnJSON($Unit, __('message.Unit has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Unit = $this->service->destroy($id);

        if ($Unit instanceof JsonResponse) {
            return $Unit;
        }

        return $this->returnJSON($Unit, __('message.Unit has been deleted successfully'));
    }

    public function restore($id)
    {
        $Unit = $this->service->restore($id);

        if ($Unit instanceof JsonResponse) {
            return $Unit;
        }

        return $this->returnJSON($Unit, __('message.Unit has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $Unit = $this->service->forceDelete($id);

        if ($Unit instanceof JsonResponse) {
            return $Unit;
        }

        return $this->returnJSON($Unit, __('message.Unit has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id',
        ]);


        $Unit = $this->service->bulkDelete($request->ids);

        if ($Unit instanceof JsonResponse) {
            return $Unit;
        }

        return $this->returnJSON($Unit, __('message.Unit has been deleted successfully.'));
    }
}
