<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrderReportsController extends Controller
{
          public function index()
          {
              // Fetch total and canceled amounts grouped by client
              $orderStats = Order::select(
                  'client_id',
                  DB::raw('SUM(total) as total_amount'), // Total order amount for each client
                  DB::raw('SUM(CASE WHEN order_status = "cancelled" THEN total ELSE 0 END) as canceled_amount') // Total canceled order amount for each client
              )
              ->with('client') // Eager load client relationship
              ->groupBy('client_id') // Group by client_id
              ->get();
          
              // Calculate overall totals
              $grandTotalAmount = Order::sum('total'); // Grand total amount of all orders
              $grandCanceledAmount = Order::where('order_status', 'cancelled')->sum('total'); // Grand total amount of canceled orders
          
              return view('applicationDashboard.pages.orderReports.index', compact('orderStats', 'grandTotalAmount', 'grandCanceledAmount'));
          }
          

          public function clientOrders($clientId, $startDate = null, $endDate = null)
{
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
