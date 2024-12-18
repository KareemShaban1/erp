<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Utils\ModuleUtil;
use Datatables;
use Illuminate\Http\Request;
use App\Services\FirebaseDeliveryService;

class DeliveryController extends Controller
{

    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

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
        $delivery_order = DeliveryOrder::create([
            'delivery_id' => $deliveryId,
            'order_id' => $orderId,
            'status' => 'assigned', // The status could be 'assigned' initially
            'assigned_at' => now(), // Timestamp of assignment
        ]);

        $this->moduleUtil->activityLog($order, 'assign_delivery', null, ['order_number' => $order->number,'status'=>'delivery_assigned', 'delivery_name'=> $delivery->contact->name]);

        // Send and store push notification
        app(FirebaseDeliveryService::class)->sendAndStoreNotification(
            $delivery_order->delivery->id,
            $delivery_order->delivery->fcm_token,
            'Order Assigned to you',
            'There is an order assigned to you.',
            [
                'order_id' => $order->id
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Delivery assigned successfully to the order.',
        ]);
    }

    public function allDeliveries()
    {
        if (!auth()->user()->can('deliveries.view_all')) {
            abort(403, 'Unauthorized action.');
        }
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
                    $url = route('order.deliveries', ['delivery_id' => $row->id]);
                    return '<a href="' . $url . '" class="btn btn-primary">' . __('lang_v1.view_orders') . '</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    
        return view('applicationDashboard.pages.deliveries.index');
    }
    


    public function orderDeliveries(Request $request)
    {
        if (!auth()->user()->can('deliveries.orders')) {
            abort(403, 'Unauthorized action.');
        }
    
        $business_id = $request->session()->get('user.business_id');
        $delivery_id = $request->query('delivery_id'); // Get delivery_id from query parameters
        $search = $request->get('search')['value'] ?? null;
    
        if ($request->ajax()) {
            // Load orders and related deliveries with their data
            $query = DeliveryOrder::with(['order','order.client.contact', 'delivery.contact'])
                ->whereHas('delivery.contact', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                });
    
            // Filter by delivery_id if provided
            if (!empty($delivery_id)) {
                $query->where('delivery_id', $delivery_id);
            }
    
            // Apply search filter if applicable
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'like', "%{$search}%") // Searching DeliveryOrder ID
                        ->orWhereHas('order', function ($query) use ($search) {
                            $query->where('number', 'like', "%{$search}%"); // Searching Order Number
                        })
                        ->orWhereHas('order.client.contact', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")  // Searching Client Name
                                ->orWhere('mobile', 'like', "%{$search}%"); // Searching Client Mobile
                        });
                });
            }
    
            return Datatables::of($query)
                ->addColumn('id', function ($row) {
                    return $row->id;
                })
                ->addColumn('delivery_name', function ($row) {
                    return $row->delivery->contact->name ?? 'N/A';
                })
                ->addColumn('client_name', function ($row) {
                    return $row->order->client->contact->name ?? 'N/A';
                })
                ->rawColumns(['id', 'delivery_name', 'client_name']) // Use raw columns if needed for HTML
                ->make(true);
        }
    
        // Pass delivery_id and other data to the view for non-AJAX requests
        return view('applicationDashboard.pages.orderDeliveries.index', compact('delivery_id'));
    }
    



    public function changePaymentStatus($orderId)
    {
        $status = request()->input('payment_status');

        $deliveryOrder = DeliveryOrder::findOrFail($orderId);
        $deliveryOrder->payment_status = $status;

        $order = Order::find($orderId);


        // Set the tracking status timestamp based on the status provided
        switch ($status) {
            case 'paid':
                $delivery = Delivery::find($deliveryOrder->delivery_id);
                if ($delivery && $delivery->contact) {
                    $delivery->contact->balance += $deliveryOrder->order->total;
                    $delivery->contact->save();
                }

                $deliveryOrder->paid_at = now();

                $this->moduleUtil->activityLog($order, 'change_payment_status', null, ['order_number' => $order->number, 'status'=>'paid']);

                break;
            case 'not_paid':
                $this->moduleUtil->activityLog($order, 'change_payment_status', null, ['order_number' => $order->number, 'status'=>'not_paid']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        $deliveryOrder->save();

        return response()->json(['success' => true, 'message' => 'Order status updated successfully.']);
    }

}