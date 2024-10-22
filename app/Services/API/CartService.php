<?php

namespace App\Services\API;

use App\Http\Resources\Cart\CartCollection;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Variation;
use Illuminate\Support\Facades\Auth;
use App\Services\BaseService;

class CartService extends BaseService
{
    /**
     * Get all cart items for the authenticated user.
     */
    public function getCartItems()
    {
        try {

            $carts = Cart::where('client_id', Auth::id())->with(['product', 'variation', 'client'])->get();

            return (new CartCollection($carts))
                ->withFullData(true);


        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing categories'));
        }
    }

    /**
     * Add a product (and optional variant) to the cart.
     */
    public function addToCart($productId, $variantId, $quantity)
    {
        // Fetch the product and variant
        $product = Product::findOrFail($productId);
        $variation = Variation::findOrFail($variantId);


        // Check if the item already exists in the cart
        $cartItem = Cart::where('client_id', Auth::id())
            ->where('product_id', $productId)
            ->where('variation_id', $variantId)
            ->first();

            if ($quantity > $variation->variation_location_details->qty_available) {
                return response()->json(['error' => 'Quantity exceeds available stock'], 400);
            }
            

        if ($cartItem) {
            // Update quantity if the item already exists
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
                return response()->json(['error' => 'Quantity must be at least 1'], 400);
            }

            // Update the quantity
            $cartItem->quantity = $quantity;

            // Recalculate total price (quantity * price)
            $cartItem->total = $cartItem->quantity * $cartItem->price;

            // Save the updated cart item
            $cartItem->save();

            return $cartItem;
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
