<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warranty\StoreWarrantyRequest;
use App\Http\Requests\Warranty\UpdateWarrantyRequest;
use App\Models\Warranty;
use App\Services\API\WarrantyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarrantyController extends Controller
{


    protected $service;

    public function __construct(WarrantyService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the warranties.
     */
    public function index(Request $request)
    {
        $warranties = $this->service->list($request);

        if ($warranties instanceof JsonResponse) {
            return $warranties;
        }

        return $warranties->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.warranties have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created Warranty in storage.
     */
    public function store(StoreWarrantyRequest $request)
    {
            $data = $request->validated();
            $Warranty = $this->service->store( $data);

            if ($Warranty instanceof JsonResponse) {
                return $Warranty;
            }

            return $this->returnJSON($Warranty, __('message.Warranty has been created successfully'));
    }

    /**
     * Display the specified Warranty.
     */
    public function show($id)
    {

        $Warranty = $this->service->show($id);

        if ($Warranty instanceof JsonResponse) {
            return $Warranty;
        }

        return $this->returnJSON($Warranty, __('message.Warranty has been created successfully'));

    }

    /**
     * Update the specified Warranty in storage.
     */
    public function update(UpdateWarrantyRequest $request, Warranty $Warranty)
    {
            $Warranty = $this->service->update($request,$Warranty);

            if ($Warranty instanceof JsonResponse) {
                return $Warranty;
            }

            return $this->returnJSON($Warranty, __('message.Warranty has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Warranty = $this->service->destroy($id);

        if ($Warranty instanceof JsonResponse) {
            return $Warranty;
        }

        return $this->returnJSON($Warranty, __('message.Warranty has been deleted successfully'));
    }

    public function restore($id)
    {
        $Warranty = $this->service->restore($id);

        if ($Warranty instanceof JsonResponse) {
            return $Warranty;
        }

        return $this->returnJSON($Warranty, __('message.Warranty has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $Warranty = $this->service->forceDelete($id);

        if ($Warranty instanceof JsonResponse) {
            return $Warranty;
        }

        return $this->returnJSON($Warranty, __('message.Warranty has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:warranties,id',
        ]);


        $Warranty = $this->service->bulkDelete($request->ids);

        if ($Warranty instanceof JsonResponse) {
            return $Warranty;
        }

        return $this->returnJSON($Warranty, __('message.Warranty has been deleted successfully.'));
    }
}
