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

    public function addToCart(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);
        
        $product = SCProduct::where('slug', $request->slug)->firstOrFail();
        $cart = Cart::query()->firstOrCreate(['user_id' => 5]);

        $cartItem = new CartItem([
            'itemable_id' => $product->id,
            'itemable_type' => SCProduct::class,
            'quantity' => 2,
        ]);
        $cart->items()->save($cartItem);

        $cart = $this->loadCartWithAllItems($cart);
        // dd($cart);
        return response()->json(
            [
                'message' => 'Product added to cart successfully',
                'cart' => $cart
            ],
            200
        );
        // dd($cart->calculatedPriceByQuantity());
    }

    private function loadCartWithAllItems(Cart $cart)
    {
        $cartItems = [];
        $cart = $cart->load('items.itemable');

        $cart->items()->each(function ($item) use (&$cartItems) {
            $cartItems[] = SCProductResource::make($item->itemable);
            // dd($cartItems);
            // return $cartItems;
        });

        // dd($cartItems, $cart->items);

        return $cartItems;
    }
}
