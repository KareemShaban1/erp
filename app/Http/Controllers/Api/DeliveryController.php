<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Delivery\DeliveryCollection;
use App\Http\Resources\Delivery\DeliveryResource;
use App\Http\Resources\Order\OrderCollection;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{

          public function getNotAssignedOrders()
          {
                    $delivery = Delivery::where('id', Auth::user()->id)->first();

                    if (!$delivery) {
                              return response()->json(['message' => 'Delivery user not found'], 404);
                    }

                    // Retrieve all order IDs already assigned to the delivery user in DeliveryOrder
                    $assignedOrderIds = DeliveryOrder::where('delivery_id', $delivery->id)
                              ->pluck('order_id'); // Assuming there is an 'order_id' column in DeliveryOrder

                    // Get orders that belong to the delivery's business location but are not assigned in DeliveryOrder
                    $orders = Order::
                              where('order_status', 'processing')->
                              where('business_location_id', $delivery->business_location_id)
                              ->whereNotIn('id', $assignedOrderIds)
                              ->get();

                    if ($orders->isEmpty()) {
                              return $this->returnJSON([],'No unassigned orders found for your location');

                            }

            return $this->returnJSON(new OrderCollection($orders),'Unassigned orders for your location');
                    // return ;
          }
          public function getAssignedOrders()
          {
                    $delivery = Delivery::where('id', Auth::user()->id)->first();

                    if (!$delivery) {
                              return response()->json(['message' => 'Delivery user not found'], 404);
                    }

                    // Retrieve assigned orders based on the delivery ID in DeliveryOrder
                    $assignedOrders = Order::
                              where('order_status', 'processing')->
                              whereHas('deliveries', function ($query) use ($delivery) {
                                        $query->where('delivery_id', $delivery->id);
                              })->get();

                    if ($assignedOrders->isEmpty()) {
                        return $this->returnJSON([],'No assigned orders found for you');
                    }

                    return $this->returnJSON(new OrderCollection($assignedOrders),'Assigned orders found for you');

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
                  return $this->returnJSON([],'No assigned orders found for you');
              }
          
              return $this->returnJSON(new OrderCollection($assignedOrders),'All orders found for you');

            //   return ;
          }
          

          public function assignDelivery(Request $request)
          {
                    $request->validate([
                              'order_id' => 'required|exists:orders,id',
                    ]);

                    $deliveryId = Auth::user()->id;
                    $orderId = $request->order_id;


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

                    return response()->json([
                              'success' => true,
                              'message' => 'Delivery assigned successfully to the order.',
                    ]);
          }

          public function changeOrderStatus($orderId)
          {
              // Define allowed statuses
              $validStatuses = ['pending', 'processing', 'shipped', 'cancelled', 'completed'];
          
              // Retrieve and validate the input status
              $status = request()->input('order_status');
          
              if (!in_array($status, $validStatuses)) {
                  return response()->json([
                      'success' => false,
                      'message' => 'Invalid status provided.',
                  ], 400);
              }
          
              // Find the order or return 404 if not found
              $order = Order::findOrFail($orderId);
              
              // Update the order status
              $order->order_status = $status;
              $order->save();
          
              // Check if an OrderTracking record already exists for the order; if not, create one
              $orderTracking = OrderTracking::firstOrNew(['order_id' => $order->id]);
          
              // Update the appropriate timestamp in the OrderTracking record based on the status
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
                  case 'cancelled':
                      $orderTracking->cancelled_at = now();
                      break;
                  case 'completed':
                      $orderTracking->completed_at = now();
                      break;
              }
          
              // Save the order tracking record
              $orderTracking->save();
          
              return response()->json(['success' => true, 'message' => 'Order status updated successfully.']);
          }
          

}