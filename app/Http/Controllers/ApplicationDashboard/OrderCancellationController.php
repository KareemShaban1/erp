<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderCancellation\OrderCancellationResource;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderRefund;
use App\Models\OrderTracking;
use App\Models\Transaction;
use App\Models\TransactionSellLine;
use App\Notifications\OrderCancellationCreatedNotification;
use App\Services\API\CancellationTransferQuantityService;
use App\Services\API\OrderService;
use App\Services\FirebaseClientService;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
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
    protected $productUtil;
    protected $transactionUtil;
    protected $transferQuantityService;

    public function __construct(
        ModuleUtil $moduleUtil,
        ProductUtil $productUtil,
        TransactionUtil $transactionUtil,
        CancellationTransferQuantityService $transferQuantityService

    ) {
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->transferQuantityService = $transferQuantityService;
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
                    app(FirebaseClientService::class)->sendAndStoreNotification(
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


    public function store($data)
    {
        $data['client_id'] = Auth::id();
        $data['status'] = 'approved';
        $data['reason'] = 'cancelled from erp';
        $data['requested_at'] = now();

        try {
            // Find the order by ID and ensure it exists
            $order = Order::find($data['order_id']);
            $orderTracking = OrderTracking::where('order_id', $data['order_id'])->first();
            if (!$order) {
                return $this->returnJSON(null, __('message.Order not found'), 404);
            }

            $order_transfer = Order::where('parent_order_id', $order->id)
                ->where('order_type', 'order_transfer')
                ->whereIn('order_status', ['pending', 'processing'])
                ->get();

            // Check if the order status allows cancellation
            if (in_array($order->order_status, ['pending'])) {
                // Set order status to 'cancelled' and save
                $order->order_status = 'cancelled';
                $order->payment_status = 'failed';
                $orderTracking->cancelled_at = now();


                // decrease quantity of order items from location
                foreach ($order->orderItems as $item) {
                    $this->productUtil->updateProductQuantity(
                        $order->business_location_id,
                        $item->product_id,
                        $item->variation_id,
                        $item->quantity
                    );
                }

                // if there is transfer from location to location based on
                // this order re transfer it again
                if ($order_transfer) {
                    foreach ($order_transfer as $transfer) {
                        foreach ($transfer->orderItems as $item) {
                            $this->transferQuantityService->transferQuantityForCancellation(
                                $transfer,
                                $item,
                                $transfer->client,
                                $transfer->to_business_location_id,
                                $transfer->from_business_location_id,
                                $item->quantity
                            );
                        }
                        $transfer->order_status = 'cancelled';
                        $transfer->save();
                    }
                }

                $business_id = $order->client->contact->business->id;

                $parent_sell_transaction = Transaction::
                    where('order_id', $order->id)
                    ->where('type', 'sell')
                    ->first();
                \Log::info('parent_sell_transaction', [$parent_sell_transaction]);
                $products = [];
                foreach ($order->orderItems as $item) {
                    $transaction_sell_line = TransactionSellLine::
                        where('product_id', $item->product_id)
                        ->where('transaction_id', $parent_sell_transaction->id)
                        ->first();
                    \Log::info('transaction_sell_line', [$transaction_sell_line]);

                    $products[] = [
                        'sell_line_id' => $transaction_sell_line->id, // Adjust this field name to match your schema
                        'quantity' => $item->quantity,
                        'unit_price_inc_tax' => $item->price, // Include price if applicable
                    ];

                    $transferOrder = Order::where('id', $item->order_id)
                        ->first();
                    $input = [
                        'transaction_id' => $parent_sell_transaction->id,
                        'order_id' => $transferOrder->id,
                        // 'invoice_no' => null,
                        // 'transaction_date' => Carbon::now(),
                        'products' => $products,
                        "discount_type" => null,
                        "discount_amount" => $item->discount,
                        "tax_id" => null,
                        "tax_amount" => "0",
                        "tax_percent" => "0",
                    ];


                    // add sell return for this cancelled order
                    $this->transactionUtil->addSellReturnForCancellation($input, $business_id, 1);
                }

                $order->save();
                $orderTracking->save();
            } else {
                // Return a response indicating the status cannot be changed
                return $this->returnJSON(null, __('message.Order status is :status, it can\'t be changed', ['status' => $order->order_status]));
            }

            // Create the OrderCancellation record
            $orderCancellation = OrderCancellation::create($data);


            // Notify admins and users about the order
            $admins = $this->moduleUtil->get_admins($order->client->contact->business_id);
            $users = $this->moduleUtil->getBusinessUsers($order->client->contact->business_id, $order);

            \Notification::send($admins, new OrderCancellationCreatedNotification($order));
            \Notification::send($users, new OrderCancellationCreatedNotification($order));


            // Return the created OrderCancellation as a resource
            return new OrderCancellationResource($orderCancellation);

        } catch (\Exception $e) {
            // Handle any unexpected exceptions
            return $this->handleException($e, __('message.Error occurred while storing OrderCancellation'));
        }
    }



}
