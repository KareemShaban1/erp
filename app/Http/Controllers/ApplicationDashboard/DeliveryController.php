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
use Carbon\Carbon;

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
            ->whereHas('contact',function($query){
                $query->where('contact_status','active');
            })
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

        $this->moduleUtil->activityLog($order, 'assign_delivery', null, ['order_number' => $order->number, 'status' => 'delivery_assigned', 'delivery_name' => $delivery->contact->name]);

        // Send and store push notification
        app(FirebaseDeliveryService::class)->sendAndStoreNotification(
            $delivery_order->delivery->id,
            $delivery_order->delivery->fcm_token,
            'Order Assigned to you',
            'There is an order assigned to you (Order ID: #' . $order->id . ').',
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
                ->addColumn('update_delivery_balance', function ($row) {
                    $url = route('deliveries.updateBalance', ['delivery_id' => $row->id]);
                    return '<a href="' . $url . '" class="btn btn-danger">' . __('lang_v1.update_delivery_balance') . '</a>';
                })
                ->addColumn('action', function ($row) {
                    $url = route('order.deliveries', ['delivery_id' => $row->id]);
                    return '<a href="' . $url . '" class="btn btn-primary">' . __('lang_v1.view_orders') . '</a>';
                })
                ->rawColumns(['update_delivery_balance', 'action'])
                ->make(true);
        }

        return view('applicationDashboard.pages.deliveries.index');
    }


    public function UpdateDeliveryBalance($delivery_id)
    {

        $delivery = Delivery::find($delivery_id);
        if ($delivery) {
            $delivery->contact->balance = 0;
            $delivery->contact->save();
            $delivery->save();
        }
        return view('applicationDashboard.pages.deliveries.index');

    }



    public function orderDeliveries(Request $request)
    {
        if (!auth()->user()->can('deliveries.orders')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $delivery_id = $request->query('delivery_id');
        $search = $request->get('search')['value'] ?? null;
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        if ($request->ajax()) {
            $query = DeliveryOrder::with(['order', 'order.client.contact', 'delivery.contact'])
                ->whereHas('delivery.contact', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                });

            if (!empty($delivery_id)) {
                $query->where('delivery_id', $delivery_id);
            }

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereHas('order', function ($query) use ($start_date, $end_date) {
                    $query->whereBetween('created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                });
            }

            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($query) use ($search) {
                            $query->where('number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('order.client.contact', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        });
                });
            }

            // Calculate total order sum
            $total_order_sum = (clone $query)->get()->sum(function ($order) {
                return $order->order->total ?? 0;
            });
            

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
                ->addColumn('order_total', function ($row) {
                    return number_format($row->order->total ?? 0, 2);
                })
                ->with('total_order_sum', number_format($total_order_sum, 2)) // Pass total order sum to frontend
                ->rawColumns(['id', 'delivery_name', 'client_name'])
                ->make(true);
        }

        return view('applicationDashboard.pages.orderDeliveries.index', compact('delivery_id'));
    }



    public function getDeliveryStatistics(Request $request)
    {
        if (!auth()->user()->can('deliveries.orders')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $delivery_id = $request->query('delivery_id'); // Get delivery_id from query parameters
        $start_date = $request->query('start_date'); // Get start_date from query parameters
        $end_date = $request->query('end_date'); // Get end_date from query parameters

        // Base query for delivery orders
        $baseQuery = DeliveryOrder::with(['order', 'order.client.contact', 'delivery.contact'])
            ->whereHas('delivery.contact', function ($query) use ($business_id) {
                $query->where('business_id', $business_id);
            });

        // Filter by delivery_id if provided
        if (!empty($delivery_id)) {
            $baseQuery->where('delivery_id', $delivery_id);
        }
        if ($start_date && $end_date) {
            $end_date = $start_date === $end_date
                ? Carbon::parse($end_date)->endOfDay()
                : Carbon::parse($end_date)->endOfDay();

            $baseQuery->whereHas('order', function ($q) use ($start_date, $end_date) {
                if ($start_date === $end_date) {
                    $q->whereDate('created_at', $start_date);
                } else {
                    $q->whereBetween('created_at', [$start_date, $end_date]);
                }
            });
        }


        // Get total orders count and total amount
        $totalOrdersCount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('order_type', 'order');
        })->count();
        $totalOrdersAmount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('order_type', 'order');
        })->with('order')->get()->sum(function ($deliveryOrder) {
            return $deliveryOrder->order->total ?? 0;
        });


        // Get orders with type 'order_refund'
        $refundOrdersCount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('order_type', 'order_refund');
        })->count();
        $refundOrdersAmount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('order_type', 'order_refund');
        })->with('order')->get()->sum(function ($deliveryOrder) {
            return $deliveryOrder->order->total ?? 0;
        });

        // Get orders with type 'order_transfer'
        $transferOrdersCount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('order_type', 'order_transfer');
        })->count();
        $transferOrdersAmount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('order_type', 'order_transfer');
        })->with('order')->get()->sum(function ($deliveryOrder) {
            return $deliveryOrder->order->total ?? 0;
        });

        // Get orders with cancellation status
        $cancelledOrdersCount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('order_status', 'cancelled');
        })->count();
        $cancelledOrdersAmount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('order_status', 'cancelled');
        })->with('order')->get()->sum(function ($deliveryOrder) {
            return $deliveryOrder->order->total ?? 0;
        });

        // Get paid and not paid orders
        $paidOrdersCount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('payment_status', 'paid')
                ->where('order_type', 'order');
        })->count();
        $paidOrdersAmount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('payment_status', 'paid')
                ->where('order_type', 'order');
        })->with('order')->get()->sum(function ($deliveryOrder) {
            return $deliveryOrder->order->total ?? 0;
        });

        $failedPayOrdersCount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('payment_status', 'failed')
                ->where('order_type', 'order');
        })->count();
        $failedPayOrdersAmount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('payment_status', 'failed')
                ->where('order_type', 'order');
        })->with('order')->get()->sum(function ($deliveryOrder) {
            return $deliveryOrder->order->total ?? 0;
        });

        $pendingPayOrdersCount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('payment_status', 'pending')
                ->where('order_type', 'order');
        })->count();
        $pendingPayOrdersAmount = (clone $baseQuery)->whereHas('order', function ($q) {
            $q->where('payment_status', 'pending')
                ->where('order_type', 'order');
        })->with('order')->get()->sum(function ($deliveryOrder) {
            return $deliveryOrder->order->total ?? 0;
        });


        // Calculate net total after removing refunded, cancelled, failed, and pending payment orders
        $netTotalAmount = $totalOrdersAmount - $refundOrdersAmount - $cancelledOrdersAmount - $failedPayOrdersAmount - $pendingPayOrdersAmount;
        // Return the statistics as JSON
        return response()->json([
            'success' => true,
            'data' => [
                'total_orders_count' => $totalOrdersCount,
                'total_orders_amount' => $totalOrdersAmount,
                'refund_orders_count' => $refundOrdersCount,
                'refund_orders_amount' => $refundOrdersAmount,
                'transfer_orders_count' => $transferOrdersCount,
                'transfer_orders_amount' => $transferOrdersAmount,
                'cancelled_orders_count' => $cancelledOrdersCount,
                'cancelled_orders_amount' => $cancelledOrdersAmount,
                'paid_orders_count' => $paidOrdersCount,
                'paid_orders_amount' => $paidOrdersAmount,
                'failed_paid_orders_count' => $failedPayOrdersCount,
                'failed_paid_orders_amount' => $failedPayOrdersAmount,
                'pending_paid_orders_count' => $pendingPayOrdersCount,
                'pending_paid_orders_amount' => $pendingPayOrdersAmount,
                'net_total_amount' => $netTotalAmount,
            ],
        ]);
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

                $this->moduleUtil->activityLog($order, 'change_payment_status', null, ['order_number' => $order->number, 'status' => 'paid']);

                break;
            case 'not_paid':
                $this->moduleUtil->activityLog($order, 'change_payment_status', null, ['order_number' => $order->number, 'status' => 'not_paid']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        $deliveryOrder->save();

        return response()->json(['success' => true, 'message' => 'Order status updated successfully.']);
    }

}