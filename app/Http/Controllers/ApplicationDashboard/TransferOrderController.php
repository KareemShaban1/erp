<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderTransfer;
use App\Models\OrderTracking;
use App\Services\FirebaseService;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class TransferOrderController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        if (request()->ajax()) {
            $status = request()->get('status', 'all'); // Default to 'all' if not provided
            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');
            $search = request()->get('search.value');

            // Validate status
            $validStatuses = ['all', 'pending', 'processing', 'shipped', 'cancelled', 'completed'];
            if (!in_array($status, $validStatuses)) {
                $status = 'all';
            }

            // Fetch filtered data
            return $this->fetchOrders($status, $startDate, $endDate, $search);
        }

        return view('applicationDashboard.pages.transferOrders.index');
    }

    /**
     * Fetch order Transfers based on filters.
     */
    private function fetchOrders($status, $startDate = null, $endDate = null, $search = null)
    {
        $query = Order::with(['client','fromBusinessLocation','toBusinessLocation'])
                ->where('order_type','order_transfer')
                ->select(['id', 'number','order_type', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total','created_at'])
                ->latest();

                
        // Apply status filter
        if ($status !== 'all') {
            $query->where('order_status', $status);
        }

        // Apply date filter
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                // Filter for a single day
                $query->whereDate('created_at', $startDate);
            } else {
                // Adjust endDate to include the entire day
                $endDate = Carbon::parse($endDate)->endOfDay();
        
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
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->addColumn('has_delivery', function ($order) {
                return $order->has_delivery; // Add the delivery status here
            })
            ->make(true);
    }



    public function changeOrderStatus($orderId)
    {
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
                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status'=>'pending']);
                break;
            case 'processing':
                $orderTracking->processing_at = now();
                // Send and store push notification
                app(FirebaseService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Status Changed',
                    'Your order has been processed successfully.',
                    ['order_id' => $order->id,
                    'status'=>$order->status]
                );
                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->order, 'status'=>'processing']);
                break;
            case 'shipped':
                $this->updateDeliveryBalance($order, $delivery);
                 // Send and store push notification
                app(FirebaseService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Status Changed',
                    'Your order has been shipped successfully.',
                    ['order_id' => $order->id, 
                    'status'=>$order->status]
                );
                $orderTracking->shipped_at = now();
                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status'=>'shipped']);
                break;
            case 'cancelled':
                $orderTracking->cancelled_at = now();
                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status'=>'cancelled']);
                break;
            case 'completed':
                $orderTracking->completed_at = now();
                // Send and store push notification
                app(FirebaseService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Status Changed',
                    'Your order has been completed successfully.',
                    ['order_id' => $order->id, 
                    'status'=>$order->status]
                );
                $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status'=>'completed']);
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
                $this->moduleUtil->activityLog($order, 'change_payment_status', null, ['order_number' => $order->number, 'status'=>'pending']);
                break;
            case 'paid':
                $this->moduleUtil->activityLog($order, 'change_payment_status', null, ['order_number' => $order->number, 'status'=>'paid']);
                break;
            case 'failed':
                $this->moduleUtil->activityLog($order, 'change_payment_status', null, ['order_number' => $order->number, 'status'=>'failed']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");    
            }

        return response()->json(['success' => true, 'message' => 'Order Payment status updated successfully.']);
    }


    public function getOrderDetails($orderId)
{
    // Fetch the order along with related data
    $order = Order::with([
        'client.contact', 
        'businessLocation', 
        'orderItems',
        'fromBusinessLocation',
        'toBusinessLocation'
    ])->find($orderId);


    if ($order) {
        // Iterate through each order item and check for Transfer details
        foreach ($order->orderItems as $item) {
            // Check if there are any records in the order_Transfer table for this order item
            $refund = OrderRefund::where('order_item_id', $item->id)->first(); // Assuming 'Transfer_amount' stores the Transfered quantity or amount

            $refund_amount = $refund->amount ?? 0;
            // Calculate the difference between the order item quantity and the Transfered amount
            $item->remaining_quantity = $item->quantity - $refund_amount;
        }

        return response()->json([
            'success' => true,
            'order' => $order
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Order not found'
    ]);
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('Order.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'description']);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;
            $input['created_by'] = $request->session()->get('user.id');

            if ($this->moduleUtil->isModuleInstalled('Repair')) {
                $input['use_for_repair'] = !empty($request->input('use_for_repair')) ? 1 : 0;
            }

            $Order = Order::create($input);
            $output = [
                'success' => true,
                'data' => $Order,
                'msg' => __("Order.added_success")
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $Order = Order::where('business_id', $business_id)->find($id);

            $is_repair_installed = $this->moduleUtil->isModuleInstalled('Repair');

            return view('Order.edit')
                ->with(compact('Order', 'is_repair_installed'));
        }
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
                $input = $request->only(['name', 'description']);
                $business_id = $request->session()->get('user.business_id');

                $Order = Order::where('business_id', $business_id)->findOrFail($id);
                $Order->name = $input['name'];
                $Order->description = $input['description'];

                if ($this->moduleUtil->isModuleInstalled('Repair')) {
                    $Order->use_for_repair = !empty($request->input('use_for_repair')) ? 1 : 0;
                }

                $Order->save();

                $output = [
                    'success' => true,
                    'msg' => __("Order.updated_success")
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
