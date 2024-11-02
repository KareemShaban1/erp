<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderCancellation\StoreOrderCancellationRequest;
use App\Http\Requests\OrderCancellation\UpdateOrderCancellationRequest;
use App\Models\OrderCancellation;
use App\Services\API\OrderCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderCancellationController extends Controller
{
   

    protected $service;

    public function __construct(OrderCancellationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        $OrderCancellations = $this->service->list($request);

        if ($OrderCancellations instanceof JsonResponse) {
            return $OrderCancellations;
        }

        return $OrderCancellations->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Order Cancellation have been retrieved successfully'),
        ]);
    }

    public function getAuthClientOrderCancellations(Request $request)
    {
        $OrderCancellations = $this->service->getAuthClientOrderCancellations($request);

        if ($OrderCancellations instanceof JsonResponse) {
            return $OrderCancellations;
        }

        return $OrderCancellations->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Order Cancellation have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created OrderCancellation in storage.
     */
    public function store(Request $request)
    {
            // Validate request data
    $validator = Validator::make($request->all(), [
        'order_id' => 'required|exists:orders,id', // Ensure order exists
        'reason' => 'required|max:1000', // Reason is required when requesting cancellation
    ]);

    if ($validator->fails()) {
        // Get the first error message and return a response with it
        $firstError = $validator->errors()->first();
        return response()->json(['message' => $firstError], 422);
    }

    // Retrieve validated data as an array
    $validatedData = $validator->validated();

    // Pass only validated data to the service
    $OrderCancellation = $this->service->store($validatedData);

            if ($OrderCancellation instanceof JsonResponse) {
                return $OrderCancellation;
            }

            return $this->returnJSON($OrderCancellation, __('message.OrderCancellation has been created successfully'));
    }

    /**
     * Display the specified OrderCancellation.
     */
    public function show($id)
    {

        $OrderCancellation = $this->service->show($id);

        if ($OrderCancellation instanceof JsonResponse) {
            return $OrderCancellation;
        }

        return $this->returnJSON($OrderCancellation, __('message.OrderCancellation has been created successfully'));

    }

    /**
     * Update the specified OrderCancellation in storage.
     */
    public function update(UpdateOrderCancellationRequest $request, OrderCancellation $OrderCancellation)
    {
            $OrderCancellation = $this->service->update($request,$OrderCancellation);

            if ($OrderCancellation instanceof JsonResponse) {
                return $OrderCancellation;
            }

            return $this->returnJSON($OrderCancellation, __('message.OrderCancellation has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $OrderCancellation = $this->service->destroy($id);

        if ($OrderCancellation instanceof JsonResponse) {
            return $OrderCancellation;
        }

        return $this->returnJSON($OrderCancellation, __('message.OrderCancellation has been deleted successfully'));
    }

    public function restore($id)
    {
        $OrderCancellation = $this->service->restore($id);

        if ($OrderCancellation instanceof JsonResponse) {
            return $OrderCancellation;
        }

        return $this->returnJSON($OrderCancellation, __('message.OrderCancellation has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $OrderCancellation = $this->service->forceDelete($id);

        if ($OrderCancellation instanceof JsonResponse) {
            return $OrderCancellation;
        }

        return $this->returnJSON($OrderCancellation, __('message.OrderCancellation has been force deleted successfully'));
    }


}
