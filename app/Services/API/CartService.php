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

                    $carts = Cart::where('client_id', Auth::id())->with(['product', 'variation','client'])->get();
        
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
        $cartItem->total = $cartItem->quantity * $cartItem->price;
    
        // Save the cart item with the updated total
        $cartItem->save();
    
        return $cartItem;
    }
    

    /**
     * Update the quantity of an item in the cart.
     */
    public function updateCartItem($cartId, $quantity)
    {
        $cartItem = Cart::where('client_id', Auth::id())->findOrFail($cartId);
        $cartItem->quantity = $quantity;
        $cartItem->save();

        return $cartItem;
    }

    /**
     * Remove an item from the cart.
     */
    public function removeCartItem($cartId)
    {
        $cartItem = Cart::where('client_id', Auth::id())->findOrFail($cartId);
        $cartItem->delete();

        return $cartItem;
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
