<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Delivery\DeliveryCollection;
use App\Http\Resources\Delivery\DeliveryResource;
use App\Http\Resources\Order\OrderCollection;
use App\Models\Category;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderTracking;
use App\Models\Transaction;
use App\Models\User;
use App\Services\API\DeliveryService;
use App\Services\FirebaseClientService;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Utils\EssentialsUtil;

class DeliveryController extends Controller
{

    protected $service;

    public function __construct(DeliveryService $service)
    {
        $this->service = $service;
    }

    public function getNotAssignedOrders(Request $request)
    {
        $notAssignedOrders = $this->service->getNotAssignedOrders($request);

        if ($notAssignedOrders instanceof JsonResponse) {
            return $notAssignedOrders;
        }

        return $notAssignedOrders->additional([
            'code' => 200,
            'status' => 'success',
            'message' => __('message.Categories have been retrieved successfully'),
        ]);
    }

    public function getAssignedOrders(Request $request)
    {
        $assignedOrders = $this->service->getAssignedOrders($request);

        if ($assignedOrders instanceof JsonResponse) {
            return $assignedOrders;
        }

        return $assignedOrders->additional([
            'code' => 200,
            'status' => 'success',
            'message' => __('message.Assigned Orders have been retrieved successfully'),
        ]);
    }




    public function getDeliveryOrders(Request $request)
    {
        $deliveryOrders = $this->service->getDeliveryOrders($request);

        if ($deliveryOrders instanceof JsonResponse) {
            return $deliveryOrders;
        }

        return $deliveryOrders->additional([
            'code' => 200,
            'status' => 'success',
            'message' => __('message.Orders have been retrieved successfully'),
        ]);
    }




    public function assignDelivery(Request $request)
    {
        $deliveryOrders = $this->service->assignDelivery($request);

        if ($deliveryOrders instanceof JsonResponse) {
            return $deliveryOrders;
        }

        return $deliveryOrders->additional([
            'code' => 200,
            'status' => 'success',
            'message' => __('message.Delivery assigned successfully to the order.'),
        ]);
    }

    public function changeOrderStatus($orderId)
    {
        $deliveryOrders = $this->service->changeOrderStatus($orderId);

        if ($deliveryOrders instanceof JsonResponse) {
            return $deliveryOrders;
        }

        return $deliveryOrders->additional([
            'code' => 200,
            'status' => 'success',
            'message' => __('message.Delivery change order status successfully.'),
        ]);
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