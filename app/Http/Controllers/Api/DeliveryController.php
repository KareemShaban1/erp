<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Delivery\DeliveryCollection;
use App\Http\Resources\Delivery\DeliveryResource;
use App\Http\Resources\Order\OrderCollection;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderTracking;
use App\Services\FirebaseService;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{

    protected $firebaseService;
    protected $moduleUtil;


    public function __construct(FirebaseService $firebaseService, ModuleUtil $moduleUtil)
    {
        $this->firebaseService = $firebaseService;
        $this->moduleUtil = $moduleUtil;

    }

    public function getNotAssignedOrders($orderType)
    {
        // Retrieve the authenticated delivery user
        $delivery = Delivery::find(Auth::user()->id);

        if (!$delivery) {
            return response()->json(['message' => 'Delivery user not found'], 404);
        }

        // Retrieve all order IDs already assigned to the delivery user in DeliveryOrder
        $assignedOrderIds = DeliveryOrder::where('delivery_id', $delivery->id)
            ->pluck('order_id');

        // Start building the query for unassigned orders
        $query = Order::where('order_status', 'processing')
            ->where('business_location_id', $delivery->business_location_id)
            ->whereNotIn('id', $assignedOrderIds);

        // Apply the order type filter if necessary
        if ($orderType !== 'all') {
            $query->where('order_type', $orderType);
        }

        // Execute the query
        $orders = $query->get();

        if ($orders->isEmpty()) {
            return $this->returnJSON([], 'No unassigned orders found for your location');
        }

        return $this->returnJSON(new OrderCollection($orders), 'Unassigned orders for your location');
    }

    public function getAssignedOrders($orderType)
    {
        $delivery = Delivery::where('id', Auth::user()->id)->first();

        if (!$delivery) {
            return response()->json(['message' => 'Delivery user not found'], 404);
        }

        // Retrieve assigned orders based on the delivery ID in DeliveryOrder
        $query = Order::
            where('order_status', 'processing')->
            whereHas('deliveries', function ($query) use ($delivery) {
                $query->where('delivery_id', $delivery->id);
            });

        // Apply the order type filter if necessary
        if ($orderType !== 'all') {
            $query->where('order_type', $orderType);
        }

        // Execute the query
        $assignedOrders = $query->get();

        if ($assignedOrders->isEmpty()) {
            return $this->returnJSON([], 'No assigned orders found for you');
        }

        return $this->returnJSON(new OrderCollection($assignedOrders), 'Assigned orders found for you');

        // return ;

    }


    public function getDeliveryOrders($status)
    {
        $delivery = Delivery::where('id', Auth::user()->id)->first();

        if (!$delivery) {
            return response()->json(['message' => 'Delivery user not found'], 404);
        }

        // Retrieve the status from the request, defaulting to 'all' if not provided
        //   $status = $request->input('status', 'all');

        // Retrieve assigned orders based on the delivery ID and status
        $assignedOrders = Order::whereHas('deliveries', function ($query) use ($delivery) {
            $query->where('delivery_id', $delivery->id);
        });

        // Apply status filter if specified and not 'all'
        if ($status !== 'all') {
            $assignedOrders->where('order_status', $status);
        }

        $assignedOrders = $assignedOrders->get();

        if ($assignedOrders->isEmpty()) {
            return $this->returnJSON([], 'No assigned orders found for you');
        }

        return $this->returnJSON(new OrderCollection($assignedOrders), 'All orders found for you');

        //   return ;
    }


    public function assignDelivery(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $deliveryId = Auth::user()->id;
        $orderId = $request->order_id;

        $order = Order::find($orderId);

        // Validate the delivery ID to ensure it exists and is available
        $delivery = Delivery::where('id', $deliveryId)
            //->where('status', 'available')  // You can uncomment this if you need to check for an available status
            ->first();

        if (!$delivery) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or unavailable delivery selected.',
            ], 400);
        }

        // Update the delivery status to 'assigned'
        $delivery->status = 'not_available';
        $delivery->save();

        // Insert a record into the delivery_orders table to log this assignment
        DeliveryOrder::create([
            'delivery_id' => $deliveryId,
            'order_id' => $orderId,
            'status' => 'assigned', // The status could be 'assigned' initially
            'assigned_at' => now(), // Timestamp of assignment
        ]);

        $this->moduleUtil->activityLog($order, 'assign_delivery', null, ['order_number' => $order->number, 'delivery_name'=> $delivery->contact->name]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery assigned successfully to the order.',
        ]);
    }

    public function changeOrderStatus($orderId)
    {
        // Define allowed statuses
        $validStatuses = ['shipped', 'completed'];

        // Retrieve and validate the input status
        $status = request()->input('order_status');
        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status provided.',
            ], 400);
        }

        $deliveryOrder = DeliveryOrder::where('order_id', $orderId)->first();

        // Find the order or return 404 if not found
        $order = Order::findOrFail($orderId);

        $delivery = Delivery::find($deliveryOrder->delivery_id);

        // Find the client
        $client = Client::find($order->client_id);
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }


        // Get or create the OrderTracking record for this order
        $orderTracking = OrderTracking::firstOrNew(['order_id' => $order->id]);



        // Update timestamps and handle specific status actions
        switch ($status) {
            case 'shipped':
                if ($order->status === 'shipped') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status is already shipped',
                    ], 404);
                }
                $orderTracking->shipped_at = now();

                // Update delivery contact balance
                $this->updateDeliveryBalance($order, $delivery);

                // Send and store push notification
                app(FirebaseService::class)->sendAndStoreNotification(
                    $client->id,
                    $client->fcm_token,
                    'Order Status Updated',
                    'Your order has been shipped successfully.',
                    ['order_id' => $order->id, 'status' => $status]
                );

                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status'=> 'shipped']);

                break;

            case 'completed':
                if ($order->status === 'completed') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status is already completed',
                    ], 404);
                }
                $orderTracking->completed_at = now();

                $delivery->status = 'available';
                $delivery->save();

                // Send and store push notification
                app(FirebaseService::class)->sendAndStoreNotification(
                    $client->id,
                    $client->fcm_token,
                    'Order Status Updated',
                    'Your order has been completed successfully.',
                    ['order_id' => $order->id, 'status' => $status]
                );

                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status'=> 'completed']);

                break;
        }

        // Update the order status
        $order->order_status = $status;

        // Save the tracking record
        $orderTracking->save();

        $order->save();


        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
        ]);
    }

    /**
     * Update the delivery contact balance based on the order total.
     *
     * @param Order $order
     * @return void
     */
    private function updateDeliveryBalance($order, $delivery)
    {
        Log::info($delivery);

        if ($delivery && $delivery->contact) {
            $delivery->contact->balance -= $order->total;
            $delivery->contact->save();
        }

        Log::info("balance updated");

    }



}