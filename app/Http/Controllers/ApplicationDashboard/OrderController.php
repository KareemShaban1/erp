<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderTracking;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
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
        $status = request()->get('status');
        $statuses = ['all', 'pending', 'processing', 'shipped', 'cancelled', 'completed'];

        if (empty($status) || !in_array($status, $statuses)) {
            return redirect()->back();
        }

        if (request()->ajax()) {
            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');


            // if (!empty(request()->start_date) && !empty(request()->end_date)) {
            //     $start = request()->start_date;
            //     $end = request()->end_date;
            // }

            if ($status == 'pending') {
                return $this->pendingOrders($startDate, $endDate);
            } elseif ($status == 'processing') {
                return $this->processingOrders($startDate, $endDate);
            } elseif ($status == 'shipped') {
                return $this->shippedOrders($startDate, $endDate);
            } elseif ($status == 'cancelled') {
                return $this->cancelledOrders($startDate, $endDate);
            } elseif ($status == 'completed') {
                return $this->completedOrders($startDate, $endDate);
            } elseif ($status == 'all') {
                return $this->allOrders($startDate, $endDate);
            }
        }

        return view('applicationDashboard.pages.orders.index',compact('status'));
    }

    public function allOrders($startDate = null, $endDate = null)
    {
        $orders = Order::with(['client','businessLocation'])
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total','created_at'])
            ->latest();

        // Apply date filter if start_date and end_date are provided
        if ($startDate && $endDate) {
            $orders->whereBetween('created_at', [$startDate, $endDate]);
        }

        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->addColumn('has_delivery', function ($order) {
                return $order->has_delivery; // Add the delivery status here
            })
            ->make(true);
    }

    public function pendingOrders($startDate = null, $endDate = null)
    {
        $orders = Order::with('client')
            ->where('order_status', 'pending')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total','created_at'])
            ->latest();

        // Apply date filter if start_date and end_date are provided
        if ($startDate && $endDate) {
            $orders->whereBetween('created_at', [$startDate, $endDate]);
        }


        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }

    // Repeat the date filter logic for other status-based methods

    public function processingOrders($startDate = null, $endDate = null)
    {
        $orders = Order::with('client')
            ->where('order_status', 'processing')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total','created_at'])
            ->latest();

        // Apply date filter if start_date and end_date are provided
        if ($startDate && $endDate) {
            $orders->whereBetween('created_at', [$startDate, $endDate]);
        }


        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->addColumn('has_delivery', function ($order) {
                return $order->has_delivery; // Add the delivery status here
            })
            ->make(true);
    }

    public function shippedOrders($startDate = null, $endDate = null)
    {
        $orders = Order::with('client')
            ->where('order_status', 'shipped')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total','created_at'])
            ->latest();

        // Apply date filter if start_date and end_date are provided
        if ($startDate && $endDate) {
            $orders->whereBetween('created_at', [$startDate, $endDate]);
        }


        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }

    public function canceledOrders($startDate = null, $endDate = null)
    {
        $orders = Order::with('client')
            ->where('order_status', 'cancelled')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total','created_at'])
            ->latest();

        // Apply date filter if start_date and end_date are provided
        if ($startDate && $endDate) {
            $orders->whereBetween('created_at', [$startDate, $endDate]);
        }


        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }

    public function completedOrders($startDate = null, $endDate = null)
    {
        $orders = Order::with('client')
            ->where('order_status', 'completed')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total','created_at'])
            ->latest();

        // Apply date filter if start_date and end_date are provided
        if ($startDate && $endDate) {
            $orders->whereBetween('created_at', [$startDate, $endDate]);
        }
        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
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
                break;
            case 'processing':
                $orderTracking->processing_at = now();
                break;
            case 'shipped':
                $this->updateDeliveryBalance($order, $delivery);
                $orderTracking->shipped_at = now();
                break;
            case 'cancelled':
                $orderTracking->cancelled_at = now();
                break;
            case 'completed':
                $orderTracking->completed_at = now();
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

        
        $client = Client::where('id',$order->id)->first();

        $client->contact->balance += $order->total ;
        $client->contact->save();


        return response()->json(['success' => true, 'message' => 'Order Payment status updated successfully.']);
    }


    public function getOrderDetails($orderId)
{
    $order = Order::with(['client.contact','businessLocation',
    'orderItems'])->find($orderId);

    if ($order) {
        return response()->json([
            'success' => true,
            'order' => $order
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Order not found.'
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
