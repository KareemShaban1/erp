<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderTracking;
use App\Services\FirebaseService;
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
        $query = OrderCancellation::with(['client.contact:id,name', 'order:id,number,order_status'])
            ->select(['id', 'order_id', 'client_id', 'status', 'created_at']);

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
            ->make(true);
    }


    public function changeOrderCancellationStatus($orderCancellationId)
    {
        $status = request()->input('status'); // Retrieve status from the request

        $orderCancellation = OrderCancellation::findOrFail($orderCancellationId);
        $orderCancellation->status = $status;

        $order = Order::where('id',$orderCancellation->order_id)->first();


        // Set the tracking status timestamp based on the status provided
        switch ($status) {
            case 'requested':
                $orderCancellation->requested_at = now();
                $this->moduleUtil->activityLog($orderCancellation, 'change_status', null, ['order_number' => $order->number, 'status'=>'requested']);
                break;
            case 'approved':
                $orderCancellation->processed_at = now();
                $this->moduleUtil->activityLog($orderCancellation, 'change_status', null, ['order_number' => $order->number, 'status'=>'approved']);
                break;
            case 'rejected':
                $this->moduleUtil->activityLog($orderCancellation, 'change_status', null, ['order_number' => $order->number, 'status'=>'rejected']);
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
                $order = Order::where('id',$orderCancellation->order_id)->first();

                if($input['admin_response']){
                    // Send and store push notification
                    app(FirebaseService::class)->sendAndStoreNotification(
                       $order->client->id,
                       $order->client->fcm_token,
                       'Order Cancellation Admin Response',
                       'Your order has been shipped successfully.',
                       ['order_id' => $order->id, 
                       'order_cancellation_id'=>$orderCancellation->id,
                       'admin_response' => $input['admin_response']]
                   );
               }

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
