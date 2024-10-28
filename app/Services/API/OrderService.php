<?php

namespace App\Services\API;

use App\Http\Resources\Order\OrderCollection;
use App\Http\Resources\Order\OrderResource;
use App\Jobs\TransferProductJob;
use App\Models\Cart;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Services\BaseService;
use App\Traits\CheckQuantityTrait;
use App\Traits\HelperTrait;
use App\Traits\UploadFileTrait;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService extends BaseService
{
    use UploadFileTrait, HelperTrait ,CheckQuantityTrait;

    protected $productUtil;
    protected $transactionUtil;
    protected $cartService;
    protected $orderTrackingService;
    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil,
    OrderTrackingService $orderTrackingService,
    CartService $cartService)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cartService = $cartService;
        $this->orderTrackingService = $orderTrackingService;
    }
    /**
     * Get all Orders with filters and pagination for DataTables.
     */
    public function list(Request $request)
    {

        try {

            $query = Order::query();

            $query = $this->withTrashed($query, $request);

            $orders = $this->withPagination($query, $request);

            return (new OrderCollection($orders))
            ->withFullData(!($request->full_data == 'false'));


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing Orders'));
        }
    }

    public function show($id) {

        try {
            $order = Order::findOrFail($id);

            if(!$order) {
                return null;
            }
            return new OrderResource($order);

        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while showing Order'));
        }
    }

    /**
     * Create a new Order.
     */
    public function store()
    {
        try {
            $carts = Cart::where('client_id', Auth::id())
                ->with(['product', 'variation.variation_location_details', 'client'])
                ->get();

    
            $client = Client::findOrFail(Auth::id());
            $orderTotal = $carts->sum('total');
    
            $order = Order::create([
                'client_id' => Auth::id(),
                'sub_total' => $orderTotal,
                'total' => $orderTotal,
                'business_location_id'=>$client->business_location_id,
            ]);

            $this->orderTrackingService->store($order,'pending');

            $this->cartService->clearCart();
    
            foreach ($carts as $cart) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cart->product_id,
                    'variation_id' => $cart->variation_id,
                    'quantity' => $cart->quantity,
                    'price' => $cart->price,
                    'discount' => $cart->discount,
                    'sub_total' => $cart->total,
                ]);
    
                $this->handleQuantityTransfer($cart, $client, $order, $orderItem);

            }


           

    
            return new OrderResource($order);
    
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while storing Order'));
        }
    }
    
    /**
     * Handle quantity transfer between locations based on client needs.
     */
    protected function handleQuantityTransfer($cart, $client, $order ,$orderItem)
    {
        $requiredQuantity = $cart->quantity;
        $quantityTransferred = 0;
        $sufficientQuantity = false;
    
        foreach ($cart->variation->variation_location_details as $locationDetail) {
            $locationId = $locationDetail->location->id;
    
            if ($locationId === $client->business_location_id) {
                if ($locationDetail->qty_available >= $requiredQuantity) {
                    $sufficientQuantity = true;
                    break;
                } else {
                    $quantityTransferred = $locationDetail->qty_available;
                    $requiredQuantity -= $quantityTransferred;
                }
            }
        }
    
        if (!$sufficientQuantity && $requiredQuantity > 0) {
            foreach ($cart->variation->variation_location_details as $locationDetail) {
                $locationId = $locationDetail->location->id;
    
                if ($locationId !== $client->business_location_id && $locationDetail->qty_available > 0) {
                    $transferQuantity = min($requiredQuantity, $locationDetail->qty_available);
                    $this->transferQuantity($order,$orderItem, $client, $locationId, $client->business_location_id, $transferQuantity);
    
                    $quantityTransferred += $transferQuantity;
                    $requiredQuantity -= $transferQuantity;
    
                    if ($requiredQuantity <= 0) {
                        $sufficientQuantity = true;
                        break;
                    }
                }
            }
        }
    
        if (!$sufficientQuantity) {
            throw new \Exception('Insufficient quantity for product ' . $cart->product_id);
        }
    }

    protected function transferQuantity($order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)
    {
        // Dispatch the job with a 10-minute delay
        TransferProductJob::dispatch($order, $orderItem, $client, $fromLocationId, $toLocationId, $quantity)
            ->delay(now());

        \Log::info("TransferProductJob dispatched for Order: {$order->id}, OrderItem: {$orderItem->id}");
    }
    
    /**
     * Update the specified Order.
     */
    public function update($request,$order)
    {

        try {

        // Validate the request data
        $data = $request->validated();

        $order->update($data);

        return new OrderResource($order);


    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while updating Order'));
    }
    }

    public function destroy($id)
    {
        try {

            $order = Order::find($id);

            if(!$order) {
                return null;
            }
            $order->delete();
            return $order;


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while deleting Order'));
        }
    }

    public function restore($id)
    {
        try {
            $order = Order::withTrashed()->findOrFail($id);
            $order->restore();
            return new OrderResource($order);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while restoring Order'));
        }
    }

    public function forceDelete($id)
    {
        try {
            $order = Order::withTrashed()
                ->findOrFail($id);

            $order->forceDelete();
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while force deleting Order'));
        }
    }


    public function checkQuantityAndLocation(){

        try {
            $client = Client::findOrFail(Auth::id());
            $carts = Cart::where('client_id', Auth::id())
                ->with(['product', 'variation.variation_location_details', 'client'])
                ->get();

                 // Check if cart is empty
        if ($carts->isEmpty()) {
            return $this->returnJSON(null, __('message.Cart is empty'));
        }

            $multiLocationMessage = false;
    
            foreach ($carts as $cart) {
                $quantity = $cart->quantity;
    
                // Check if sufficient quantity is available at client's business location
                $sufficientQuantity = $this->checkSufficientQuantity($cart->variation->variation_location_details, $client->business_location_id, $quantity);
    
                // If the required quantity is not available, set multi-location message
                if (!$sufficientQuantity) {
                    $multiLocationMessage = true;
                }

            }
    
            // Add multi-location message if applicable
            if ($multiLocationMessage) {
                return $this->returnJSON(null, __('message.Order will be shipped tomorrow due to multiple locations')); ;
            }
    
            return $this->returnJSON(null, __('message.Order will be shipped today')); ;

    
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing cart items'));
        }
    }
    
}
