<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Binafy\LaravelCart\Models\Cart;
use Binafy\LaravelCart\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCProductResource;
use Illuminate\Support\Str;

class CartController extends Controller
{

    public function getCart(Request $request)
    {

        $user = auth()->user();

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        $cart = $this->loadCartWithAllItems($cart);

        return response()->json(
            [
                'cart' => $cart
            ],
            200
        );
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
}
