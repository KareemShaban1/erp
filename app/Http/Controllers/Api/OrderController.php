<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Order;
use App\Services\API\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $service;

    public function __construct(OrderService $service)
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
     * Store a newly created Order in storage.
     */
    public function store()
    {
            $order = $this->service->store();

            if ($order instanceof JsonResponse) {
                return $order;
            }

            return $this->returnJSON($order, __('message.Order has been created successfully'));
    }

    /**
     * Display the specified Order.
     */
    public function show($id)
    {

        $order = $this->service->show($id);

        if ($order instanceof JsonResponse) {
            return $order;
        }

        return $this->returnJSON($order, __('message.Order has been created successfully'));

    }

    /**
     * Update the specified Order in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
            $order = $this->service->update($request,$order);

            if ($order instanceof JsonResponse) {
                return $order;
            }

            return $this->returnJSON($order, __('message.Order has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $order = $this->service->destroy($id);

        if ($order instanceof JsonResponse) {
            return $order;
        }

        return $this->returnJSON($order, __('message.Order has been deleted successfully'));
    }

    public function restore($id)
    {
        $order = $this->service->restore($id);

        if ($order instanceof JsonResponse) {
            return $order;
        }

        return $this->returnJSON($order, __('message.Order has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $order = $this->service->forceDelete($id);

        if ($order instanceof JsonResponse) {
            return $order;
        }

        return $this->returnJSON($order, __('message.Order has been force deleted successfully'));
    }

    public function checkQuantityAndLocation()
    {
            $response = $this->service->checkQuantityAndLocation();

            if ($response instanceof JsonResponse) {
                return $response;
            }
            return $response;

    }
    
}
