<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
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

        if ($request->ajax()) {
            $query = Order::select(
                'client_id',
                DB::raw('SUM(CASE WHEN order_type = "order" THEN total ELSE 0 END) as total_amount'),
                DB::raw('SUM(CASE WHEN order_type = "order_refund" THEN total ELSE 0 END) as refund_amount'),
                DB::raw('SUM(CASE WHEN order_status = "cancelled" THEN total ELSE 0 END) as canceled_amount')
            )
                ->with(['client.contact', 'client.business_location'])
                ->groupBy('client_id');

            // Apply date filters
            // if ($request->start_date && $request->end_date) {
            //     $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
            // }
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            if ($startDate && $endDate) {
                if ($startDate === $endDate) {
                    // Filter for a single day
                    $query->whereDate('created_at', $startDate);
                } else {
                    // Filter for a range of dates
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            }
            // Apply client filter if selected
            if (!empty($request->client_id)) {
                $query->where('client_id', $request->client_id);
            }

            // Calculate grand totals separately
            $grandTotals = Order::select(
                DB::raw('SUM(CASE WHEN order_type = "order" THEN total ELSE 0 END) as total_amount'),
                DB::raw('SUM(CASE WHEN order_type = "order_refund" THEN total ELSE 0 END) as refund_amount'),
                DB::raw('SUM(CASE WHEN order_status = "cancelled" THEN total ELSE 0 END) as canceled_amount')
            );

            // if ($request->start_date && $request->end_date) {
            //     $grandTotals->whereBetween('created_at', [$request->start_date, $request->end_date]);
            // }
            if ($startDate && $endDate) {
                if ($startDate === $endDate) {
                    // Filter for a single day
                    $grandTotals->whereDate('created_at', $startDate);
                } else {
                    // Filter for a range of dates
                    $grandTotals->whereBetween('created_at', [$startDate, $endDate]);
                }
            }

            if (!empty($request->client_id)) {
                $grandTotals->where('client_id', $request->client_id);
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

        $clients = Client::active()->with('contact')->get();

        return view('applicationDashboard.pages.orderReports.index', compact('clients'));
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
            if ($startDate === $endDate) {
                // Filter for a single day
                $ordersQuery->whereDate('created_at', $startDate);
            } else {
                // Filter for a range of dates
                $ordersQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
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
