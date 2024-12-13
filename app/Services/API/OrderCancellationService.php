<?php

namespace App\Services\API;

use App\Http\Resources\OrderCancellation\OrderCancellationCollection;
use App\Http\Resources\OrderCancellation\OrderCancellationResource;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderTracking;
use App\Models\Transaction;
use App\Models\TransactionSellLine;
use App\Notifications\OrderCancellationCreatedNotification;
use App\Notifications\OrderTransferCreatedNotification;
use App\Services\BaseService;
use App\Services\FirebaseService;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderCancellationService extends BaseService
{
    protected $moduleUtil;
    protected $orderService;
    protected $productUtil;
    protected $transactionUtil;
    protected $transferQuantityService;

    public function __construct(
        ModuleUtil $moduleUtil,
        ProductUtil $productUtil,
        OrderService $orderService,
        TransferQuantityService $transferQuantityService,
        TransactionUtil $transactionUtil

    ) {
        $this->moduleUtil = $moduleUtil;
        $this->orderService = $orderService;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->transferQuantityService = $transferQuantityService;
    }
    /**
     * Get all OrderCancellations with filters and pagination for DataTables.
     */

    public function list(Request $request)
    {

        try {

            $query = OrderCancellation::query();

            $query = $this->withTrashed($query, $request);

            $OrderCancellations = $this->withPagination($query, $request);

            return (new OrderCancellationCollection($OrderCancellations))
                ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing OrderCancellations'));
        }
    }

    public function getAuthClientOrderCancellations(Request $request)
    {

        try {

            $query = OrderCancellation::where('client_id', Auth::user()->id);

            $query = $this->withTrashed($query, $request);

            $OrderCancellations = $this->withPagination($query, $request);

            return (new OrderCancellationCollection($OrderCancellations))
                ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing OrderCancellations'));
        }
    }

    public function show($id)
    {

        try {
            $OrderCancellation = OrderCancellation::businessId()->find($id);

            if (!$OrderCancellation) {
                return null;
            }
            return $OrderCancellation;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing OrderCancellation'));
        }
    }

    /**
     * Create a new OrderCancellation.
     */
    public function store($data)
    {
        $data['client_id'] = Auth::id();
        $data['status'] = 'requested';
        $data['requested_at'] = now();

        try {
            // Find the order by ID and ensure it exists
            $order = Order::find($data['order_id']);
            $orderTracking = OrderTracking::where('order_id', $data['order_id'])->first();
            if (!$order) {
                return $this->returnJSON(null, __('message.Order not found'), 404);
            }

            $order_transfer = Order::where('parent_order_id', $order->id)
                ->where('order_type', 'order_transfer')
                ->whereIn('order_status', ['pending', 'processing'])
                ->get();

            // Check if the order status allows cancellation
            if (in_array($order->order_status, ['pending'])) {
                // Set order status to 'cancelled' and save
                $order->order_status = 'cancelled';
                $orderTracking->cancelled_at = now();


                foreach ($order->orderItems as $item) {

                    $this->productUtil->updateProductQuantity(
                        $order->business_location_id,
                        $item->product_id,
                        $item->variation_id,
                        $item->quantity
                    );
                }

                foreach ($order_transfer as $transfer) {
                    foreach ($transfer->orderItems as $item) {
                        $this->transferQuantityService->transferQuantityForCancellation(
                            $transfer,
                            $item,
                            $transfer->client,
                            $transfer->to_business_location_id,
                            $transfer->from_business_location_id,
                            $item->quantity
                        );
                    }
                    $transfer->order_status = 'cancelled';
                    $transfer->save();
                }

                $business_id = $order->client->contact->business->id;

                // dd($order->id);
                $parent_sell_transaction = Transaction::
                    where('order_id', $order->id)
                    ->where('type', 'sell')
                    ->first();
                \Log::info('parent_sell_transaction', [$parent_sell_transaction]);
                $products = [];
                foreach ($order->orderItems as $item) {
                    $transaction_sell_line = TransactionSellLine::
                        where('product_id', $item->product_id)
                        ->where('transaction_id', $parent_sell_transaction->id)
                        ->first();
                    \Log::info('transaction_sell_line', [$transaction_sell_line]);

                    $products[] = [
                        'sell_line_id' => $transaction_sell_line->id, // Adjust this field name to match your schema
                        'quantity' => $item->quantity,
                        'unit_price_inc_tax' => $item->price, // Include price if applicable
                    ];

                    $transferOrder = Order::where('id',$item->order_id)
                    ->first();
                    $input = [
                        'transaction_id' => $parent_sell_transaction->id,
                        'order_id' => $transferOrder->id,
                        'invoice_no' => null,
                        // 'transaction_date' => Carbon::now(),
                        'products' => $products,
                        "discount_type" => null,
                        "discount_amount" => $item->discount,
                        "tax_id" => null,
                        "tax_amount" => "0",
                        "tax_percent" => "0",
                    ];

                    \Log::info('input', [$input]);


                    $this->transactionUtil->addSellReturnForCancellation($input, $business_id, 1);
                }


                $order->save();
                $orderTracking->save();
            } else {
                // Return a response indicating the status cannot be changed
                return $this->returnJSON(null, __('message.Order status is :status, it can\'t be changed', ['status' => $order->order_status]));
            }

            // Create the OrderCancellation record
            $orderCancellation = OrderCancellation::create($data);


            // Notify admins and users about the order
            $admins = $this->moduleUtil->get_admins($order->client->contact->business_id);
            $users = $this->moduleUtil->getBusinessUsers($order->client->contact->business_id, $order);

            \Notification::send($admins, new OrderCancellationCreatedNotification($order));
            \Notification::send($users, new OrderCancellationCreatedNotification($order));


            // Return the created OrderCancellation as a resource
            return new OrderCancellationResource($orderCancellation);

        } catch (\Exception $e) {
            // Handle any unexpected exceptions
            return $this->handleException($e, __('message.Error occurred while storing OrderCancellation'));
        }
    }

    /**
     * Update the specified OrderCancellation.
     */
    public function update($request, $OrderCancellation)
    {

        try {

            // Validate the request data
            $data = $request->validated();

            $OrderCancellation->update($data);

            return new OrderCancellationResource($OrderCancellation);


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while updating OrderCancellation'));
        }
    }

    public function destroy($id)
    {
        try {

            $OrderCancellation = OrderCancellation::find($id);

            if (!$OrderCancellation) {
                return null;
            }
            $OrderCancellation->delete();
            return $OrderCancellation;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting OrderCancellation'));
        }
    }

    public function restore($id)
    {
        try {
            $OrderCancellation = OrderCancellation::withTrashed()->findOrFail($id);
            $OrderCancellation->restore();
            return new OrderCancellationResource($OrderCancellation);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring OrderCancellation'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $OrderCancellation = OrderCancellation::withTrashed()
                ->findOrFail($id);

            $OrderCancellation->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting OrderCancellation'));
        }
    }

}
