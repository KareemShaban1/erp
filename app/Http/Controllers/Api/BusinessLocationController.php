<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessLocation\StoreBusinessLocationRequest;
use App\Http\Requests\BusinessLocation\UpdateBusinessLocationRequest;
use App\Models\BusinessLocation;
use App\Services\API\BusinessLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessLocationController extends Controller
{


    protected $service;

    public function __construct(BusinessLocationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the businessLocations.
     */
    public function index(Request $request)
    {
        $businessLocations = $this->service->list($request);

        if ($businessLocations instanceof JsonResponse) {
            return $businessLocations;
        }

        return $businessLocations->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.businessLocations have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created BusinessLocation in storage.
     */
    public function store(StoreBusinessLocationRequest $request)
    {
            $data = $request->validated();
            $BusinessLocation = $this->service->store( $data);

            if ($BusinessLocation instanceof JsonResponse) {
                return $BusinessLocation;
            }

            return $this->returnJSON($BusinessLocation, __('message.BusinessLocation has been created successfully'));
    }

    /**
     * Display the specified BusinessLocation.
     */
    public function show($id)
    {

        $BusinessLocation = $this->service->show($id);

        if ($BusinessLocation instanceof JsonResponse) {
            return $BusinessLocation;
        }

        return $this->returnJSON($BusinessLocation, __('message.BusinessLocation has been created successfully'));

    }

    /**
     * Update the specified BusinessLocation in storage.
     */
    public function update(UpdateBusinessLocationRequest $request, BusinessLocation $BusinessLocation)
    {
            $BusinessLocation = $this->service->update($request,$BusinessLocation);

            if ($BusinessLocation instanceof JsonResponse) {
                return $BusinessLocation;
            }

            return $this->returnJSON($BusinessLocation, __('message.BusinessLocation has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $BusinessLocation = $this->service->destroy($id);

        if ($BusinessLocation instanceof JsonResponse) {
            return $BusinessLocation;
        }

        return $this->returnJSON($BusinessLocation, __('message.BusinessLocation has been deleted successfully'));
    }

    public function restore($id)
    {
        $BusinessLocation = $this->service->restore($id);

        if ($BusinessLocation instanceof JsonResponse) {
            return $BusinessLocation;
        }

        return $this->returnJSON($BusinessLocation, __('message.BusinessLocation has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $BusinessLocation = $this->service->forceDelete($id);

        if ($BusinessLocation instanceof JsonResponse) {
            return $BusinessLocation;
        }

        return $this->returnJSON($BusinessLocation, __('message.BusinessLocation has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:businessLocations,id',
        ]);


        $BusinessLocation = $this->service->bulkDelete($request->ids);

        if ($BusinessLocation instanceof JsonResponse) {
            return $BusinessLocation;
        }

        return $this->returnJSON($BusinessLocation, __('message.BusinessLocation has been deleted successfully.'));
    }
}
