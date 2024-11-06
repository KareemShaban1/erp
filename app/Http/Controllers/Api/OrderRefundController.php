<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderRefund;
use App\Services\API\OrderRefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderRefundController extends Controller
{
    protected $service;

    public function __construct(OrderRefundService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        $orderRefunds = $this->service->list($request);

        if ($orderRefunds instanceof JsonResponse) {
            return $orderRefunds;
        }

        return $orderRefunds->additional([
            'code' => 200,
            'status' => 'success',
            'message' => __('message.Order Cancellation have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created OrderRefund in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
            'order_id' => 'required|exists:orders,id',
            'order_item_ids' => 'required|array',
            'order_item_ids.*.order_item_id' => 'required|exists:order_items,id',
            'order_item_ids.*.quantity' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            // Get the first validation error with field name
            $errors = $validator->errors()->toArray();
            $firstErrorField = array_key_first($errors);
            $firstErrorMessage = $errors[$firstErrorField][0];
            
            // Return the error with field name and message in the desired format
            return response()->json([
                'message' => "{$firstErrorField}: {$firstErrorMessage}"
            ], 422);
        }
        
        // Pass only validated data to the service
        $validatedData = $validator->validated();
        $orderRefund = $this->service->store($validatedData);
    
        // Check if the service returned a JSON response (for error handling)
        if ($orderRefund instanceof JsonResponse) {
            return $orderRefund;
        }
    
        return $this->returnJSON($orderRefund, __('message.OrderRefund has been created successfully'));
    }
    

    /**
     * Display the specified OrderRefund.
     */
    public function show($id)
    {

        $orderRefund = $this->service->show($id);

        if ($orderRefund instanceof JsonResponse) {
            return $orderRefund;
        }

        return $this->returnJSON($orderRefund, __('message.OrderRefund has been created successfully'));

    }

    /**
     * Update the specified OrderRefund in storage.
     */
    public function update(Request $request, OrderRefund $orderRefund)
    {
        $orderRefund = $this->service->update($request, $orderRefund);

        if ($orderRefund instanceof JsonResponse) {
            return $orderRefund;
        }

        return $this->returnJSON($orderRefund, __('message.OrderRefund has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $orderRefund = $this->service->destroy($id);

        if ($orderRefund instanceof JsonResponse) {
            return $orderRefund;
        }

        return $this->returnJSON($orderRefund, __('message.OrderRefund has been deleted successfully'));
    }

}
