<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\API\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display the cart items.
     */
    public function index()
    {
        $cartItems = $this->cartService->getCartItems();
        if ($cartItems instanceof JsonResponse) {
          return $cartItems;
      }

      return $cartItems->additional([
          'code' => 200,
          'status' => 'success',
          'message' =>  __('message.Cart Items have been retrieved successfully'),
      ]);
    }

    /**
     * Add an item to the cart.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:variations,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = $this->cartService->addToCart(
            $request->product_id,
            $request->variant_id,
            $request->quantity
        );

        if ($cartItem instanceof JsonResponse) {
            return $cartItem;
        }

       
        return $this->returnJSON($cartItem, __('message.Cart Item has been created successfully'));

    }

    /**
     * Update a cart item.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1', 
        ]);

        $cartItem = $this->cartService->updateCartItem($id, $request->quantity);

        if ($cartItem instanceof JsonResponse) {
            return $cartItem;
        }

        return $cartItem->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Cart Items have been retrieved successfully'),
        ]);
        
        // return $this->returnJSON($cartItem, __('message.Cart Item has been updated successfully'));
    }

    /**
     * Remove a specific cart item.
     */
    public function destroy($id)
    {
        $cartItem = $this->cartService->removeCartItem($id);

        if ($cartItem instanceof JsonResponse) {
            return $cartItem;
        }

        return $cartItem->additional([
            'code' => 200,
            'status' => 'success',
            'message' =>  __('message.Cart Items have been deleted successfully'),
        ]);
        // return $this->returnJSON($cartItem, __('message.Cart Item has been deleted successfully'));
    }

    /**
     * Clear all items from the cart.
     */
    public function clear()
    {
        $cartItem = $this->cartService->clearCart();

        if ($cartItem instanceof JsonResponse) {
            return $cartItem;
        }

        return $this->returnJSON(null, __('message.Cart Item has been cleared successfully'));
    }
}
