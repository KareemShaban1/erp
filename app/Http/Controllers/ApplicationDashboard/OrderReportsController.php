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
                DB::raw('SUM(total) as total_amount'), // Total order amount for each client
                DB::raw('SUM(CASE WHEN order_status = "cancelled" THEN total ELSE 0 END) as canceled_amount') // Total canceled order amount for each client
            )
                ->with('client') // Eager load client relationship
                ->groupBy('client_id');

            // Apply filters for start_date and end_date
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }

            // Apply search filter
            if ($request->search['value']) {
                $query->whereHas('client.contact', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search['value'] . '%');
                });
            }

            // Calculate grand totals
            $grandTotalAmountQuery = Order::select(
                DB::raw('SUM(total) as total_amount'),
                DB::raw('SUM(CASE WHEN order_status = "cancelled" THEN total ELSE 0 END) as canceled_amount')
            );

            if ($request->start_date && $request->end_date) {
                $grandTotalAmountQuery->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }

            $grandTotals = $grandTotalAmountQuery->first();

            return DataTables::of($query)
                ->addColumn('client_name', function ($row) {
                    // Add the link to the client name
                    return '<a href="' . route('client.orders', $row->client->id) . '">' .
                        ($row->client->contact->name ?? 'Unknown Client') .
                        '</a>';
                })
                ->addColumn('client_location', function ($row) {
                    return $row->client->business_location->name ?? 'Unknown Location';
                })
                ->addColumn('total_amount', function ($row) {
                    return number_format($row->total_amount, 2);
                })
                ->addColumn('canceled_amount', function ($row) {
                    return number_format($row->canceled_amount, 2);
                })
                ->with([
                    'grand_total_amount' => number_format($grandTotals->total_amount, 2),
                    'grand_canceled_amount' => number_format($grandTotals->canceled_amount, 2),
                ])
                ->rawColumns(['client_name', 'client_location', 'total_amount', 'canceled_amount'])
                ->make(true);
        }

        return view('applicationDashboard.pages.orderReports.index');
    }





    public function clientOrders($clientId, $startDate = null, $endDate = null)
    {
        if (!auth()->user()->can('orders.reports')) {
            abort(403, 'Unauthorized action.');
        }
        $orders = Order::with(['client', 'businessLocation'])
            ->select([
                'id',
                'number',
                'client_id',
                'payment_method',
                'order_status',
                'payment_status',
                'shipping_cost',
                'sub_total',
                'total',
                'created_at'
            ])
            ->where('client_id', $clientId) // Filter by client ID
            ->latest();

        // Apply date filter if start_date and end_date are provided
        if ($startDate && $endDate) {
            $orders->whereBetween('created_at', [$startDate, $endDate]);
        }

        if (request()->ajax()) {
            return Datatables::of($orders)
                ->addColumn('client_contact_name', function ($order) {
                    return optional($order->client->contact)->name ?? 'N/A';
                })
                ->addColumn('has_delivery', function ($order) {
                    return $order->has_delivery; // Add the delivery status here
                })
                ->make(true);
        }

        return view('applicationDashboard.pages.orderReports.clientOrders');

    }


}
