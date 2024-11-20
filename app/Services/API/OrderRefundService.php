<?php

namespace App\Services\API;

use App\Http\Resources\OrderRefund\OrderRefundCollection;
use App\Http\Resources\OrderRefund\OrderRefundResource;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Services\BaseService;
use App\Services\FirebaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderRefundService extends BaseService
{
    /**
     * Get all brands with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = OrderRefund::query();

            $query = $this->withTrashed($query, $request);

            $orderRefunds = $this->withPagination($query, $request);

            return (new OrderRefundCollection($orderRefunds))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing brands'));
        }
    }

    public function show($id) {

        try {
            $orderRefund = OrderRefund::businessId()->find($id);

            if(!$orderRefund) {
                return null;
            }
            return $orderRefund;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing OrderRefund'));
        }
    }

    /**
     * Create a new OrderRefund.
     */
    public function store($data)
    {
        try {
            // Add necessary data before creating the OrderRefund
            $data['client_id'] = Auth::id();
            $data['status'] = 'requested';
            $data['requested_at'] = now();
            $data['reason'] = $data['reason'] ?? null;
    
            // Loop through each order item to create refund entries if needed
            $orderRefunds = [];
            foreach ($data['order_item_ids'] as $orderItem) {
                $refundData = [
                    'order_id' => $data['order_id'],
                    'order_item_id' => $orderItem['order_item_id'],
                    'amount' => $orderItem['quantity'],
                    'client_id' => $data['client_id'],
                    'status' => $data['status'],
                    'requested_at' => $data['requested_at'],
                    'reason' => $data['reason'],
                ];
    
                // Create OrderRefund record for each item
                $orderRefund = OrderRefund::create($refundData);
                $orderRefunds[] = new OrderRefundResource($orderRefund);
            }
    
            // Return all created OrderRefunds
            return $orderRefunds;
    
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return $this->handleException($e, __('message.Error happened while storing OrderRefund'));
        }
    }
    

    /**
     * Update the specified OrderRefund.
     */
    public function update($request,$orderRefund)
    {
        try {

        // Validate the request data
        $data = $request->validated();

        $orderRefund->update($data);

        return new OrderRefundResource($orderRefund);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating OrderRefund'));
    }
    }

    public function destroy($id)
    {
        try {

            $orderRefund = OrderRefund::find($id);

            if(!$orderRefund) {
                return null;
            }
            $orderRefund->delete();
            return $orderRefund;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting OrderRefund'));
        }
    }


}
