<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTracking;
use App\Services\API\OrderTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
   
    protected $service;

    public function __construct(OrderTrackingService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        $orderTracking = $this->service->list($request);

        if ($orderTracking instanceof JsonResponse) {
            return $orderTracking;
        }

        return $orderTracking->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Categories have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created OrderTracking in storage.
     */
    public function store(Order $order , $status)
    {
            $orderTracking = $this->service->store( $order , $status);

            if ($orderTracking instanceof JsonResponse) {
                return $orderTracking;
            }

            return $this->returnJSON($orderTracking, __('message.OrderTracking has been created successfully'));
    }

    /**
     * Display the specified OrderTracking.
     */
    public function show($id)
    {

        $orderTracking = $this->service->show($id);

        if ($orderTracking instanceof JsonResponse) {
            return $orderTracking;
        }

        return $this->returnJSON($orderTracking, __('message.OrderTracking has been created successfully'));

    }

    /**
     * Update the specified OrderTracking in storage.
     */
    public function update(OrderTracking $orderTracking,$status)
    {
            $orderTracking = $this->service->update($orderTracking,$status);

            if ($orderTracking instanceof JsonResponse) {
                return $orderTracking;
            }

            return $this->returnJSON($orderTracking, __('message.OrderTracking has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $orderTracking = $this->service->destroy($id);

        if ($orderTracking instanceof JsonResponse) {
            return $orderTracking;
        }

        return $this->returnJSON($orderTracking, __('message.OrderTracking has been deleted successfully'));
    }

    public function restore($id)
    {
        $orderTracking = $this->service->restore($id);

        if ($orderTracking instanceof JsonResponse) {
            return $orderTracking;
        }

        return $this->returnJSON($orderTracking, __('message.OrderTracking has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $orderTracking = $this->service->forceDelete($id);

        if ($orderTracking instanceof JsonResponse) {
            return $orderTracking;
        }

        return $this->returnJSON($orderTracking, __('message.OrderTracking has been force deleted successfully'));
    }

}
