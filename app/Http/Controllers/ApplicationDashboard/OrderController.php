<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\BusinessLocation;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderTracking;
use App\Services\FirebaseService;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{

    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('orders.view')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $status = request()->get('status', 'all'); // Default to 'all' if not provided
            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');
            $search = request()->get('search')['value'];
            $businessLocation = request()->get('business_location');
            $deliveryName = request()->get('delivery_name');
            $paymentStatus = request()->get('payment_status', 'all');

            // Validate status
            $validStatuses = ['all', 'pending', 'processing', 'shipped', 'cancelled', 'completed'];
            if (!in_array($status, $validStatuses)) {
                $status = 'all';
            }

            // Fetch filtered data
            return $this->fetchOrders($status, $startDate, $endDate, $search, $businessLocation, $deliveryName, $paymentStatus);
        }

        $business_locations = BusinessLocation::BusinessId()->active()->select('id', 'name')->get();

        return view('applicationDashboard.pages.orders.index', compact('business_locations'));
    }

    /**
     * Fetch order refunds based on filters.
     */
    private function fetchOrders($status, $startDate = null, $endDate = null, $search = null, $businessLocation = null, $deliveryName = null, $paymentStatus = null)
    {
        $business_id = request()->session()->get('user.business_id');
        $user_locations = Auth::user()->permitted_locations();

        $query = Order::with(['client.contact', 'businessLocation'])
            ->select([
                'orders.id',
                'orders.number',
                'orders.order_type',
                'orders.client_id',
                'orders.business_location_id',
                'orders.payment_method',
                'orders.order_status',
                'orders.payment_status',
                'orders.shipping_cost',
                'orders.sub_total',
                'orders.total',
                'orders.created_at'
            ])
            ->where('orders.order_type', 'order')
            ->latest();

        // Apply status filter
        if ($status !== 'all') {
            $query->where('orders.order_status', $status);
        }

        if ($businessLocation) {
            $query->where('orders.business_location_id', $businessLocation);
        }

        if ($paymentStatus !== 'all') {
            $query->where('orders.payment_status', $paymentStatus);
        }

        if ($user_locations !== "all") {
            $query->where(function ($query) use ($user_locations) {
                $query->whereIn('orders.business_location_id', $user_locations);
            });
        }

        if ($deliveryName) {
            $query->whereHas('deliveries.contact', function ($query) use ($deliveryName) {
                $query->where('contacts.name', 'like', "%{$deliveryName}%");
            });
        }

        // Apply date filter
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $query->whereDate('orders.created_at', $startDate);
            } else {
                $endDate = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('orders.created_at', [$startDate, $endDate]);
            }
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('orders.id', 'like', "%{$search}%")
                    ->orWhere('orders.number', 'like', "%{$search}%")
                    ->orWhereHas('client.contact', function ($query) use ($search) {
                        $query->where('contacts.name', 'like', "%{$search}%")
                            ->orWhere('contacts.mobile', 'like', "%{$search}%");
                    });
            });
        }


        return $this->formatDatatableResponse($query);
    }

    /**
     * Format the response for DataTables.
     */
    private function formatDatatableResponse($query)
    {

        return Datatables::of($query)
            ->addColumn('business_location_name', function ($order) {
                if ($order->businessLocation) {
                    return $order->businessLocation->name ?? 'N/A';
                }
            })
            ->addColumn('client_contact_name', function ($order) {
                try {
                    if ($order->client && $order->client->contact) {
                        return $order->client->contact->name ?? 'N/A';
                    }
                    return 'N/A';
                } catch (\Exception $e) {
                    \Log::error('Error getting client contact name: ' . $e->getMessage());
                    return 'Error';
                }
            })
            ->addColumn('client_contact_mobile', function ($order) {
                try {
                    if ($order->client && $order->client->contact) {
                        return $order->client->contact->mobile ?? 'N/A';
                    }
                    return 'N/A';
                } catch (\Exception $e) {
                    \Log::error('Error getting client contact mobile: ' . $e->getMessage());
                    return 'Error';
                }
            })
            ->filterColumn('client_contact_name', function ($query, $keyword) {
                $query->whereHas('client', function ($query) use ($keyword) {
                    $query->whereHas('contact', function ($query) use ($keyword) {
                        $query->where('contacts.name', 'like', "%{$keyword}%");
                    });
                });
            })
            ->filterColumn('client_contact_mobile', function ($query, $keyword) {
                $query->whereHas('client', function ($query) use ($keyword) {
                    $query->whereHas('contact', function ($query) use ($keyword) {
                        $query->where('contacts.mobile', 'like', "%{$keyword}%");
                    });
                });
            })
            ->addColumn('has_delivery', function ($order) {
                return $order->has_delivery; // Add the delivery status here
            })
            ->addColumn('delivery_name', function ($order) {
                if ($order->has_delivery) {
                    // Assuming `deliveries` is a relationship on the Order model
                    return $order->deliveries->pluck('contact.name')->implode(', ') ?: __('lang_v1.delivery_assigned');
                }
            })
            ->make(true);
    }

    public function changeOrderStatus($orderId)
    {
        if (!auth()->user()->can('orders.changeStatus')) {
            abort(403, 'Unauthorized action.');
        }
        $status = request()->input('order_status');

        $order = Order::findOrFail($orderId);
        $order->order_status = $status;
        $order->save();

        // Check if an OrderTracking already exists for the order
        $orderTracking = OrderTracking::firstOrNew(['order_id' => $order->id]);

        $deliveryOrder = DeliveryOrder::where('order_id', $orderId)->first();

        $delivery = Delivery::find($deliveryOrder->delivery_id);

        // Set the tracking status timestamp based on the status provided
        switch ($status) {
            case 'pending':
                $orderTracking->pending_at = now();
                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status' => 'pending']);
                break;
            case 'processing':
                $orderTracking->processing_at = now();
                \Log::info('data',[
                    $order->client->id,
                    $order->client->fcm_token,
                ]);
                // Send and store push notification
                app(FirebaseService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Status Changed',
                    'Your order has been processed successfully.',
                    [
                        'order_id' => $order->id,
                        'status' => $order->status
                    ]
                );
                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status' => 'processing']);
                break;
            case 'shipped':
                $this->updateDeliveryBalance($order, $delivery);
                // Send and store push notification
                app(FirebaseService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Status Changed',
                    'Your order has been shipped successfully.',
                    [
                        'order_id' => $order->id,
                        'status' => $order->status
                    ]
                );
                $orderTracking->shipped_at = now();
                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status' => 'shipped']);
                break;
            case 'cancelled':
                $orderTracking->cancelled_at = now();
                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status' => 'cancelled']);
                break;
            case 'completed':
                $orderTracking->completed_at = now();
                // Send and store push notification
                app(FirebaseService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Status Changed',
                    'Your order has been completed successfully.',
                    [
                        'order_id' => $order->id,
                        'status' => $order->status
                    ]
                );
                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status' => 'completed']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        // Save the order tracking record (it will either update or create)
        $orderTracking->save();

        return response()->json(['success' => true, 'message' => 'Order status updated successfully.']);
    }

    public function changePaymentStatus($orderId)
    {
        if (!auth()->user()->can('orders.changePayment')) {
            abort(403, 'Unauthorized action.');
        }
        $status = request()->input('payment_status'); // Retrieve status from the request

        $order = Order::findOrFail($orderId);
        $order->payment_status = $status;
        $order->save();
        $deliveryOrder = DeliveryOrder::where('order_id', $orderId)->first();

        $delivery = Delivery::find($deliveryOrder->delivery_id);

        if ($delivery && $delivery->contact) {
            $delivery->contact->balance += $order->total;
            $delivery->contact->save();
        }

        switch ($status) {
            case 'pending':
                $this->moduleUtil->activityLog($order, 'change_payment_status', null, ['order_number' => $order->number, 'status' => 'pending']);
                break;
            case 'paid':
                $this->moduleUtil->activityLog($order, 'change_payment_status', null, ['order_number' => $order->number, 'status' => 'paid']);
                break;
            case 'failed':
                $this->moduleUtil->activityLog($order, 'change_payment_status', null, ['order_number' => $order->number, 'status' => 'failed']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        return response()->json(['success' => true, 'message' => 'Order Payment status updated successfully.']);
    }

    public function getOrderDetails($orderId)
    {
        // Fetch activity logs related to the order
        $activityLogs = Activity::with(['subject'])
            ->leftJoin('users as u', 'u.id', '=', 'activity_log.causer_id')
            ->leftJoin('clients as c', 'c.id', '=', 'activity_log.causer_id')
            ->leftJoin('deliveries as d', 'd.id', '=', 'activity_log.causer_id')
            ->leftJoin('contacts as contact', function ($join) {
                $join->on('contact.id', '=', 'c.contact_id')
                    ->orOn('contact.id', '=', 'd.contact_id');
            })
            ->where('subject_type', 'App\Models\Order')
            ->where('subject_id', $orderId)
            ->select(
                'activity_log.*',
                DB::raw("
            CASE 
                WHEN u.id IS NOT NULL THEN CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''), ' (user)')
                WHEN c.id IS NOT NULL THEN CONCAT(COALESCE(contact.name, ''), ' (client)')
                WHEN d.id IS NOT NULL THEN CONCAT(COALESCE(contact.name, ''), ' (delivery)')
                ELSE 'Unknown'
            END as created_by
        ")
            )
            ->get();

        // Fetch the order along with related data
        $order = Order::with([
            'client.contact',
            'businessLocation',
            'orderItems',
            'delivery',
            'transaction'
        ])->find($orderId);

        if ($order) {
            // Iterate through each order item and check for refund details
            foreach ($order->orderItems as $item) {
                // Check if there are any records in the order_refund table for this order item
                $refund = OrderRefund::where('order_item_id', $item->id)->get();

                $refund_amount = $refund->sum('amount') ?? 0;
                // Calculate the difference between the order item quantity and the refunded amount
                $item->remaining_quantity = $item->quantity - $refund_amount;
            }

            // Return the order details and activity logs
            return response()->json([
                'success' => true,
                'order' => $order,
                'activityLogs' => $activityLogs,
            ]);
        }

        // If the order is not found
        return response()->json([
            'success' => false,
            'message' => 'Order not found'
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
