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
//         return response()->json(['cart_items' => $cartItems], 200);
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

        return response()->json(['cart_item' => $cartItem], 201);
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

        return response()->json(['cart_item' => $cartItem], 200);
    }

    /**
     * Remove a specific cart item.
     */
    public function destroy($id)
    {
        $cartItem = $this->cartService->removeCartItem($id);

        return response()->json(['message' => 'Item removed successfully.'], 200);
    }

    /**
     * Clear all items from the cart.
     */
    public function clear()
    {
        $this->cartService->clearCart();

        return response()->json(['message' => 'Cart cleared successfully.'], 200);
    }
}
