<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Binafy\LaravelCart\Models\Cart;
use Binafy\LaravelCart\Models\CartItem;
use Exception;
use Hashids\Hashids;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCProductResource;
use Illuminate\Support\Str;
use Rahat1994\SparkCommerce\Models\SCAnonymousCart;

class CartController extends Controller
{

    public function getCart(Request $request, $refernce = null)
    {

        if (auth()->check()) {
            $user = auth()->user();
            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
            $cart = $this->loadCartWithAllItems($cart);

            return response()->json(
                [
                    'cart' => $cart
                ],
                200
            );
        } else {
            try {
                $project = strval(config("app.name"));
                $hashIds = new Hashids($project);
                $anonymousCartId = $hashIds->decode($refernce);
                // dd($anonymousCartId);
                if (empty($anonymousCartId)) {
                    throw new Exception('Cart not found');
                }
                $cart = SCAnonymousCart::findOrFail($anonymousCartId[0]);
            } catch (\Throwable $th) {
                // dd($th);
                return response()->json(
                    [
                        'message' => 'Cart not found',
                        'cart' => []
                    ],
                    404
                );
            }
        }
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = SCProduct::where('slug', $request->slug)->firstOrFail();

        $user = auth()->user();

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        // check if product already exists in the cart
        $cartItem = $cart->items()->where('itemable_id', $product->id)->first();

        // if product already exists in the cart, update the quantity
        if ($cartItem) {
            $cartItem->quantity = $request->quantity;
            $cartItem->save();
        } else {
            $cartItem = new CartItem([
                'itemable_id' => $product->id,
                'itemable_type' => SCProduct::class,
                'quantity' => $request->quantity,
            ]);
            $cart->items()->save($cartItem);
        }
        $cart = $this->loadCartWithAllItems($cart);
        // dd($cart);
        return response()->json(
            [
                'message' => 'Product added to cart successfully',
                'cart' => $cart
            ],
            200
        );
    }

    public function removeFromCart(Request $request, $slug)
    {
        $product = SCProduct::where('slug', $slug)->firstOrFail();

        $user = auth()->user();

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        $cartItem = $cart->items()->where('itemable_id', $product->id)->get();
        // dd();
        $cart->removeItem($cartItem[0]);

        $cart = $this->loadCartWithAllItems($cart);
        return response()->json(
            [
                'message' => 'Product removed from cart successfully',
                'cart' => $cart
            ],
            200
        );
    }

    public function clearUserCart(Request $request)
    {
        $user = auth()->user();

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        $cart->emptyCart();

        return response()->json(
            [
                'message' => 'Cart cleared successfully',
                'cart' => []
            ],
            200
        );
    }

    private function loadCartWithAllItems(Cart $cart)
    {
        $cartItems = [];
        $cart = $cart->load('items.itemable');
        // dd($cart);
        $cart->items()->each(function ($item) use (&$cartItems) {
            $temp = [];
            $temp['quantity'] = $item->quantity;
            $temp['item'] = SCProductResource::make($item->itemable);

            $cartItems[] = $temp;
        });

        return $cartItems;
    }

    public function checkout(Request $request)
    {
        // Assuming you have a Cart model and it's already filled with items
        $cart = Cart::where('id', $cartId)->where('user_id', $userId)->first();

        if (!$cart || $cart->items->isEmpty()) {
            throw new Exception("Cart is empty or not found.");
        }

        // Begin database transaction to ensure data integrity
        DB::beginTransaction();
        try {
            // Create a new Order
            $order = new Order();
            $order->user_id = $userId;
            $order->status = 'pending';
            // Add other order details like shipping address, etc.
            $order->save();

            // Loop through cart items and add them to the order
            foreach ($cart->items as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $cartItem->product_id;
                $orderItem->quantity = $cartItem->quantity;
                // Add other item details like price, etc.
                $orderItem->save();

                // Optionally, update inventory here
            }

            // Process payment and update order status if successful

            // Mark cart as processed or delete it
            $cart->delete(); // or mark as processed

            DB::commit();

            // Send order confirmation to user

            return $order;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
