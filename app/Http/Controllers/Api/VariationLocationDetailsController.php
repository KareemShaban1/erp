<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VariationLocationDetails\StoreVariationLocationDetailsRequest;
use App\Http\Requests\VariationLocationDetails\UpdateVariationLocationDetailsRequest;
use App\Models\VariationLocationDetails;
use App\Services\API\VariationLocationDetailsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariationLocationDetailsController extends Controller
{


    protected $service;

    public function __construct(VariationLocationDetailsService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the variationLocationDetails.
     */
    public function index(Request $request)
    {
        $variationLocationDetails = $this->service->list($request);

        if ($variationLocationDetails instanceof JsonResponse) {
            return $variationLocationDetails;
        }

        return $variationLocationDetails->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Categories have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created VariationLocationDetails in storage.
     */
    public function store(StoreVariationLocationDetailsRequest $request)
    {
            $data = $request->validated();
            $variationLocationDetails = $this->service->store( $data);

            if ($variationLocationDetails instanceof JsonResponse) {
                return $variationLocationDetails;
            }

            return $this->returnJSON($variationLocationDetails, __('message.VariationLocationDetails has been created successfully'));
    }

    /**
     * Display the specified VariationLocationDetails.
     */
    public function show($id)
    {

        $variationLocationDetails = $this->service->show($id);

        if ($variationLocationDetails instanceof JsonResponse) {
            return $variationLocationDetails;
        }

        return $this->returnJSON($variationLocationDetails, __('message.VariationLocationDetails has been created successfully'));

    }

    /**
     * Update the specified VariationLocationDetails in storage.
     */
    public function update(UpdateVariationLocationDetailsRequest $request, VariationLocationDetails $variationLocationDetails)
    {
            $variationLocationDetails = $this->service->update($request,$variationLocationDetails);

            if ($variationLocationDetails instanceof JsonResponse) {
                return $variationLocationDetails;
            }

            return $this->returnJSON($variationLocationDetails, __('message.VariationLocationDetails has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $variationLocationDetails = $this->service->destroy($id);

        if ($variationLocationDetails instanceof JsonResponse) {
            return $variationLocationDetails;
        }

        return $this->returnJSON($variationLocationDetails, __('message.VariationLocationDetails has been deleted successfully'));
    }

    public function restore($id)
    {
        $variationLocationDetails = $this->service->restore($id);

        if ($variationLocationDetails instanceof JsonResponse) {
            return $variationLocationDetails;
        }

        return $this->returnJSON($variationLocationDetails, __('message.VariationLocationDetails has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $variationLocationDetails = $this->service->forceDelete($id);

        if ($variationLocationDetails instanceof JsonResponse) {
            return $variationLocationDetails;
        }

        return $this->returnJSON($variationLocationDetails, __('message.VariationLocationDetails has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:variationLocationDetails,id',
        ]);


        $variationLocationDetails = $this->service->bulkDelete($request->ids);

        if ($variationLocationDetails instanceof JsonResponse) {
            return $variationLocationDetails;
        }

        return $this->returnJSON($variationLocationDetails, __('message.VariationLocationDetails has been deleted successfully.'));
    }
}
