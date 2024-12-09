<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderRefund;
use App\Models\OrderTracking;
use App\Services\FirebaseService;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class OrderCancellationController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;


    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        if (!auth()->user()->can('orders_cancellation.view')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $status = request()->get('status', 'all'); // Default to 'all' if not provided
            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');
            $search = request()->get('search.value');

            // Validate status
            $validStatuses = ['all', 'requested', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                $status = 'all';
            }

            // Fetch filtered data
            return $this->fetchOrderCancellations($status, $startDate, $endDate, $search);
        }

        return view('applicationDashboard.pages.orderCancellations.index');
    }

    /**
     * Fetch order refunds based on filters.
     */
    private function fetchOrderCancellations($status, $startDate = null, $endDate = null, $search = null)
    {
        $user_locations = Auth::user()->permitted_locations();

        $query = OrderCancellation::with(['client.contact:id,name', 'order:id,number,order_status'])
            ->select(['id', 'order_id', 'client_id', 'status', 'created_at'])
            ->latest();

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Apply user locations filter
        if ($user_locations !== "all") {
            $query->whereHas('order', function ($q) use ($user_locations) {
                $q->whereIn('business_location_id', $user_locations);
            });
        }

        // Apply date filter
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                // Filter for a single day
                $query->whereDate('created_at', $startDate);
            } else {
                // Filter for a range of dates
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('id', 'like', "%$search%")
                    ->orWhere('status', 'like', "%$search%")
                    ->orWhereHas('order', function ($query) use ($search) {
                        $query->where('number', 'like', "%$search%");
                    })
                    ->orWhereHas('client.contact', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
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
            ->addColumn('client_contact_name', function ($orderCancellation) {
                return optional($orderCancellation->client->contact)->name ?? 'N/A';
            })
            ->addColumn('order_number', function ($orderCancellation) {
                return optional($orderCancellation->order)->number ?? 'N/A';
            })
            ->addColumn('order_status', function ($orderCancellation) {
                return optional($orderCancellation->order)->order_status ?? 'N/A';
            })
            ->make(true);
    }


    public function changeOrderCancellationStatus($orderCancellationId)
    {
        if (!auth()->user()->can('orders_cancellation.changeStatus')) {
            abort(403, 'Unauthorized action.');
        }
        $status = request()->input('status'); // Retrieve status from the request

        $orderCancellation = OrderCancellation::findOrFail($orderCancellationId);
        $orderCancellation->status = $status;

        $order = Order::where('id', $orderCancellation->order_id)->first();


        // Set the tracking status timestamp based on the status provided
        switch ($status) {
            case 'requested':
                $orderCancellation->requested_at = now();
                $this->moduleUtil->activityLog($orderCancellation, 'change_order_cancellation_status', null, ['order_number' => $order->number, 'status' => 'requested']);
                break;
            case 'approved':
                $orderCancellation->processed_at = now();
                $this->moduleUtil->activityLog($orderCancellation, 'change_order_cancellation_status', null, ['order_number' => $order->number, 'status' => 'approved']);
                break;
            case 'rejected':
                $this->moduleUtil->activityLog($orderCancellation, 'change_order_cancellation_status', null, ['order_number' => $order->number, 'status' => 'rejected']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        $orderCancellation->save();

        return response()->json(['success' => true, 'message' => 'Order Cancellation status updated successfully.']);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $orderCancellation = OrderCancellation::findOrFail($id);

        // Return data as JSON to be used in the modal
        return response()->json($orderCancellation);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('order_cancellation.update')) {
            abort(403, 'Unauthorized action.');
        }

        $orderCancellation = OrderCancellation::findOrFail($id);

        // Return data as JSON to be used in the modal
        return response()->json($orderCancellation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('order_cancellation.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['status', 'reason', 'admin_response']);

                $orderCancellation = OrderCancellation::findOrFail($id);
                // $orderCancellation->status = $input['status'];
                // Set the tracking status timestamp based on the status provided
                switch ($input['status']) {
                    case 'requested':
                        $orderCancellation->requested_at = now();
                        $this->moduleUtil->activityLog($orderCancellation, 'change_order_cancellation_status', null, ['order_number' => $order->number, 'status' => 'requested']);
                        break;
                    case 'approved':
                        $orderCancellation->processed_at = now();
                        $this->moduleUtil->activityLog($orderCancellation, 'change_order_cancellation_status', null, ['order_number' => $order->number, 'status' => 'approved']);
                        break;
                    case 'rejected':
                        $this->moduleUtil->activityLog($orderCancellation, 'change_order_cancellation_status', null, ['order_number' => $order->number, 'status' => 'rejected']);
                        break;
                    default:
                        throw new \InvalidArgumentException("Invalid status");
                }
                $orderCancellation->reason = $input['reason'];
                $orderCancellation->admin_response = $input['admin_response'];
                $order = Order::where('id', $orderCancellation->order_id)->first();

                if ($input['admin_response']) {
                    // Send and store push notification
                    app(FirebaseService::class)->sendAndStoreNotification(
                        $order->client->id,
                        $order->client->fcm_token,
                        'Order Cancellation Admin Response',
                        'Your order has been shipped successfully.',
                        [
                            'order_id' => $order->id,
                            'order_cancellation_id' => $orderCancellation->id,
                            'admin_response' => $input['admin_response']
                        ]
                    );

                    $this->moduleUtil->activityLog($orderCancellation, 'order_cancellation_admin_response', null, ['order_number' => $order->number, 'admin_response' => $input['admin_response']]);

                }

                $orderCancellation->save();

                return response()->json(['success' => true, 'message' => 'Order Cancellation updated successfully.']);

            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }


    public function getCancellationDetails($orderId)
    {
        $activityLogs = Activity::with(['subject'])
            ->leftJoin('users as u', 'u.id', '=', 'activity_log.causer_id')
            ->leftJoin('clients as c', 'c.id', '=', 'activity_log.causer_id')
            ->leftJoin('deliveries as d', 'd.id', '=', 'activity_log.causer_id')
            ->leftJoin('contacts as contact', function ($join) {
                $join->on('contact.id', '=', 'c.contact_id')
                    ->orOn('contact.id', '=', 'd.contact_id');
            })
            ->where('subject_type', 'App\Models\OrderCancellation')
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
        $orderCancellation = OrderCancellation::
            with([
                'order.client.contact',
                'order.businessLocation',
                'order.orderItems',
                'order.delivery'
            ])->find($orderId);

        if ($orderCancellation) {
            // Iterate through each order item and check for refund details
            foreach ($orderCancellation->order->orderItems as $item) {
                // Check if there are any records in the order_cancellation table for this order item
                $refund = OrderRefund::where('order_item_id', $item->id)->get();

                $refund_amount = $refund->sum('amount') ?? 0;
                // Calculate the difference between the order item quantity and the refunded amount
                $item->remaining_quantity = $item->quantity - $refund_amount;
            }

            // Return the order details and activity logs
            return response()->json([
                'success' => true,
                'order_cancellation' => $orderCancellation,
                'activityLogs' => $activityLogs,
            ]);
        }

        // If the order is not found
        return response()->json([
            'success' => false,
            'message' => 'Order not found'
        ]);
    }


}
