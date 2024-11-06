<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Services\API\OrderItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    protected $service;

    public function __construct(OrderItemService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        $orderItemItems = $this->service->list($request);

        if ($orderItemItems instanceof JsonResponse) {
            return $orderItemItems;
        }

        return $orderItemItems->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Categories have been retrieved successfully'),
        ]);
    }

    /**
     * Store a newly created Order in storage.
     */
    public function store(Request $request)
    {
            $data = $request->validated();
            $orderItem = $this->service->store( $data);

            if ($orderItem instanceof JsonResponse) {
                return $orderItem;
            }

            return $this->returnJSON($orderItem, __('message.Order has been created successfully'));
    }

    /**
     * Display the specified Order.
     */
    public function show($id)
    {

        $orderItem = $this->service->show($id);

        if ($orderItem instanceof JsonResponse) {
            return $orderItem;
        }

        return $this->returnJSON($orderItem, __('message.Order has been created successfully'));

    }

    /**
     * Update the specified Order in storage.
     */
    public function update(Request $request, OrderItem $orderItem)
    {
            $orderItem = $this->service->update($request,$orderItem);

            if ($orderItem instanceof JsonResponse) {
                return $orderItem;
            }

            return $this->returnJSON($orderItem, __('message.Order has been updated successfully'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $orderItem = $this->service->destroy($id);

        if ($orderItem instanceof JsonResponse) {
            return $orderItem;
        }

        return $this->returnJSON($orderItem, __('message.Order has been deleted successfully'));
    }

    public function restore($id)
    {
        $orderItem = $this->service->restore($id);

        if ($orderItem instanceof JsonResponse) {
            return $orderItem;
        }

        return $this->returnJSON($orderItem, __('message.Order has been restored successfully'));
    }

    public function forceDelete($id)
    {
        $orderItem = $this->service->forceDelete($id);

        if ($orderItem instanceof JsonResponse) {
            return $orderItem;
        }

        return $this->returnJSON($orderItem, __('message.Order has been force deleted successfully'));
    }

    public function bulkDelete(Request $request)
    {

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id',
        ]);


        $orderItem = $this->service->bulkDelete($request->ids);

        if ($orderItem instanceof JsonResponse) {
            return $orderItem;
        }

        return $this->returnJSON($orderItem, __('message.Order has been deleted successfully.'));
    }
}
