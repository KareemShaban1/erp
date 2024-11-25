<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderTracking;
use App\Services\API\OrderService;
use App\Services\FirebaseService;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrderRefundController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;
    protected $orderService;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil , OrderService $orderService)
    {
        $this->moduleUtil = $moduleUtil;
        $this->orderService = $orderService;
    }

    public function index()
    {
        if (request()->ajax()) {
            $status = request()->get('status', 'all'); // Default to 'all' if not provided
            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');
            $search = request()->get('search.value');

            // Validate status
            $validStatuses = ['all', 'requested', 'processed', 'approved', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                $status = 'all';
            }

            // Fetch filtered data
            return $this->fetchOrderRefunds($status, $startDate, $endDate, $search);
        }

        return view('applicationDashboard.pages.orderRefunds.index');
    }

    /**
     * Fetch order refunds based on filters.
     */
    private function fetchOrderRefunds($status, $startDate = null, $endDate = null, $search = null)
    {
        $query = OrderRefund::with(['client.contact:id,name', 'order:id,number,order_status','order_item.product:id,name', 'order_item'])  // Added product relationship
        ->select(['id', 'order_id', 'client_id','order_item_id', 'status','refund_status', 'amount', 'created_at']);

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
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
            ->addColumn('client_contact_name', function ($orderRefund) {
                return optional($orderRefund->client->contact)->name ?? 'N/A';
            })
            ->addColumn('order_number', function ($orderRefund) {
                return optional($orderRefund->order)->number ?? 'N/A';
            })
            ->addColumn('order_status', function ($orderRefund) {
                return optional($orderRefund->order)->order_status ?? 'N/A';
            })
            ->addColumn('order_item', function ($orderRefund) {
                return $orderRefund->order_item;
            })
            ->make(true);
    }

    public function changeOrderRefundStatus($orderRefundId)
    {
        $status = request()->input('status'); // Retrieve status from the request

        $orderRefund = OrderRefund::findOrFail($orderRefundId);
        $orderRefund->status = $status;

        $order = Order::where('id', $orderRefund->order_id)->first();

        // Set the tracking status timestamp based on the status provided
        switch ($status) {
            case 'requested':
                $orderRefund->requested_at = now();
                $this->moduleUtil->activityLog($orderRefund, 'change_status', null, ['order_number' => $order->number, 'status' => 'requested']);
                break;
            case 'processed':
                $orderRefund->processed_at = now();
                $this->moduleUtil->activityLog($orderRefund, 'change_status', null, ['order_number' => $order->number, 'status' => 'processed']);
                break;
            case 'approved':
                $orderRefund->processed_at = now();
                $this->moduleUtil->activityLog($orderRefund, 'change_status', null, ['order_number' => $order->number, 'status' => 'approved']);
                $this->orderService->storeRefundOrderItem($order,$orderRefund);
                break;
            case 'rejected':
                $this->moduleUtil->activityLog($orderRefund, 'change_status', null, ['order_number' => $order->number, 'status' => 'rejected']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        $orderRefund->save();

        return response()->json(['success' => true, 'message' => 'Order Refund status updated successfully.']);
    }


    public function changeRefundStatus($orderRefundId)
    {
        $status = request()->input('refund_status');

        $orderRefund = OrderRefund::findOrFail($orderRefundId);
        $orderRefund->refund_status = $status;

        $order = Order::where('id', $orderRefund->order_id)->first();

        // Set the tracking status timestamp based on the status provided
        switch ($status) {
            case 'pending':
                // $orderRefund->requested_at = now();
                $this->moduleUtil->activityLog($orderRefund, 'change_refund_status', null, ['order_number' => $order->number, 'status' => 'pending']);
                break;
            case 'processed':
                // $orderRefund->processed_at = now();
                // Send and store push notification
                 app(FirebaseService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Refund Status Changed',
                    'Your order refund has been processed successfully.',
                    ['order_id' => $order->id,
                    'order_number'=>$order->order_number,
                    'status'=>$orderRefund->status]
                );
                $this->moduleUtil->activityLog($orderRefund, 'change_refund_status', null, ['order_number' => $order->number, 'status' => 'processed']);
                break;
            case 'delivering':
                // $orderRefund->processed_at = now();
                $this->moduleUtil->activityLog($orderRefund, 'change_refund_status', null, ['order_number' => $order->number, 'status' => 'delivering']);
                // Send and store push notification
                app(FirebaseService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Refund Status Changed',
                    'Your order refund is delivering.',
                    ['order_id' => $order->id,
                    'order_number'=>$order->order_number,
                    'status'=>$orderRefund->status]
                );
                break;
            case 'completed':
                // Send and store push notification
                app(FirebaseService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Refund Status Changed',
                    'Your order refund has been returned to store successfully.',
                    ['order_id' => $order->id,
                    'order_number'=>$order->order_number,
                    'status'=>$orderRefund->status]
                );
                $this->moduleUtil->activityLog($orderRefund, 'change_refund_status', null, ['order_number' => $order->number, 'status' => 'completed']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        $orderRefund->save();

        return response()->json(['success' => true, 'message' => 'Order Refund status updated successfully.']);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('Order.create')) {
            abort(403, 'Unauthorized action.');
        }

        $quick_add = false;
        if (!empty(request()->input('quick_add'))) {
            $quick_add = true;
        }

        $is_repair_installed = $this->moduleUtil->isModuleInstalled('Repair');

        return view('Order.create')
            ->with(compact('quick_add', 'is_repair_installed'));
    }


    public function store(Request $request)
{
    $data = $request->validate([
        'order_id' => 'required|exists:orders,id',
        'items' => 'required|array',
        'items.*.id' => 'required|exists:order_items,id',
        'items.*.refund_reason' => 'required|string',
        'items.*.refund_amount' => 'required|numeric|min:0',
        'items.*.refund_status' => 'required|in:requested,processed,approved,rejected',
        'items.*.refund_admin_response' => 'nullable|string',
    ]);
    $order = Order::find($request->order_id);

    foreach ($data['items'] as $item) {
        $orderRefund = OrderRefund::create([
            'reason' => $item['refund_reason'] ?? null,
            'admin_response' => $item['refund_admin_response'] ?? null,
            'amount' => $item['refund_amount'] ?? 0,
            'status' => $item['refund_status'],
            'order_item_id'=>$item['id'],
            'order_id'=>$order->id,
            'client_id'=>$order->client->id
        ]);

        switch ($orderRefund->status) {
            case 'requested':
                $orderRefund->requested_at = now();
                $this->moduleUtil->activityLog($orderRefund, 'add_order_refund', null, ['order_number' => $order->number, 'status' => 'requested']);
                break;
            case 'processed':
                $orderRefund->processed_at = now();
                $this->moduleUtil->activityLog($orderRefund, 'add_order_refund', null, 
                ['order_number' => $order->number, 'status' => 'processed']);
                break;
            case 'approved':
                $orderRefund->processed_at = now();
                $this->moduleUtil->activityLog($orderRefund, 'add_order_refund', null, ['order_number' => $order->number, 'status' => 'approved']);
                $this->orderService->storeRefundOrder($order,$data['items']);
                break;
            case 'rejected':
                $this->moduleUtil->activityLog($orderRefund, 'add_order_refund', null, ['order_number' => $order->number, 'status' => 'rejected']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $orderRefund->status");
            }
       
    }

    return response()->json([
        'success' => true,
        'message' => __('Refund processed successfully.'),
    ]);
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
        $orderRefund = OrderRefund::findOrFail($id);

        // Return data as JSON to be used in the modal
        return response()->json($orderRefund);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('Order.update')) {
            abort(403, 'Unauthorized action.');
        }

        $orderRefund = OrderRefund::findOrFail($id);

        // Return data as JSON to be used in the modal
        return response()->json($orderRefund);
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
        if (!auth()->user()->can('Order.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['status', 'reason', 'admin_response']);

                $orderRefund = OrderRefund::findOrFail($id);
                $orderRefund->status = $input['status'];
                $orderRefund->reason = $input['reason'];
                $orderRefund->admin_response = $input['admin_response'];

                $orderRefund->save();

                $order = Order::where('id', $orderRefund->order_id)->first();

                if ($input['admin_response']) {
                    // Send and store push notification
                    app(FirebaseService::class)->sendAndStoreNotification(
                        $order->client->id,
                        $order->client->fcm_token,
                        'Order Cancellation Admin Response',
                        'Your order has been shipped successfully.',
                        [
                            'order_id' => $order->id,
                            'order_refund_id' => $orderRefund->id,
                            'admin_response' => $input['admin_response']
                        ]
                    );
                }

                // $output = [
                //     'success' => true,
                //     'msg' => __("Order.updated_success")
                // ];

                return response()->json(['success' => true, 'message' => 'Order Refund updated successfully.']);

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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('Order.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $Order = Order::where('business_id', $business_id)->findOrFail($id);
                $Order->delete();

                $output = [
                    'success' => true,
                    'msg' => __("Order.deleted_success")
                ];
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

    public function getOrderApi()
    {
        try {
            $api_token = request()->header('API-TOKEN');

            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $orders = Order::where('business_id', $api_settings->business_id)
                ->get();
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($orders);
    }
}
