<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderTracking;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrderCancellationController extends Controller
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



        $statuses = ['all', 'requested', 'approved', 'rejected'];

        if (empty($status) || !in_array($status, $statuses)) {
            return redirect()->back();
        }

        if (request()->ajax()) {
            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');

            if ($status == 'requested') {
                return $this->requestedOrderCancellations($start_date , $end_date);
            } elseif ($status == 'approved') {
                return $this->approvedOrderCancellation($start_date , $end_date);
            } elseif ($status == 'rejected') {
                return $this->rejectedOrderCancellation($start_date , $end_date);
            } elseif ($status == 'all') {
                return $this->allOrders($start_date , $end_date);
            }
        }


        return view('applicationDashboard.pages.orderCancellations.index',compact('status'));
    }

    public function allOrders($startDate = null, $endDate = null)
    {
        $orderCancellations = OrderCancellation::with(
            ['client.contact:id,name', 'order:id,number,order_status'])
            ->select(['id', 'order_id', 'client_id', 'status']);
    
        // Apply date filter if start_date and end_date are provided
        if ($startDate && $endDate) {
            $orderCancellations->whereBetween('created_at', [$startDate, $endDate]);
        }
    
        return Datatables::of($orderCancellations)
            ->addColumn('client_contact_name', function ($orderCancellation) {
                return optional($orderCancellation->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }
    
    
    public function requestedOrderCancellations($startDate = null, $endDate = null)
    {
        $orderCancellations = OrderCancellation::with(
            ['client.contact:id,name', 'order:id,number,order_status'])
            ->where('status', 'requested')
            ->select(['id', 'order_id', 'client_id', 'status']);
    
            if ($startDate && $endDate) {
                $orderCancellations->whereBetween('created_at', [$startDate, $endDate]);
            }
    
        return Datatables::of($orderCancellations)
            ->addColumn('client_contact_name', function ($orderCancellation) {
                return optional($orderCancellation->client->contact)->name ?? 'N/A';
            })
            ->make(true);
    }
    
    
    public function approvedOrderCancellation($startDate = null, $endDate = null)
    {
        $orderCancellations = OrderCancellation::with(
            ['client.contact:id,name', 'order:id,number,order_status'])
        ->where('status', 'approved')
        ->select(['id', 'order_id', 'client_id', 'status']);

        if ($startDate && $endDate) {
            $orderCancellations->whereBetween('created_at', [$startDate, $endDate]);
        }

    return Datatables::of($orderCancellations)
        ->addColumn('client_contact_name', function ($orderCancellation) {
            return optional($orderCancellation->client->contact)->name ?? 'N/A';
        })
        ->make(true);
    }
    
    public function rejectedOrderCancellation($startDate = null, $endDate = null)
    {
        $orderCancellations = OrderCancellation::with(['client:id,contact.name','order:id,number'])
        ->where('status', 'rejected')
        ->select(['id', 'order_id', 'client_id', 'status']);

        if ($startDate && $endDate) {
            $orderCancellations->whereBetween('created_at', [$startDate, $endDate]);
        }

    return Datatables::of($orderCancellations)
        ->addColumn('client_contact_name', function ($orderCancellation) {
            return optional($orderCancellation->client->contact)->name ?? 'N/A';
        })
        ->make(true);
    }
    public function changeOrderCancellationStatus($orderCancellationId)
    {
        $status = request()->input('status'); // Retrieve status from the request

        $orderCancellation = OrderCancellation::findOrFail($orderCancellationId);
        $orderCancellation->status = $status;


        // Set the tracking status timestamp based on the status provided
        switch ($status) {
            case 'requested':
                $orderCancellation->requested_at = now();
                break;
            case 'approved':
                $orderCancellation->processed_at = now();
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        $orderCancellation->save();

        return response()->json(['success' => true, 'message' => 'Order Cancellation status updated successfully.']);
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
        if (!auth()->user()->can('Order.update')) {
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
        if (!auth()->user()->can('Order.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['status', 'reason','admin_response']);

                $orderCancellation = OrderCancellation::findOrFail($id);
                $orderCancellation->status = $input['status'];
                $orderCancellation->reason = $input['reason'];
                $orderCancellation->admin_response = $input['admin_response'];

                $orderCancellation->save();

                // $output = [
                //     'success' => true,
                //     'msg' => __("Order.updated_success")
                // ];

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
