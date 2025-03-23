<?php

namespace App\Services\API;

use App\Services\BaseService;
use App\Http\Resources\Order\OrderCollection;
use App\Http\Resources\Order\OrderResource;
use App\Models\Category;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderTracking;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FirebaseClientService;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Utils\EssentialsUtil;


class DeliveryService extends BaseService
{
          protected $essentialsUtil;
          protected $commonUtil;
          protected $FirebaseClientService;
          protected $moduleUtil;


          public function __construct(
                    FirebaseClientService $FirebaseClientService,
                    ModuleUtil $moduleUtil,
                    EssentialsUtil $essentialsUtil,
                    Util $commonUtil
          ) {
                    $this->FirebaseClientService = $FirebaseClientService;
                    $this->moduleUtil = $moduleUtil;
                    $this->essentialsUtil = $essentialsUtil;
                    $this->commonUtil = $commonUtil;

          }

          public function getNotAssignedOrders($request)
          {
                    // Retrieve the authenticated delivery user
                    $delivery = Delivery::find(Auth::user()->id);

                    if (!$delivery) {
                              return response()->json(['message' => 'Delivery user not found'], 404);
                    }

                    // Retrieve all order IDs already assigned to the delivery user in DeliveryOrder
                    $assignedOrderIds = DeliveryOrder::where('delivery_id', $delivery->id)
                              ->pluck('order_id');

                    // Start building the query for unassigned orders
                    $query = Order::where('order_status', 'processing')
                              ->where('business_location_id', $delivery->business_location_id)
                              ->whereNotIn('id', $assignedOrderIds)
                              ->latest();

                    // Apply the order type filter if necessary
                    if ($request->orderType !== 'all') {
                              $query->where('order_type', $request->orderType);
                    }

                    // Execute the query
                    // $orders = $query->latest()->get();

                    $query = $this->withTrashed($query, $request);

                    $orders = $this->withPagination($query, $request);


                    if ($orders->isEmpty()) {
                              return $this->returnJSON([], 'No unassigned orders found for your location');
                    }

                    return (new OrderCollection($orders))
                              ->withFullData(!($request->full_data == 'false'));

          }

          public function getAssignedOrders($request)
          {
                    $delivery = Delivery::where('id', Auth::user()->id)->first();

                    if (!$delivery) {
                              return response()->json(['message' => 'Delivery user not found'], 404);
                    }

                    // Retrieve assigned orders based on the delivery ID in DeliveryOrder
                    $query = Order::
                              where('order_status', 'processing')->
                              whereHas('deliveries', function ($query) use ($delivery) {
                                        $query->where('delivery_id', $delivery->id);
                              })->latest();


                    // Apply the order type filter if necessary
                    if ($request->orderType !== 'all') {
                              $query->where('order_type', $request->orderType);
                    }

                    // Execute the query
                    // $assignedOrders = $query->latest()->get();

                    $query = $this->withTrashed($query, $request);

                    $orders = $this->withPagination($query, $request);

                    return (new OrderCollection($orders))
                              ->withFullData(!($request->full_data == 'false'));

          }




          public function getDeliveryOrders($request)
          {
                    $delivery = Delivery::where('id', Auth::user()->id)->first();

                    if (!$delivery) {
                              return response()->json(['message' => 'Delivery user not found'], 404);
                    }

                    // Retrieve assigned orders based on the delivery ID and status
                    $query = Order::whereHas('deliveries', function ($query) use ($delivery) {
                              $query->where('delivery_id', $delivery->id);
                    })->latest();

                    // Apply status filter if specified and not 'all'
                    if ($request->status !== 'all') {
                              $query->where('order_status', $request->status);
                    }

                    // $assignedOrders = $assignedOrders->latest()->get();

                    $query = $this->withTrashed($query, $request);

                    $orders = $this->withPagination($query, $request);

                    return (new OrderCollection($orders))
                              ->withFullData(!($request->full_data == 'false'));


          }




          public function assignDelivery(Request $request)
          {
                    $request->validate([
                              'order_id' => 'required|exists:orders,id',
                    ]);

                    $deliveryId = Auth::user()->id;
                    $orderId = $request->order_id;

                    $order = Order::find($orderId);

                    // Validate the delivery ID to ensure it exists and is available
                    $delivery = Delivery::where('id', $deliveryId)
                              ->where('status', 'available')  // You can uncomment this if you need to check for an available status
                              ->first();

                    if (!$delivery) {
                              return response()->json([
                                        'success' => false,
                                        'message' => 'Invalid or unavailable delivery selected.',
                              ], 400);
                    }

                    // Update the delivery status to 'assigned'
                    $delivery->status = 'not_available';
                    $delivery->save();

                    // Insert a record into the delivery_orders table to log this assignment
                    $deliveryOrder = DeliveryOrder::create([
                              'delivery_id' => $deliveryId,
                              'order_id' => $orderId,
                              'status' => 'assigned', // The status could be 'assigned' initially
                              'assigned_at' => now(), // Timestamp of assignment
                    ]);

                    $this->moduleUtil->activityLog($order, 'assign_delivery', null, ['order_number' => $order->number, 'delivery_name' => $delivery->contact->name]);

                    return $deliveryOrder;
          }

          public function changeOrderStatus($orderId)
          {
                    // Define allowed statuses
                    $validStatuses = ['shipped', 'completed'];

                    // Retrieve and validate the input status
                    $status = request()->input('order_status');
                    if (!in_array($status, $validStatuses)) {
                              return response()->json([
                                        'success' => false,
                                        'message' => 'Invalid status provided.',
                              ], 400);
                    }

                    $deliveryOrder = DeliveryOrder::where('order_id', $orderId)->first();

                    // Find the order or return 404 if not found
                    $order = Order::findOrFail($orderId);

                    $delivery = Delivery::find($deliveryOrder->delivery_id);

                    // Find the client
                    $client = Client::find($order->client_id);
                    if (!$client) {
                              return response()->json([
                                        'success' => false,
                                        'message' => 'Client not found.',
                              ], 404);
                    }


                    // Get or create the OrderTracking record for this order
                    $orderTracking = OrderTracking::firstOrNew(['order_id' => $order->id]);



                    // Update timestamps and handle specific status actions
                    switch ($status) {
                              case 'shipped':
                                        if ($order->status === 'shipped') {
                                                  return response()->json([
                                                            'success' => false,
                                                            'message' => 'Status is already shipped',
                                                  ], 404);
                                        }
                                        $orderTracking->shipped_at = now();

                                        // Update delivery contact balance
                                        $this->updateDeliveryBalance($order, $delivery);

                                        // Send and store push notification
                                        app(FirebaseClientService::class)->sendAndStoreNotification(
                                                  $client->id,
                                                  $client->fcm_token,
                                                  'Order Status Updated',
                                                  'Your order has been shipped successfully (Order ID: #' . $order->id . ').',
                                                  ['order_id' => $order->id, 'status' => $status]
                                        );

                                        $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status' => 'shipped']);

                                        break;

                              case 'completed':
                                        if ($order->status === 'completed') {
                                                  return response()->json([
                                                            'success' => false,
                                                            'message' => 'Status is already completed',
                                                  ], 404);
                                        }
                                        $orderTracking->completed_at = now();

                                        $delivery->status = 'available';
                                        $delivery->save();

                                        // Send and store push notification
                                        app(FirebaseClientService::class)->sendAndStoreNotification(
                                                  $client->id,
                                                  $client->fcm_token,
                                                  'Order Status Updated',
                                                  'Your order has been completed successfully (Order ID: #' . $order->id . ').',
                                                  ['order_id' => $order->id, 'status' => $status]
                                        );

                                        $this->moduleUtil->activityLog($order, 'change_status', null, ['order_number' => $order->number, 'status' => 'completed']);

                                        break;
                    }

                    // Update the order status
                    $order->order_status = $status;

                    // Save the tracking record
                    $orderTracking->save();

                    $order->save();


                    return new OrderResource($order);

          }

          public function getDeliveryData()
          {
                    $id = Auth::user()->id;

                    $delivery = Delivery::businessId()->find($id);

                    if (!$delivery) {
                              return response()->json([
                                        'success' => false,
                                        'message' => 'Delivery not found',
                              ], 404);
                    }
                    return response()->json([
                              'success' => true,
                              'data' => $delivery,
                              'message' => 'Delivery Data retrieved successfully.',
                    ], 200);
          }

          public function changeDeliveryStatus(Request $request)
          {
                    $request->validate([
                              'status' => 'required|in:available,not_available',
                    ]);

                    // Define allowed statuses
                    $validStatuses = ['available', 'not_available'];

                    // Retrieve and validate the input status
                    $status = request()->input('status');
                    if (!in_array($status, $validStatuses)) {
                              return response()->json([
                                        'success' => false,
                                        'message' => 'Invalid status provided.',
                              ], 400);
                    }

                    $deliveryId = Auth::user()->id;

                    // Validate the delivery ID to ensure it exists and is available
                    $delivery = Delivery::where('id', $deliveryId)
                              ->first();

                    if (!$delivery) {
                              return response()->json([
                                        'success' => false,
                                        'message' => 'Invalid or unavailable delivery selected.',
                              ], 400);
                    }

                    $delivery->status = $status;
                    $delivery->save();
                    return response()->json([
                              'success' => true,
                              'data' => $delivery,
                              'message' => 'Delivery status updated successfully.',
                    ], 200);
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


          public function showData()
          {
                    // $delivery_id = Auth::user()->id;
                    $delivery = Delivery::find(Auth::user()->id);
                    // $id = 
                    $user = User::find($delivery->user_id);
                    $business_id = $user->business_id;

                    if (!$user) {
                              return response()->json([
                                        'success' => false,
                                        'message' => 'Delivery not assigned to user.',
                              ], 400);
                    }


                    $payroll = Transaction::where('business_id', $business_id)
                              ->with(['transaction_for', 'payment_lines'])
                              ->where('expense_for', $user->id)
                              ->orderBy('transaction_date', 'desc') // Order by transaction_date in descending order
                              ->first(); // Fetch the latest transaction
                    // ->findOrFail($id);

                    if (!$payroll) {
                              return response()->json([
                                        'success' => false,
                                        'message' => 'لا يوجد كشف مرتبات لهذا الشهر',
                              ], 400);
                    }

                    $transaction_date = \Carbon::parse($payroll->transaction_date);

                    $department = Category::where('category_type', 'hrm_department')
                              ->find($payroll->transaction_for->essentials_department_id);

                    $designation = Category::where('category_type', 'hrm_designation')
                              ->find($payroll->transaction_for->essentials_designation_id);

                    $month_name = $transaction_date->format('F');
                    $year = $transaction_date->format('Y');
                    $allowances = !empty($payroll->essentials_allowances) ? json_decode($payroll->essentials_allowances, true) : [];
                    $deductions = !empty($payroll->essentials_deductions) ? json_decode($payroll->essentials_deductions, true) : [];
                    $bank_details = json_decode($payroll->transaction_for->bank_details, true);
                    $payment_types = $this->moduleUtil->payment_types();
                    $final_total_in_words = $this->commonUtil->numToIndianFormat($payroll->final_total);

                    $start_of_month = \Carbon::parse($payroll->transaction_date);
                    $end_of_month = \Carbon::parse($payroll->transaction_date)->endOfMonth();

                    $leaves = EssentialsLeave::where('business_id', $business_id)
                              ->where('user_id', $payroll->transaction_for->id)
                              ->whereDate('start_date', '>=', $start_of_month)
                              ->whereDate('end_date', '<=', $end_of_month)
                              ->get();

                    $total_leaves = 0;
                    $days_in_a_month = $start_of_month->daysInMonth;
                    foreach ($leaves as $leave) {
                              $start_date = \Carbon::parse($leave->start_date);
                              $end_date = \Carbon::parse($leave->end_date);
                              $total_leaves += $start_date->diffInDays($end_date) + 1;
                    }

                    $total_work_duration = $this->essentialsUtil->getTotalWorkDuration('hour', $payroll->transaction_for->id, $business_id, $start_of_month->format('Y-m-d'), $end_of_month->format('Y-m-d'));

                    // Fetch expense transactions
                    $expense_transactions = Transaction::where('business_id', $business_id)
                              ->where('expense_for', $payroll->transaction_for->id)
                              ->where('type', 'expense')
                              ->whereBetween('transaction_date', [$start_of_month->format('Y-m-d'), $end_of_month->format('Y-m-d')])
                              ->get();

                    foreach ($expense_transactions as $expense) {
                              // Check if this expense is already in deductions
                              $exists = false;
                              if (isset($deductions['deduction_names'])) {
                                        foreach ($deductions['deduction_names'] as $index => $name) {
                                                  if ($name === __('essentials::lang.expense') && $deductions['deduction_amounts'][$index] == $expense->final_total) {
                                                            $exists = true;
                                                            break;
                                                  }
                                        }
                              }

                              if (!$exists) {
                                        $deductions['deduction_names'][] = __('essentials::lang.expense');
                                        $deductions['deduction_amounts'][] = $expense->final_total;
                                        $deductions['deduction_types'][] = 'fixed';
                                        $deductions['deduction_percents'][] = 0;
                              }
                    }

                    return view('essentials::payroll.delivery_show')
                              ->with(compact('payroll', 'month_name', 'allowances', 'deductions', 'year', 'payment_types', 'bank_details', 'designation', 'department', 'final_total_in_words', 'total_leaves', 'days_in_a_month', 'total_work_duration'));
          }


}