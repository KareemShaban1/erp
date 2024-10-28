<?php

namespace App\Services\API;

use App\Http\Resources\OrderTracking\OrderTrackingCollection;
use App\Http\Resources\OrderTracking\OrderTrackingResource;
use App\Models\OrderTracking;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderTrackingService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all OrderTrackings with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = OrderTracking::query();

            $query = $this->withTrashed($query, $request);

            $orderTracking = $this->withPagination($query, $request);

            return (new OrderTrackingCollection($orderTracking))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing OrderTrackings'));
        }
    }

    public function show($id) {

        try {
            $orderTracking = OrderTracking::businessId()->find($id);

            if(!$orderTracking) {
                return null;
            }
            return $orderTracking;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing OrderTracking'));
        }
    }

    /**
     * Create a new OrderTracking.
     */
    public function store($order, $status)
{
    try {
        // Create an empty OrderTracking instance
        $orderTracking = new OrderTracking();
        $orderTracking->order_id = $order->id;

        // Set the tracking status timestamp based on the status provided
        switch ($status) {
            case 'pending':
                $orderTracking->pending_at = now();
                break;
            case 'processing':
                $orderTracking->processing_at = now();
                break;
            case 'shipped':
                $orderTracking->shipped_at = now();
                break;
            case 'canceled':
                $orderTracking->canceled_at = now();
                break;
            case 'declined':
                $orderTracking->declined_at = now();
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        // Save the OrderTracking record
        $orderTracking->save();

        // Return the created OrderTracking resource
        return new OrderTrackingResource($orderTracking);

    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing OrderTracking'));
    }
}


    /**
     * Update the specified OrderTracking.
     */
    public function update(OrderTracking $orderTracking, $status)
{
    try {
        // Update the tracking status timestamp based on the status provided
        switch ($status) {
            case 'pending':
                $orderTracking->pending_at = now();
                break;
            case 'processing':
                $orderTracking->processing_at = now();
                break;
            case 'shipped':
                $orderTracking->shipped_at = now();
                break;
            case 'canceled':
                $orderTracking->canceled_at = now();
                break;
            case 'declined':
                $orderTracking->declined_at = now();
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        // Save the updated OrderTracking record
        $orderTracking->save();

        // Return the updated OrderTracking resource
        return new OrderTrackingResource($orderTracking);

    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating OrderTracking'));
    }
}


    public function destroy($id)
    {
        try {

            $orderTracking = OrderTracking::find($id);

            if(!$orderTracking) {
                return null;
            }
            $orderTracking->delete();
            return $orderTracking;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting OrderTracking'));
        }
    }

    public function restore($id)
    {
        try {
            $orderTracking = OrderTracking::withTrashed()->findOrFail($id);
            $orderTracking->restore();
            return new OrderTrackingResource($orderTracking);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring OrderTracking'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $orderTracking = OrderTracking::withTrashed()
                ->findOrFail($id);

            $orderTracking->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting OrderTracking'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = OrderTracking::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                OrderTracking::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = OrderTracking::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                OrderTracking::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting OrderTrackings'));
        }
    }
}
