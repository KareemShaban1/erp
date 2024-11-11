<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use Datatables;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{


    public function getAvailableDeliveries($orderId)
    {
        $order = Order::where('id', $orderId)->first();
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

    public function allDeliveries()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
    
            // Load orders and related deliveries with their data
            $deliveries = Delivery::with(['contact'])
                ->whereHas('contact', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                });
    
            return Datatables::of($deliveries)
                ->addColumn('id', function ($row) {
                    return $row->id;
                })
                ->addColumn('delivery_name', function ($row) {
                    return $row->contact->name ?? '';
                })
                ->addColumn('action', function ($row) {
                    // Add a button to redirect to orderDeliveries with delivery_id
                    $url = route('order.deliveries', ['delivery_id' => $row->id]);
                    return '<a href="' . $url . '" class="btn btn-primary">View Orders</a>';
                })
                ->rawColumns(['action']) // Make sure the 'action' column is rendered as HTML
                ->make(true);
        }
    
        return view('applicationDashboard.pages.deliveries.index');
    }
    

    public function orderDeliveries($delivery_id = null)
{

    if (request()->ajax()) {
        $business_id = request()->session()->get('user.business_id');

        // Load orders and related deliveries with their data
        $ordersDeliveries = DeliveryOrder::with(['order.client.contact', 'delivery.contact'])
            ->whereHas('delivery.contact', function ($query) use ($business_id) {
                $query->where('business_id', $business_id);
            });

        // If delivery_id is provided, filter the data based on the delivery_id
        if (!empty($delivery_id)) {
            $ordersDeliveries->where('delivery_id', $delivery_id);
        }

        return Datatables::of($ordersDeliveries)
            ->addColumn('id', function ($row) {
                return $row->id;
            })
            ->addColumn('delivery_name', function ($row) {
                return $row->delivery->contact->name ?? '';
            })
            ->addColumn('client_name', function ($row) {
                return $row->order->client->contact->name ?? '';
            })
            ->make(true);
    }
    return view('applicationDashboard.pages.orderDeliveries.index', compact('delivery_id'));

    // return view('applicationDashboard.pages.orderDeliveries.index');
}



}