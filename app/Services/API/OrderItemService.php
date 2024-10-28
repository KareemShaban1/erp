<?php

namespace App\Services\API;

use App\Http\Resources\OrderItem\OrderItemCollection;
use App\Http\Resources\OrderItem\OrderItemResource;
use App\Models\OrderItem;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderItemService extends BaseService
{
    use UploadFileTrait, HelperTrait;
    /**
     * Get all OrderItems with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = OrderItem::query();

            $query = $this->withTrashed($query, $request);

            $orderItems = $this->withPagination($query, $request);

            return (new OrderItemCollection($orderItems))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing OrderItems'));
        }
    }

    public function show($id) {

        try {
            $orderItem = OrderItem::businessId()->find($id);

            if(!$orderItem) {
                return null;
            }
            return $orderItem;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing OrderItem'));
        }
    }

    /**
     * Create a new OrderItem.
     */
    public function store($data)
    {

        try {

        // First, create the OrderItem without the image
        $orderItem = OrderItem::create($data);

        // Return the created OrderItem
        return new OrderItemResource($orderItem);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing OrderItem'));
    }
    }

    /**
     * Update the specified OrderItem.
     */
    public function update($request,$orderItem)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $orderItem->update($data);

        return new OrderItemResource($orderItem);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating OrderItem'));
    }
    }

    public function destroy($id)
    {
        try {

            $orderItem = OrderItem::find($id);

            if(!$orderItem) {
                return null;
            }
            $orderItem->delete();
            return $orderItem;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting OrderItem'));
        }
    }

    public function restore($id)
    {
        try {
            $orderItem = OrderItem::withTrashed()->findOrFail($id);
            $orderItem->restore();
            return new OrderItemResource($orderItem);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring OrderItem'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $orderItem = OrderItem::withTrashed()
                ->findOrFail($id);

            $orderItem->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting OrderItem'));
        }
    }


    public function bulkDelete(mixed $ids)
    {
        try {
            $trashedRecords = OrderItem::onlyTrashed()->whereIn('id', $ids)->get();

            if ($trashedRecords->isNotEmpty()) {
                OrderItem::whereIn('id', $trashedRecords->pluck('id'))->forceDelete();
            }

            $nonTrashedIds = OrderItem::whereIn('id', $ids)->get()->pluck('id');

            if ($nonTrashedIds->isNotEmpty()) {
                OrderItem::whereIn('id', $nonTrashedIds)->delete();
            }

            return $ids;
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting OrderItems'));
        }
    }
}
