<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
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

        $start_date = request()->get('start_date');
        $end_date = request()->get('end_date');

        $statuses = ['all', 'pending', 'processing', 'shipped', 'cancelled', 'completed'];

        if (empty($status) || !in_array($status, $statuses)) {
            return redirect()->back();
        }

        if (request()->ajax()) {
            if ($status == 'pending') {
                return $this->pendingOrders();
            } elseif ($status == 'processing') {
                return $this->processingOrders();
            }  elseif ($status == 'shipped') {
                return $this->shippedOrders();
            } elseif ($status == 'cancelled') {
                return $this->cancelledOrders();
            } elseif ($status == 'completed') {
                return $this->completedOrders();
            } elseif ($status == 'all') {
                return $this->allOrders();
            }
        }


        return view('applicationDashboard.pages.orders.index');
    }

    public function allOrders()
    {
        $orders = Order::with('client')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total']);
    
        // Apply date filter if start_date and end_date are provided
        if (request()->has(['start_date', 'end_date'])) {
            $orders->whereBetween('created_at', [request()->get('start_date'), request()->get('end_date')]);
        }
    
        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }
    
    public function pendingOrders()
    {
        $orders = Order::with('client')
            ->where('order_status', 'pending')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total']);
    
        if (request()->has(['start_date', 'end_date'])) {
            $orders->whereBetween('created_at', [request()->get('start_date'), request()->get('end_date')]);
        }
    
        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }
    
    // Repeat the date filter logic for other status-based methods
    
    public function processingOrders()
    {
        $orders = Order::with('client')
            ->where('order_status', 'processing')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total']);
    
        if (request()->has(['start_date', 'end_date'])) {
            $orders->whereBetween('created_at', [request()->get('start_date'), request()->get('end_date')]);
        }
    
        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }
    
    public function shippedOrders()
    {
        $orders = Order::with('client')
            ->where('order_status', 'shipped')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total']);
    
        if (request()->has(['start_date', 'end_date'])) {
            $orders->whereBetween('created_at', [request()->get('start_date'), request()->get('end_date')]);
        }
    
        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }
    
    public function canceledOrders()
    {
        $orders = Order::with('client')
            ->where('order_status', 'cancelled')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total']);
    
        if (request()->has(['start_date', 'end_date'])) {
            $orders->whereBetween('created_at', [request()->get('start_date'), request()->get('end_date')]);
        }
    
        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }
    
    public function completedOrders()
    {
        $orders = Order::with('client')
            ->where('order_status', 'completed')
            ->select(['id', 'number', 'client_id', 'payment_method', 'order_status', 'payment_status', 'shipping_cost', 'sub_total', 'total']);
    
        if (request()->has(['start_date', 'end_date'])) {
            $orders->whereBetween('created_at', [request()->get('start_date'), request()->get('end_date')]);
        }
    
        return Datatables::of($orders)
            ->addColumn('client_contact_name', function ($order) {
                return optional($order->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }
    

    public function changeOrderStatus($orderId)
    {
        $status = request()->input('order_status'); // Retrieve status from the request

        $order = Order::findOrFail($orderId);
        $order->order_status = $status;
        $order->save();

        $orderTracking = new OrderTracking();
        $orderTracking->order_id = $order->id;

        // Set the tracking status timestamp based on the status provided
        switch ($status) {
            case 'pending':
                $orderTracking->pending_at = now();
                break;
            case 'processing':
                $orderTracking->processing_at = now();
                break;
            case 'shipped':
                $orderTracking->shipped_at = now();
                break;
            case 'cancelled':
                $orderTracking->canceled_at = now();
                break;
            case 'completed':
                $orderTracking->completed_at = now();
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        $orderTracking->save();

        return response()->json(['success' => true, 'message' => 'Order status updated successfully.']);
    }

    public function changePaymentStatus($orderId)
    {
        $status = request()->input('payment_status'); // Retrieve status from the request

        $order = Order::findOrFail($orderId);
        $order->payment_status = $status;
        $order->save();


        return response()->json(['success' => true, 'message' => 'Order Payment status updated successfully.']);
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
}