<?php

namespace App\Http\Controllers\ApplicationDashboard;

use App\Events\TransactionPaymentAdded;
use App\Http\Controllers\Controller;
use App\Models\BusinessLocation;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\DeliveryOrder;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderTracking;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\TransactionSellLine;
use App\Services\API\OrderService;
use App\Services\API\QuantityTransferService;
use App\Services\FirebaseClientService;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class RefundOrderController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;

    protected $transactionUtil;

    protected $orderService;

    protected $quantityTransferService;


    public function __construct(
        ModuleUtil $moduleUtil,
        TransactionUtil $transactionUtil,
        OrderService $orderService,
        QuantityTransferService $quantityTransferService
    ) {
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->orderService = $orderService;
        $this->quantityTransferService = $quantityTransferService;
    }

    public function index()
    {
        if (!auth()->user()->can('orders_refund.view')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $status = request()->get('status', 'all'); // Default to 'all' if not provided
            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');
            $search = request()->get('search')['value'];
            $businessLocation = request()->get('business_location');
            $deliveryName = request()->get('delivery_name');
            $paymentStatus = request()->get('payment_status', 'all');

            // Validate status
            $validStatuses = ['all', 'pending', 'processing', 'shipped', 'cancelled', 'completed'];
            if (!in_array($status, $validStatuses)) {
                $status = 'all';
            }

            // Fetch filtered data
            return $this->fetchOrders($status, $startDate, $endDate, $search, $businessLocation, $deliveryName, $paymentStatus);
        }

        $business_locations = BusinessLocation::BusinessId()->active()->select('id', 'name')->get();

        return view('applicationDashboard.pages.refundOrders.index', compact('business_locations'));
    }

    /**
     * Fetch order refunds based on filters.
     */
    private function fetchOrders($status, $startDate = null, $endDate = null, $search = null, $businessLocation = null, $deliveryName = null, $paymentStatus = null)
    {
        $business_id = request()->session()->get('user.business_id');
        $user_locations = Auth::user()->permitted_locations();

        $query = Order::with([
            'client.contact',
            'parentOrder',
            'businessLocation',
            'transaction' => function ($query) {
                $query->where('type', 'sell_return'); // Filter transactions with type 'sell'
            }
        ])
            ->select([
                'orders.id',
                'orders.number',
                'orders.order_type',
                'orders.client_id',
                'orders.business_location_id',
                'orders.parent_order_id',
                'orders.payment_method',
                'orders.order_status',
                'orders.payment_status',
                'orders.shipping_cost',
                'orders.sub_total',
                'orders.total',
                'orders.user_id',
                'orders.created_at'
            ])
            ->where('orders.order_type', 'order_refund');
        // ->latest();

        if (Auth::check()) {
            $query->where(function ($subQuery) {
                $subQuery->whereNull('orders.user_id') // Allow orders where user_id is null
                    ->orWhere('orders.user_id', Auth::user()->id); // Also include orders assigned to the user
            });
        }

        $query = $query->latest();

        // Apply status filter
        if ($status !== 'all') {
            $query->where('orders.order_status', $status);
        }

        if ($businessLocation) {
            $query->where('orders.business_location_id', $businessLocation);
        }

        if ($paymentStatus !== 'all') {
            $query->where('orders.payment_status', $paymentStatus);
        }

        if ($user_locations !== "all") {
            $query->where(function ($query) use ($user_locations) {
                $query->whereIn('orders.business_location_id', $user_locations);
            });
        }

        if ($deliveryName) {
            $query->whereHas('deliveries.contact', function ($query) use ($deliveryName) {
                $query->where('contacts.name', 'like', "%{$deliveryName}%");
            });
        }

        // Apply date filter
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $query->whereDate('orders.created_at', $startDate);
            } else {
                $endDate = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('orders.created_at', [$startDate, $endDate]);
            }
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('orders.id', 'like', "%{$search}%")
                    ->orWhere('orders.number', 'like', "%{$search}%")
                    ->orWhereHas('client.contact', function ($query) use ($search) {
                        $query->where('contacts.name', 'like', "%{$search}%")
                            ->orWhere('contacts.mobile', 'like', "%{$search}%");
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
            ->addColumn('invoice_no', function ($order) {
                if ($order->transaction) {
                    return $order->transaction->invoice_no ?? 'N/A';
                }
            })
            ->addColumn('business_location_name', function ($order) {
                if ($order->businessLocation) {
                    return $order->businessLocation->name ?? 'N/A';
                }
            })
            ->addColumn('client_contact_name', function ($order) {
                try {
                    if ($order->client && $order->client->contact) {
                        return $order->client->contact->name ?? 'N/A';
                    }
                    return 'N/A';
                } catch (\Exception $e) {
                    \Log::error('Error getting client contact name: ' . $e->getMessage());
                    return 'Error';
                }
            })
            ->addColumn('client_contact_mobile', function ($order) {
                try {
                    if ($order->client && $order->client->contact) {
                        return $order->client->contact->mobile ?? 'N/A';
                    }
                    return 'N/A';
                } catch (\Exception $e) {
                    \Log::error('Error getting client contact mobile: ' . $e->getMessage());
                    return 'Error';
                }
            })
            ->filterColumn('client_contact_name', function ($query, $keyword) {
                $query->whereHas('client', function ($query) use ($keyword) {
                    $query->whereHas('contact', function ($query) use ($keyword) {
                        $query->where('contacts.name', 'like', "%{$keyword}%");
                    });
                });
            })
            ->filterColumn('client_contact_mobile', function ($query, $keyword) {
                $query->whereHas('client', function ($query) use ($keyword) {
                    $query->whereHas('contact', function ($query) use ($keyword) {
                        $query->where('contacts.mobile', 'like', "%{$keyword}%");
                    });
                });
            })
            ->addColumn('has_delivery', function ($order) {
                return $order->has_delivery; // Add the delivery status here
            })
            ->addColumn('delivery_name', function ($order) {
                if ($order->has_delivery) {
                    // Assuming `deliveries` is a relationship on the Order model
                    return $order->deliveries->pluck('contact.name')->implode(', ') ?: __('lang_v1.delivery_assigned');
                }
            })
            ->addColumn('parent_order_number', function ($order) {
                // dd($order);
                return $order->parentOrder->number ?? 'N/A';
            })
            ->make(true);
    }



    public function changeOrderStatus($orderId)
    {
        if (!auth()->user()->can('orders_refund.changeStatus')) {
            abort(403, 'Unauthorized action.');
        }
        $status = request()->input('order_status');

        $order = Order::findOrFail($orderId);



        // Check if an OrderTracking already exists for the order
        $orderTracking = OrderTracking::firstOrNew(['order_id' => $order->id]);

        $deliveryOrder = DeliveryOrder::where('order_id', $orderId)->first();

        $delivery = Delivery::find($deliveryOrder->delivery_id);


        // Set the tracking status timestamp based on the status provided
        switch ($status) {
            case 'pending':
                $orderTracking->pending_at = now();
                $this->moduleUtil->activityLog(
                    $order,
                    'change_status',
                    null,
                    ['order_number' => $order->number, 'status' => 'pending', 'order_type', $order->order_type]
                );
                break;
            case 'processing':
                $order->user_id = Auth::user()->id;
                $orderTracking->processing_at = now();
                // Send and store push notification
                app(FirebaseClientService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Status Changed',
                    'Your order has been processed successfully.',
                    [
                        'order_id' => $order->id,
                        'status' => $order->status
                    ]
                );
                $this->moduleUtil->activityLog(
                    $order,
                    'change_status',
                    null,
                    ['order_number' => $order->number, 'status' => 'processing', 'order_type', $order->order_type]
                );

                break;
            case 'shipped':
                $this->updateDeliveryBalance($order, $delivery);
                // Send and store push notification
                app(FirebaseClientService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Status Changed',
                    'Your order has been shipped successfully.',
                    [
                        'order_id' => $order->id,
                        'status' => $order->status
                    ]
                );
                $orderTracking->shipped_at = now();
                $this->moduleUtil->activityLog(
                    $order,
                    'change_status',
                    null,
                    ['order_number' => $order->number, 'status' => 'shipped', 'order_type', $order->order_type]
                );
                break;
            case 'cancelled':
                $orderTracking->cancelled_at = now();
                $this->moduleUtil->activityLog(
                    $order,
                    'change_status',
                    null,
                    ['order_number' => $order->number, 'status' => 'cancelled', 'order_type', $order->order_type]
                );
                break;
            case 'completed':
                $orderTracking->completed_at = now();
                // Send and store push notification
                app(FirebaseClientService::class)->sendAndStoreNotification(
                    $order->client->id,
                    $order->client->fcm_token,
                    'Order Status Changed',
                    'Your order has been completed successfully.',
                    [
                        'order_id' => $order->id,
                        'status' => $order->status
                    ]
                );
                $this->moduleUtil->activityLog(
                    $order,
                    'change_status',
                    null,
                    ['order_number' => $order->number, 'status' => 'completed', 'order_type', $order->order_type]
                );
                // Handle product details from order->order_items

                $business_id = $order->client->contact->business->id;
                $parent_sell_transaction = Transaction::
                    where('order_id', $order->parent_order_id)
                    ->where('type', 'sell')
                    ->first();
                $products = [];
                foreach ($order->orderItems as $item) {

                    $transaction_sell_line = TransactionSellLine::
                        where('product_id', $item->product_id)
                        ->where('transaction_id', $parent_sell_transaction->id)
                        ->first();
                    $products[] = [
                        'sell_line_id' => $transaction_sell_line->id, // Adjust this field name to match your schema
                        'quantity' => $item->quantity,
                        'unit_price_inc_tax' => $item->price, // Include price if applicable
                    ];
                }

                $input = [
                    'transaction_id' => $parent_sell_transaction->id,
                    'invoice_no' => null,
                    'order_id' => $order->id,
                    'transaction_date' => Carbon::now(),
                    'products' => $products,
                    "discount_type" => null,
                    "discount_amount" => "0.00",
                    "tax_id" => null,
                    "tax_amount" => "0",
                    "tax_percent" => "0",
                    // 'shipping_charges'=>$order->shipping_cost,
                ];

                \Log::info('sale_refund_data', [$input]);

                $this->transactionUtil->addSellReturnForRefund($input, $business_id, 1, true);
                foreach ($order->orderItems as $item) {
                    $this->quantityTransferService->transferQuantity($order, $item, $order->client, $order->business_location_id, 1, $item->quantity);
                }
                //     public function transferQuantity($order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)

                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        $order->order_status = $status;
        $order->save();
        // Save the order tracking record (it will either update or create)
        $orderTracking->save();

        return response()->json(['success' => true, 'message' => 'Order status updated successfully.']);
    }


    public function changePaymentStatus($orderId)
    {
        if (!auth()->user()->can('orders_refund.changePayment')) {
            abort(403, 'Unauthorized action.');
        }
        $status = request()->input('payment_status'); // Retrieve status from the request

        $order = Order::findOrFail($orderId);

        $deliveryOrder = DeliveryOrder::where('order_id', $orderId)->first();
        $delivery = Delivery::find($deliveryOrder->delivery_id);


        $transaction = Transaction::
            where('type', 'sell_return')->
            where('location_id', $order->business_location_id)->
            where('order_id', $order->id)
            ->first();

        // If transaction is null, do not change status
        if (!$transaction) {
            \Log::info('transaction_error', [$transaction]);
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found. Payment status was not updated.'
            ], 400);
        }

        $order->payment_status = $status;
        $order->save();

        $saleReturnPaymentData = [
            'transaction_id' => $transaction->id,
            'business_id' => $order->client->contact->business_id,
            'amount' => $order->total,
            'business_location_id' => $order->business_location_id,
            'method' => 'cash',
            'note' => ''
        ];

        \Log::info('saleReturnPaymentData', [$saleReturnPaymentData]);

        switch ($status) {
            case 'pending':
                $this->moduleUtil->activityLog(
                    $order,
                    'change_payment_status',
                    null,
                    ['order_number' => $order->number, 'status' => 'pending', 'order_type', $order->order_type]
                );
                break;
            case 'paid':
                $this->moduleUtil->activityLog(
                    $order,
                    'change_payment_status',
                    null,
                    ['order_number' => $order->number, 'status' => 'paid', 'order_type', $order->order_type]
                );
                if ($deliveryOrder) {
                    $deliveryOrder->payment_status = 'paid';
                    $deliveryOrder->save();

                    if ($delivery && $delivery->contact) {
                        $delivery->contact->balance += $order->total;
                        $delivery->contact->save();
                    }
                }
                $this->makeSaleReturnPayment($saleReturnPaymentData);

                break;
            case 'failed':
                $this->moduleUtil->activityLog(
                    $order,
                    'change_payment_status',
                    null,
                    ['order_number' => $order->number, 'status' => 'failed', 'order_type', $order->order_type]
                );
                break;
            default:
                throw new \InvalidArgumentException("Invalid status: $status");
        }

        return response()->json(['success' => true, 'message' => 'Order Payment status updated successfully.']);
    }



    public function getOrderRefundDetails($orderId)
    {
        // Fetch activity logs related to the order
        $activityLogs = Activity::with(['subject'])
            ->leftJoin('users as u', function ($join) {
                $join->on('u.id', '=', 'activity_log.causer_id')
                    ->where('activity_log.causer_type', '=', 'App\Models\User');
            })
            ->leftJoin('clients as c', function ($join) {
                $join->on('c.id', '=', 'activity_log.causer_id')
                    ->where('activity_log.causer_type', '=', 'App\Models\Client');
            })
            ->leftJoin('deliveries as d', function ($join) {
                $join->on('d.id', '=', 'activity_log.causer_id')
                    ->where('activity_log.causer_type', '=', 'App\Models\Delivery');
            })
            ->leftJoin('contacts as contact', function ($join) {
                $join->on('contact.id', '=', 'c.contact_id')
                    ->orOn('contact.id', '=', 'd.contact_id');
            })
            ->where('subject_type', 'App\Models\Order')
            ->where('subject_id', $orderId)
            ->select(
                'activity_log.*',
                DB::raw("
                CASE 
                    WHEN u.id IS NOT NULL THEN CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''), ' (user)')
                    WHEN c.id IS NOT NULL THEN CONCAT(COALESCE(contact.name, ''), ' (client)')
                    WHEN d.id IS NOT NULL THEN CONCAT(COALESCE(contact.name, ''), ' (delivery)')
                    ELSE 'Unknown'
                END as created_by
            ")
            )
            ->get();

        // Fetch the order along with related data
        $order = Order::with([
            'client.contact',
            'businessLocation',
            'orderItems',
            'delivery',
            'transaction'
        ])->find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ]);
        }
        $parentOrder = Order::find($order->parent_order_id);


        $refunds = []; // Array to store all refund details

        // Iterate through each order item and check for refund details
        foreach ($parentOrder->orderItems as $item) {
            // Get refunds for this order item
            $itemRefunds = OrderRefund::
            with('order_item')->
            where('order_item_id', $item->id)->get();

            // Sum up the refund amounts
            $refundAmount = $itemRefunds->sum('amount') ?? 0;

            // Calculate the remaining quantity
            $item->remaining_quantity = $item->quantity - $refundAmount;


            // Store refund details in the array
            if ($itemRefunds->isNotEmpty()) {
                $refunds = array_merge($refunds, $itemRefunds->toArray());
            }
        }

        return response()->json([
            'success' => true,
            'order' => $order,
            'refunds' => $refunds,
            'activityLogs' => $activityLogs,
        ]);
    }




    /**
    * Update the delivery contact balance based on the order total.
    
    *
    * @param Order $order
    * @return void
    */
    private function updateDeliveryBalance($order, $delivery)
    {

        if ($delivery && $delivery->contact) {
            $delivery->contact->balance -= $order->total;
            $delivery->contact->save();
        }

    }

    protected function makeSaleReturnPayment($salePaymentData)
    {
        try {
            $business_id = $salePaymentData['business_id'];
            $transaction_id = $salePaymentData['transaction_id'];
            $transaction = Transaction::where('business_id', $business_id)
                ->with(['contact'])->findOrFail($transaction_id);

            $location = BusinessLocation::find($salePaymentData['business_location_id']);

            $transaction_before = $transaction->replicate();

            if ($transaction->payment_status != 'paid') {
                // $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                // 'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                // 'cheque_number', 'bank_account_number']);
                $salePaymentData['paid_on'] = Carbon::now();
                $salePaymentData['transaction_id'] = $transaction->id;
                $salePaymentData['amount'] = $this->transactionUtil->num_uf($salePaymentData['amount']);
                // $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                $salePaymentData['created_by'] = 1;
                $salePaymentData['payment_for'] = $transaction->contact_id;

                // $salePaymentData['account_id'] =2;

                if (!empty($location->default_payment_accounts)) {
                    $default_payment_accounts = json_decode(
                        $location->default_payment_accounts,
                        true
                    );
                    // Check for cash account and set account_id
                    if (!empty($default_payment_accounts['cash']['is_enabled']) && !empty($default_payment_accounts['cash']['account'])) {
                        $salePaymentData['account_id'] = $default_payment_accounts['cash']['account'] ?? 1;
                    }
                }


                $prefix_type = 'purchase_payment';
                if (in_array($transaction->type, ['sell', 'sell_return'])) {
                    $prefix_type = 'sell_payment';
                } elseif (in_array($transaction->type, ['expense', 'expense_refund'])) {
                    $prefix_type = 'expense_payment';
                }

                DB::beginTransaction();

                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                //Generate reference number
                $salePaymentData['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                //Pay from advance balance
                $payment_amount = $salePaymentData['amount'];
                // $contact_balance = !empty($transaction->contact) ? $transaction->contact->balance : 0;
                // if ($inputs['method'] == 'advance' && $inputs['amount'] > $contact_balance) {
                //     throw new AdvanceBalanceNotAvailable(__('lang_v1.required_advance_balance_not_available'));
                // }

                if (!empty($salePaymentData['amount'])) {
                    $tp = TransactionPayment::create($salePaymentData);
                    $salePaymentData['transaction_type'] = $transaction->type;
                    event(new TransactionPaymentAdded($tp, $salePaymentData));
                }

                //update payment status
                $payment_status = $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total);
                $transaction->payment_status = $payment_status;

                $this->transactionUtil->activityLog($transaction, 'payment_edited', $transaction_before);

                DB::commit();
            }

            $output = [
                'success' => true,
                'msg' => __('purchase.payment_added_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $msg = __('messages.something_went_wrong');

            \Log::info('error', [$e]);

            $output = [
                'success' => false,
                'msg' => $msg
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }
}
