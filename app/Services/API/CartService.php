<?php

namespace App\Services\API;

use App\Http\Resources\Cart\CartCollection;
use App\Http\Resources\Cart\CartResource;
use App\Models\Cart;
use App\Models\Client;
use App\Models\Product;
use App\Models\Variation;
use App\Traits\CheckQuantityTrait;
use Illuminate\Support\Facades\Auth;
use App\Services\BaseService;

class CartService extends BaseService
{
    use CheckQuantityTrait;
    /**
     * Get all cart items for the authenticated user.
     */
    public function getCartItems()
{
    try {
        $client = Client::findOrFail(Auth::id());
        $carts = Cart::where('client_id', Auth::id())
            ->with(['product', 'variation.variation_location_details', 'client'])
            ->get();

             // Check if cart is empty
        if ($carts->isEmpty()) {
            return $this->returnJSON([], __('message.Cart is empty'));
        }

        $totalPrice = 0;
        $totalDiscount = 0;
        $multiLocationMessage = false;

        foreach ($carts as $cart) {
            $price = $cart->variation->default_sell_price ?? 0;
            $quantity = $cart->quantity;
            $discount = $cart->variation->discount ?? 0;

            // Check if sufficient quantity is available at client's business location
            $sufficientQuantity = $this->checkSufficientQuantity($cart->variation->variation_location_details, $client->business_location_id, $quantity);

            // If the required quantity is not available, set multi-location message
            if (!$sufficientQuantity) {
                $multiLocationMessage = true;
            }

            $totalPrice += ($price * $quantity);
            $totalDiscount += ($discount * $quantity);
        }

        // Create response with CartCollection and additional data
        $cartCollection = (new CartCollection($carts))
            ->withFullData(true)
            ->setTotals($totalPrice, $totalDiscount);

        // Add multi-location message if applicable
        if ($multiLocationMessage) {
            $cartCollection->setLocationMessage(__('message.Order will be shipped tomorrow due to multiple locations'));
        }

        return $cartCollection;

    } catch (\Exception $e) {
        return $this->handleException($e, __('message.Error happened while listing cart items'));
    }
}

    

    
    

    /**
     * Add a product (and optional variant) to the cart.
     */
    public function addToCart($productId, $variantId, $quantity)
    {
        // Fetch the product and variation
        $product = Product::findOrFail($productId);
        $variation = Variation::findOrFail($variantId);

        // Calculate the current stock available
        $current_stock = $variation->variation_location_details->sum('qty_available');

        // dd($current_stock);

        // Check if the requested quantity exceeds available stock
        if ($quantity > $current_stock) {
            return response()->json([
                'code' => 400,
                'status' => 'failed',
                'message' => __('message.Quantity exceeds available stock'),
                'data' => null
            ], 400);
        }

        // Check if the item already exists in the cart
        $cartItem = Cart::where('client_id', Auth::id())
            ->where('product_id', $productId)
            ->where('variation_id', $variantId)
            ->first();

        if ($cartItem) {
            // If the new quantity after adding exceeds available stock, return an error
            if ($cartItem->quantity + $quantity > $current_stock) {
                return response()->json([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => __('message.Quantity exceeds available stock'),
                    'data' => null
                ], 400);
            }

            // Update quantity if the item already exists in the cart
            $cartItem->quantity += $quantity;
        } else {
            // Create a new cart item
            $cartItem = Cart::create([
                'client_id' => Auth::id(),
                'product_id' => $productId,
                'variation_id' => $variantId,
                'quantity' => $quantity,
                'price' => $variation->default_sell_price,
            ]);
        }

        // Calculate total price (quantity * price)
        if ($cartItem->discount) {
            $cartItem->total = ($cartItem->quantity * $cartItem->price) - $cartItem->discount;
        } else {
            $cartItem->total = $cartItem->quantity * $cartItem->price;
        }

        // Save the cart item with the updated total
        $cartItem->save();

        return $cartItem;
    }


    /**
     * Update the quantity of an item in the cart.
     */
    public function updateCartItem($cartId, $quantity)
    {
        try {
            $cartItem = Cart::where('client_id', Auth::id())->findOrFail($cartId);

            if ($quantity < 1) {
                return response()->json([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => __('message.Quantity must be at least 1'),
                    'data' => null
                ], 400);
            }

            $current_stock = $cartItem->variation->variation_location_details->sum('qty_available');

            if ($quantity > $current_stock) {
                return response()->json([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => __('message.Quantity exceeds available stock'),
                    'data' => null
                ], 400);
            }

            // Update the quantity
            $cartItem->quantity = $quantity;

            // Recalculate total price (quantity * price)
            $cartItem->total = $cartItem->quantity * $cartItem->price;

            // Save the updated cart item
            $cartItem->save();

            return new CartResource($cartItem);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error occurred while updating cart item'));
        }
    }


    /**
     * Remove an item from the cart.
     */
    public function removeCartItem($cartId)
    {
        try {
            $cartItem = Cart::where('client_id', Auth::id())->findOrFail($cartId);

            // If you are using soft deletes, you can soft delete the cart item
            $cartItem->delete();

            return response()->json(['message' => 'Item removed from cart'], 200);
        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error occurred while removing cart item'));
        }
    }


    /**
     * Clear all items from the user's cart.
     */
    public function clearCart()
    {
        Cart::where('client_id', Auth::id())->delete();
        return true;
    }
}
