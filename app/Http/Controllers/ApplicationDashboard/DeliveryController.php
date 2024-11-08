<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{


          public function getAvailableDeliveries($orderId)
          {
                    $order = Order::where('id',$orderId)->first();
                    // Retrieve available deliveries and eager load the contact relationship
                    $deliveries = Delivery::with('contact')
                              ->where('business_location_id', $order->business_location_id)
                              // Uncomment the where clause if you want to filter by availability status
                              ->where('status', 'available')
                              ->get();

                    // Format the data to include contact name
                    $formattedDeliveries = $deliveries->map(function ($delivery) {
                              return [
                                        'id' => $delivery->id,
                                        'name' => $delivery->contact->name ?? 'N/A', // Fallback to 'N/A' if no contact name
                              ];
                    });

                    return response()->json([
                              'success' => true,
                              'deliveries' => $formattedDeliveries,
                    ]);
          }


          public function assignDelivery(Request $request)
          {
              $deliveryId = $request->input('delivery_id');
              $orderId = $request->input('order_id');

          
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
          


}