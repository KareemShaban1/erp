<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrderReportsController extends Controller
{

    public function index(Request $request)
    {
        if (!auth()->user()->can('orders.reports')) {
            abort(403, 'Unauthorized action.');
        }
    
        if (request()->ajax()) {
            $query = Order::select(
                'client_id',
                DB::raw('SUM(CASE WHEN order_type = "order" THEN total ELSE 0 END) as total_amount'),
                DB::raw('SUM(CASE WHEN order_type = "order_refund" THEN total ELSE 0 END) as refund_amount'),
                DB::raw('SUM(CASE WHEN order_status = "cancelled" THEN total ELSE 0 END) as canceled_amount')
            )
                ->with(['client.contact', 'client.business_location']) // Ensure relationships are eager loaded
                ->groupBy('client_id');
    
            // Apply date filters
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }
    
            // Apply search filter for client name
            if (!empty($request->search['value'])) {
                $searchValue = $request->search['value'];
            
                $query->whereHas('client.contact', function ($q) use ($searchValue) {
                    $q->where('name', 'like', '%' . $searchValue . '%');
                });
            }
            
    
            // Calculate grand totals separately
            $grandTotals = Order::select(
                DB::raw('SUM(CASE WHEN order_type = "order" THEN total ELSE 0 END) as total_amount'),
                DB::raw('SUM(CASE WHEN order_type = "order_refund" THEN total ELSE 0 END) as refund_amount'),
                DB::raw('SUM(CASE WHEN order_status = "cancelled" THEN total ELSE 0 END) as canceled_amount')
            );
    
            if ($request->start_date && $request->end_date) {
                $grandTotals->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }
    
            $grandTotals = $grandTotals->first();
    
            return DataTables::of($query)
                ->addColumn('client_name', function ($row) {
                    return '<a href="' . route('client.orders', $row->client_id) . '">' .
                        optional($row->client->contact)->name ?? 'Unknown Client' .
                        '</a>';
                })
                ->addColumn('client_location', function ($row) {
                    return optional($row->client->business_location)->name ?? 'Unknown Location';
                })
                ->editColumn('total_amount', function ($row) {
                    return number_format($row->total_amount, 2);
                })
                ->editColumn('canceled_amount', function ($row) {
                    return number_format($row->canceled_amount, 2);
                })
                ->editColumn('refund_amount', function ($row) {
                    return number_format($row->refund_amount, 2);
                })
                ->with([
                    'grand_total_amount' => number_format($grandTotals->total_amount ?? 0, 2),
                    'grand_canceled_amount' => number_format($grandTotals->canceled_amount ?? 0, 2),
                    'grand_refund_amount' => number_format($grandTotals->refund_amount ?? 0, 2),
                ])
                ->rawColumns(['client_name'])
                ->make(true);
        }
    
        return view('applicationDashboard.pages.orderReports.index');
    }





    public function clientOrders(Request $request, $clientId)
    {
        if (!auth()->user()->can('orders.reports')) {
            abort(403, 'Unauthorized action.');
        }
        if (!$clientId) {
            return response()->json(['error' => 'Client ID is required'], 400);
        }
    
        // Get filters from request
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $orderType = $request->order_type;
        $orderStatus = $request->order_status;
    
        // Build the query
        $ordersQuery = Order::with(['client', 'businessLocation'])
            ->select([
                'id',
                'number',
                'client_id',
                'payment_method',
                'order_status',
                'order_type',
                'payment_status',
                'shipping_cost',
                'sub_total',
                'total',
                'created_at'
            ])
            ->where('client_id', $clientId)
            ->latest();
    
        // Apply filters
        if ($startDate && $endDate) {
            $ordersQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        if ($orderType && $orderType !== 'all') {
            $ordersQuery->where('order_type', $orderType);
        }
        if ($orderStatus && $orderStatus !== 'all') {
            $ordersQuery->where('order_status', $orderStatus);
        }
    
        // Calculate the total sum
        $totalSum = $ordersQuery->sum('total');
    
        // Fetch the filtered orders
        $orders = $ordersQuery->get();
    
        // Return JSON if request is AJAX
        if (request()->ajax()) {
            return Datatables::of($orders)
                ->addColumn('client_contact_name', function ($order) {
                    return optional($order->client->contact)->name ?? 'N/A';
                })
                ->addColumn('has_delivery', function ($order) {
                    return $order->has_delivery;
                })
                ->with('totalSum', $totalSum) // Add total sum to response
                ->make(true);
        }
    
        return view('applicationDashboard.pages.orderReports.clientOrders', [
            'clientId' => $clientId
        ]);
    }
    



}
