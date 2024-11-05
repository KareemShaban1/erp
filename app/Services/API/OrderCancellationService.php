<?php

namespace App\Services\API;

use App\Http\Resources\OrderCancellation\OrderCancellationCollection;
use App\Http\Resources\OrderCancellation\OrderCancellationResource;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Services\BaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderCancellationService extends BaseService
{
    /**
     * Get all OrderCancellations with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = OrderCancellation::query();

            $query = $this->withTrashed($query, $request);

            $OrderCancellations = $this->withPagination($query, $request);

            return (new OrderCancellationCollection($OrderCancellations))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing OrderCancellations'));
        }
    }

    public function getAuthClientOrderCancellations(Request $request)
    {

        try {

            $query = OrderCancellation::where('client_id',Auth::user()->id);

            $query = $this->withTrashed($query, $request);

            $OrderCancellations = $this->withPagination($query, $request);

            return (new OrderCancellationCollection($OrderCancellations))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing OrderCancellations'));
        }
    }

    public function show($id) {

        try {
            $OrderCancellation = OrderCancellation::businessId()->find($id);

            if(!$OrderCancellation) {
                return null;
            }
            return $OrderCancellation;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing OrderCancellation'));
        }
    }

    /**
     * Create a new OrderCancellation.
     */
    public function store($data)
    {

        $data['client_id'] = Auth::user()->id;
        $data['status'] = 'requested';
        $data['requested_at'] = now();
        try {


        // First, create the OrderCancellation without the image
        $OrderCancellation = OrderCancellation::create($data);

        $order = Order::where('id', $data['order_id'])->first();
        $order->status = 'cancelled';
        $order->save();
        
        // Return the created OrderCancellation
        return new OrderCancellationResource($OrderCancellation);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while storing OrderCancellation'));
    }
    }

    /**
     * Update the specified OrderCancellation.
     */
    public function update($request,$OrderCancellation)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $OrderCancellation->update($data);

        return new OrderCancellationResource($OrderCancellation);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating OrderCancellation'));
    }
    }

    public function destroy($id)
    {
        try {

            $OrderCancellation = OrderCancellation::find($id);

            if(!$OrderCancellation) {
                return null;
            }
            $OrderCancellation->delete();
            return $OrderCancellation;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting OrderCancellation'));
        }
    }

    public function restore($id)
    {
        try {
            $OrderCancellation = OrderCancellation::withTrashed()->findOrFail($id);
            $OrderCancellation->restore();
            return new OrderCancellationResource($OrderCancellation);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring OrderCancellation'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $OrderCancellation = OrderCancellation::withTrashed()
                ->findOrFail($id);

            $OrderCancellation->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting OrderCancellation'));
        }
    }

}
