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
    // public function getCartItems()
    // {
    //     try {
    //         $client = Client::findOrFail(Auth::id());
    //         $carts = Cart::where('client_id', Auth::id())
    //             ->with(['product', 'variation.variation_location_details', 'client'])
    //             ->get();

    //         // Check if the cart is empty
    //         if ($carts->isEmpty()) {
    //             return $this->returnJSON([], __('message.Cart is empty'));
    //         }

    //         $totalPrice = 0;
    //         $totalDiscount = 0;
    //         $totalAfterDiscount = 0;
    //         $multiLocationMessage = false;

    //         foreach ($carts as $cart) {
    //             // $price = $cart->variation->default_sell_price ?? 0;
    //             $price = $cart->variation->sell_price_inc_tax ?? 0;
    //             $quantity = $cart->quantity;

    //             // Fetch the latest discount for the variation
    //             $latestDiscount = $cart->variation->discounts()->latest('id')->first();
    //             $discountAmount = $latestDiscount ? $latestDiscount->discount_amount : 0;

    //             // Calculate total discount for this cart item
    //             $itemDiscount = $discountAmount * $quantity;

    //             // Check if sufficient quantity is available at the client's business location
    //             $sufficientQuantity = $this->checkSufficientQuantity($cart->variation->variation_location_details, $client->business_location_id, $quantity);

    //             // If the required quantity is not available, set multi-location message
    //             if (!$sufficientQuantity) {
    //                 $multiLocationMessage = true;
    //             }

    //             $totalPrice += ($price * $quantity);
    //             $totalDiscount += $itemDiscount;
    //             $totalAfterDiscount += (($price * $quantity) - $itemDiscount);
    //         }

    //         // Create response with CartCollection and additional data
    //         $cartCollection = (new CartCollection($carts))
    //             ->withFullData(true)
    //             ->setTotals($totalPrice, $totalDiscount, $totalAfterDiscount);



    //         // Add multi-location message if applicable
    //         if ($multiLocationMessage) {
    //             $cartCollection->setLocationMessage(__('message.Order will be shipped tomorrow due to multiple locations'));
    //         }

    //         return $cartCollection;

    //     } catch (\Exception $e) {
    //         return $this->handleException($e, __('message.Error happened while listing cart items'));
    //     }
    // }

    public function getCartItems()
    {
        try {
            $client = Client::findOrFail(Auth::id())
            ;
            $carts = Cart::where('client_id', Auth::id())
                ->with(['product',
                'variation',
                'variation.variation_location_details', 'client'])
                ->get();

            // Check if the cart is empty
            if ($carts->isEmpty()) {
                return $this->returnJSON([], __('message.Cart is empty'));
            }

            $totalPrice = 0;
            $totalDiscount = 0;
            $totalAfterDiscount = 0;
            $multiLocationMessage = false;

            foreach ($carts as $cart) {
                $variation = $cart->variation;
                $quantity = $cart->quantity;
                

                // Determine the base price: Use client_selling_price if available, otherwise use sell_price_inc_tax
                $basePrice = $variation->client_selling_price ?? $variation->sell_price_inc_tax;

                // Fetch the latest discount for the variation
                $latestDiscount = $variation->discounts()->latest('id')->first();

                // Apply discount only if price is based on sell_price_inc_tax
                $discountAmount = ($basePrice === $variation->sell_price_inc_tax && $latestDiscount)
                    ? $latestDiscount->discount_amount
                    : 0;

                // Calculate total discount for this cart item
                $itemDiscount = $discountAmount * $quantity;

                // Check if sufficient quantity is available at the client's business location
                $sufficientQuantity = $this->checkSufficientQuantity(
                    $variation->variation_location_details,
                    $client->business_location_id,
                    $quantity
                );

                // If the required quantity is not available, set multi-location message
                if (!$sufficientQuantity) {
                    $multiLocationMessage = true;
                }

                // Calculate totals
                $totalPrice += ($basePrice * $quantity);
                $totalDiscount += $itemDiscount;
                $totalAfterDiscount += (($basePrice * $quantity) - $itemDiscount);
            }

            // Create response with CartCollection and additional data
            $cartCollection = (new CartCollection($carts))
                ->withFullData(true)
                ->setTotals($totalPrice, $totalDiscount, $totalAfterDiscount);

            // Add multi-location message if applicable
            if ($multiLocationMessage) {
                $cartCollection->setLocationMessage(__('message.Order will be shipped tomorrow due to multiple locations'));
            }

            return $cartCollection;

        } catch (\Exception $e) {
            return $this->handleException($e, __('message.Error happened while listing cart items'));
        }
    }




    // public function addToCart($productId, $variantId, $quantity)
    // {

    //     // Fetch the product and variation
    //     $product = Product::findOrFail($productId);
    //     $variation = Variation::findOrFail($variantId);

    //     // Fetch the latest discount for the variation
    //     $latestDiscount = $variation->discounts()->latest('id')->first();

    //     // Calculate the current stock available
    //     $current_stock = $variation->variation_location_details->sum('qty_available');

    //     // Check if the requested quantity exceeds available stock
    //     if ($quantity > $current_stock) {
    //         return response()->json([
    //             'code' => 400,
    //             'status' => 'failed',
    //             'message' => __('message.Quantity exceeds available stock'),
    //             'data' => null
    //         ], 400);
    //     }

    //     // Check if the item already exists in the cart
    //     $cartItem = Cart::where('client_id', Auth::id())
    //         ->where('product_id', $productId)
    //         ->where('variation_id', $variantId)
    //         ->first();

    //     if ($cartItem) {
    //         // Set the new total quantity (prevent exceeding available stock)
    //         $newQuantity = $cartItem->quantity + $quantity;

    //         if ($newQuantity > $current_stock) {
    //             return response()->json([
    //                 'code' => 400,
    //                 'status' => 'failed',
    //                 'message' => __('message.Quantity exceeds available stock'),
    //                 'data' => null
    //             ], 400);
    //         }

    //         // Update the quantity and recalculate totals
    //         $cartItem->quantity = $newQuantity;
    //     } else {
    //         // Create a new cart item
    //         $cartItem = Cart::create([
    //             'client_id' => Auth::id(),
    //             'product_id' => $productId,
    //             'variation_id' => $variantId,
    //             'quantity' => $quantity,
    //             'price' => $variation->client_selling_price ? $variation->client_selling_price : $variation->sell_price_inc_tax,
    //         ]);
    //     }

    //     // Apply discount if available
    //     $discountAmount = $latestDiscount ? $latestDiscount->discount_amount : 0;

    //     // Calculate total price (quantity * price) - discount
    //     $cartItem->discount = $discountAmount;
    //     $cartItem->discount_type = $latestDiscount->discount_type;
    //     $cartItem->total = ($cartItem->quantity * $cartItem->price) - $discountAmount;

    //     // Save the cart item with the updated total
    //     $cartItem->save();

    //     return $cartItem;
    // }

    public function addToCart($productId, $variantId, $quantity)
    {
        // Fetch the product and variation
        $product = Product::findOrFail($productId);
        $variation = Variation::findOrFail($variantId);

        // Fetch the latest discount for the variation
        $latestDiscount = $variation->discounts()->latest('id')->first();

        // Calculate the current stock available
        $current_stock = $variation->variation_location_details->sum('qty_available');

        // Check if the requested quantity exceeds available stock
        if ($quantity > $current_stock) {
            return response()->json([
                'code' => 400,
                'status' => 'failed',
                'message' => __('message.Quantity exceeds available stock'),
                'data' => null
            ], 400);
        }

        // Determine the base price: Use client_selling_price if available, otherwise use sell_price_inc_tax
        $basePrice = $variation->client_selling_price ?? $variation->sell_price_inc_tax;

        // Check if the item already exists in the cart
        $cartItem = Cart::where('client_id', Auth::id())
            ->where('product_id', $productId)
            ->where('variation_id', $variantId)
            ->first();

        if ($cartItem) {
            // Set the new total quantity (prevent exceeding available stock)
            $newQuantity = $cartItem->quantity + $quantity;

            if ($newQuantity > $current_stock) {
                return response()->json([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => __('message.Quantity exceeds available stock'),
                    'data' => null
                ], 400);
            }

            // Update the quantity
            $cartItem->quantity = $newQuantity;
        } else {
            // Create a new cart item
            $cartItem = Cart::create([
                'client_id' => Auth::id(),
                'product_id' => $productId,
                'variation_id' => $variantId,
                'quantity' => $quantity,
                'price' => $basePrice, // Store the correct price
            ]);
        }

        // Apply discount **only if price is based on sell_price_inc_tax**
        $discountAmount = ($basePrice === $variation->sell_price_inc_tax && $latestDiscount)
            ? $latestDiscount->discount_amount
            : 0;

        // Calculate total price (quantity * price) - discount
        $cartItem->discount = $discountAmount;
        $cartItem->discount_type = $discountAmount > 0 ? $latestDiscount->discount_type : null;
        $cartItem->total = ($cartItem->quantity * $basePrice) - $discountAmount;

        // Save the cart item with the updated total
        $cartItem->save();

        return $cartItem;
    }




    /**
     * Update the quantity of an item in the cart.
     */
    // public function updateCartItem($cartId, $quantity)
    // {
    //     try {
    //         $cartItem = Cart::where('client_id', Auth::id())->findOrFail($cartId);

    //         if ($quantity < 1) {
    //             return response()->json([
    //                 'code' => 400,
    //                 'status' => 'failed',
    //                 'message' => __('message.Quantity must be at least 1'),
    //                 'data' => null
    //             ], 400);
    //         }

    //         $current_stock = $cartItem->variation->variation_location_details->sum('qty_available');

    //         if ($quantity > $current_stock) {
    //             return response()->json([
    //                 'code' => 400,
    //                 'status' => 'failed',
    //                 'message' => __('message.Quantity exceeds available stock'),
    //                 'data' => null
    //             ], 400);
    //         }

    //         // Update the quantity
    //         $cartItem->quantity = $quantity;

    //         // Recalculate total price (quantity * price)
    //         $cartItem->total = $cartItem->quantity * $cartItem->price;

    //         // Save the updated cart item
    //         $cartItem->save();

    //         return $this->getCartItems();

    //         // return new CartResource($cartItem);
    //     } catch (\Exception $e) {
    //         return $this->handleException($e, __('message.Error occurred while updating cart item'));
    //     }
    // }

    public function updateCartItem($cartId, $quantity)
    {
        try {
            $cartItem = Cart::where('client_id', Auth::id())->findOrFail($cartId);

            // Validate quantity
            if ($quantity < 1) {
                return response()->json([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => __('message.Quantity must be at least 1'),
                    'data' => null
                ], 400);
            }

            // Get the variation and available stock
            $variation = $cartItem->variation;
            $current_stock = $variation->variation_location_details->sum('qty_available');

            // Check if the requested quantity exceeds available stock
            if ($quantity > $current_stock) {
                return response()->json([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => __('message.Quantity exceeds available stock'),
                    'data' => null
                ], 400);
            }

            // Determine the base price: Use client_selling_price if available, otherwise use sell_price_inc_tax
            $basePrice = $variation->client_selling_price ?? $variation->sell_price_inc_tax;

            // Fetch the latest discount
            $latestDiscount = $variation->discounts()->latest('id')->first();

            // Apply discount **only if price is based on sell_price_inc_tax**
            $discountAmount = ($basePrice === $variation->sell_price_inc_tax && $latestDiscount)
                ? $latestDiscount->discount_amount
                : 0;

            // Update quantity
            $cartItem->quantity = $quantity;

            // Recalculate total price (quantity * price) - discount
            $cartItem->discount = $discountAmount;
            $cartItem->discount_type = $discountAmount > 0 ? $latestDiscount->discount_type : null;
            $cartItem->total = ($cartItem->quantity * $basePrice) - $discountAmount;

            // Save the updated cart item
            $cartItem->save();

            return $this->getCartItems();

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

            return $this->getCartItems();

            // return response()->json(['message' => 'Item removed from cart'], 200);
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
