<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

use Binafy\LaravelCart\Models\Cart;
use Binafy\LaravelCart\Models\CartItem;
use Exception;
use Hashids\Hashids;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rahat1994\SparkCommerce\Models\SCAnonymousCart;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Http\Resources\SCMVProductResource;

trait CanHandleCart
{
    public function getCartWithItemObjects(int $userId)
    {
        $cart = Cart::query()->firstOrCreate(['user_id' => $userId]);
        return $this->loadCartWithAllItems($cart);
    }

    public function addItemToCart(Request $request)
    {
        $user = $this->user();
        try {
            $product = SCProduct::where('slug', $request->slug)->firstOrFail();
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'message' => 'Product not found',
                    'cart' => [],
                ],
                404
            );
        }

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        // check if product already exists in the cart
        $cartItem = $cart->items()->where('itemable_id', $product->id)->first();

        // if product already exists in the cart, update the quantity
        if ($cartItem) {
            $cartItem->quantity = $request->quantity;
            $cartItem->save();
        } else {

            if ($this->productHasDifferentVendor($product, $cart)) {
                return response()->json(
                    [
                        'message' => 'You can not add products from different vendors in the same cart',
                        'cart' => $this->loadCartWithAllItems($cart),
                    ],
                    400
                );
            }

            $cartItem = new CartItem([
                'itemable_id' => $product->id,
                'itemable_type' => SCProduct::class,
                'quantity' => $request->quantity,
            ]);
            $cart->items()->save($cartItem);
        }
        $cart = $this->loadCartWithAllItems($cart);

        
        return response()->json(
            [
                'message' => 'Product added to cart successfully',
                'cart' => $cart,
            ],
            200
        );
        
    }

    public function removeFromCart(Request $request, $slug, $refernce = null)
    {
        $product = SCProduct::where('slug', $slug)->firstOrFail();

        $user = $this->user();

        if ($user !== null) {
            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

            $cartItem = $cart->items()->where('itemable_id', $product->id)->get();
            // dd();
            $cart->removeItem($cartItem[0]);

            $cart = $this->loadCartWithAllItems($cart);

            return response()->json(
                [
                    'message' => 'Product removed from cart successfully',
                    'cart' => $cart,
                ],
                200
            );
        } else {

            try {
                $project = strval(config('app.name'));
                $hashIds = new Hashids($project);
                $anonymousCartId = $hashIds->decode($refernce);
                // dd($anonymousCartId);
                if (empty($anonymousCartId)) {
                    throw new Exception('Cart not found');
                }
                $anonymosCart = SCAnonymousCart::findOrFail($anonymousCartId[0]);

                try {
                    $product = SCProduct::where('slug', $request->slug)->firstOrFail();
                } catch (\Throwable $th) {
                    return response()->json(
                        [
                            'message' => 'Product not found',
                            'cart' => [],
                        ],
                        404
                    );
                }

                // check if product already exists in the cart
                $cartItems = $anonymosCart->cart_content;

                // update the quantity if product already exists in the cart in json

                $productIndex = -1;

                foreach ($cartItems as $key => $item) {
                    if ($item['slug'] == $product->slug) {
                        $productIndex = $key;

                        break;
                    }
                }

                if ($productIndex != -1) {
                    unset($cartItems[$productIndex]);
                } else {
                    return response()->json(
                        [
                            'message' => 'Product not found in cart',
                            'refernce' => $refernce,
                        ],
                        200
                    );
                }

                $anonymosCart->cart_content = $cartItems;
                $anonymosCart->save();
                $cart = $this->loadAnonymousCartWithAllItems($anonymosCart);

                return response()->json(
                    [
                        'message' => 'Product removed from cart successfully',
                        'refernce' => $refernce,
                        'cart' => $cart,
                    ],
                    200
                );
            } catch (\Throwable $th) {

                return response()->json(
                    [
                        'message' => 'Cart not found',
                        'cart' => [],
                    ],
                    404
                );
            }
        }
    }

    public function clearUserCart(Request $request)
    {
        $user = $this->user();

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        $cart->emptyCart();

        return response()->json(
            [
                'message' => 'Cart cleared successfully',
                'cart' => [],
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
            $temp['item'] = SCMVProductResource::make($item->itemable);

            $cartItems[] = $temp;
        });

        return $cartItems;
    }
}
