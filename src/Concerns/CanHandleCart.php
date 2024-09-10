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

        $cart = $this->mutateDataBeforeLoadingCartWithAllItems($cart);

        $cartData = $this->loadCartWithAllItems($cart);

        return $cartData;
    }

    public function addItemToCart(Request $request)
    {
        $user = $this->user();
        try {

            $product = SCProduct::where('slug', $request->slug)->firstOrFail();
            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
            $cartItem = $cart->items()->where('itemable_id', $product->id)->first();    

            if ($cartItem) {
                $this->beginDatabaseTransaction();

                $quantity = $this->mutateQuantityBeforeUpdatingCartItem($cartItem, $request);

                $cartItem->quantity = $quantity;
                $cartItem->save();
                
                $this->commitDatabaseTransaction();

                $this->callHook('afterUpdatingCartItem');
            } else {

                $this->beginDatabaseTransaction();

                $data = $this->beforeAddingItemToCart($cart, $product, $request);
    
                $cartItem = new CartItem($data);

                $cart->items()->save($cartItem);

                $this->callHook('afterAddingItemToCart');

                $this->commitDatabaseTransaction();
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
        catch (\Throwable $exception) {
            throw $exception;
        }        
    }

    public function removeItemFromCart(Request $request, $slug, $refernce = null)
    {
        $product = SCProduct::where('slug', $slug)->firstOrFail();

        $user = $this->user();

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
        $cartItem = $cart->items()->where('itemable_id', $product->id)->get();
        $cart->removeItem($cartItem[0]);

        $cart = $this->loadCartWithAllItems($cart);

        return response()->json(
            [
                'message' => 'Product removed from cart successfully',
                'cart' => $cart,
            ],
            200
        );
        
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
        $this->callHook('beforeLoadingCartWithAllItems');

        $cartItems = [];
        $cart = $cart->load('items.itemable');
        // dd($cart);
        $cart->items()->each(function ($item) use (&$cartItems) {
            $temp = [];
            $temp['quantity'] = $item->quantity;
            $temp['item'] = SCMVProductResource::make($item->itemable);

            $cartItems[] = $temp;
        });

        $this->callHook('afterLoadingCartWithAllItems');

        return $cartItems;
        
    }

    public function mutateDataBeoforeLoadingAllItems($cart){
        return $cart;
    }

    public function mutateDataBeforeLoadingCartWithAllItems($cart)
    {
        return $cart;
    }

    protected function mutateQuantityBeforeUpdatingCartItem($cartItem, $request)
    {
        return $request->quantity;
    }

    protected function beforeAddingItemToCart($cart, $product, $request)
    {
        return [
            'itemable_id' => $product->id,
            'itemable_type' => SCProduct::class,
            'quantity' => $request->quantity,
        ];
    }
}
