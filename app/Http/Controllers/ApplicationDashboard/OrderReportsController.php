<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderReportsController extends Controller
{
          public function index()
          {
              // Fetch total and canceled amounts grouped by client
              $orderStats = Order::select(
                  'client_id',
                  DB::raw('SUM(total) as total_amount'), // Sum of total amounts for all orders
                  DB::raw('SUM(CASE WHEN order_status = "canceled" THEN total ELSE 0 END) as canceled_amount') // Sum of total for canceled orders
              )
              ->with('client') // Eager load client relationship
              ->groupBy('client_id')
              ->get();
          
              // Calculate overall totals
              $grandTotalAmount = Order::sum('total'); // Grand total amount of all orders
              $grandCanceledAmount = Order::where('order_status', 'canceled')->sum('total'); // Grand total amount of canceled orders
          
              return view('applicationDashboard.pages.orderReports.index', compact('orderStats', 'grandTotalAmount', 'grandCanceledAmount'));
          }
          
          
}
