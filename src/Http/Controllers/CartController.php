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
use Rahat1994\SparkCommerce\Models\SCOrder;

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

    public function addToCart(Request $request, $refernce = null)
    {
        $request->validate([
            'slug' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        if (auth()->check()) {
            try {
                $product = SCProduct::where('slug', $request->slug)->firstOrFail();
            } catch (\Throwable $th) {
                dd($th);
                return response()->json(
                    [
                        'message' => 'Product not found',
                        'cart' => []
                    ],
                    404
                );
            }


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
        } else {
            $project = strval(config("app.name"));
            $hashIds = new Hashids($project);

            if (is_null($refernce)) {

                $cart = new SCAnonymousCart();
                $cart->cart_content = [];
                $cart->save();
                $refernce = $hashIds->encode($cart->id);
            }
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
                        'cart' => []
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
                $cartItems[$productIndex]['quantity'] = $request->quantity;
            } else {
                $cartItems[] = [
                    'slug' => $product->slug,
                    'quantity' => $request->quantity
                ];
            }

            $anonymosCart->cart_content = $cartItems;
            $anonymosCart->save();
            $cart = $this->loadAnonymousCartWithAllItems($anonymosCart);

            return response()->json(
                [
                    'message' => 'Product added to cart successfully',
                    'refernce' => $refernce,
                    'cart' => $cart
                ],
                200
            );
        }
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

    public function associateAnonymousCart(Request $request, $refernce = null)
    {
        // this controller method will be used to associate the anonymous cart with the user cart

        $project = strval(config("app.name"));
        $hashIds = new Hashids($project);

        $anonymousCartId = $hashIds->decode($refernce);

        if (empty($anonymousCartId)) {
            throw new Exception('Cart not found');
        }

        $anonymosCart = SCAnonymousCart::findOrFail($anonymousCartId[0]);

        $user = auth()->user();

        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        $cartItems = $anonymosCart->cart_content;

        foreach ($cartItems as $item) {
            $product = SCProduct::where('slug', $item['slug'])->firstOrFail();

            // check if product already exists in the cart
            $cartItem = $cart->items()->where('itemable_id', $product->id)->first();

            // if product already exists in the cart, update the quantity
            if ($cartItem) {
                $cartItem->quantity = $item['quantity'];
                $cartItem->save();
            } else {
                $cartItem = new CartItem([
                    'itemable_id' => $product->id,
                    'itemable_type' => SCProduct::class,
                    'quantity' => $item['quantity'],
                ]);
                $cart->items()->save($cartItem);
            }
        }

        $anonymosCart->delete();

        $cart = $this->loadCartWithAllItems($cart);

        return response()->json(
            [
                'message' => 'Cart associated successfully',
                'cart' => $cart
            ],
            200
        );
    }

    private function loadAnonymousCartWithAllItems(SCAnonymousCart $cart)
    {
        $cartItems = [];
        $cart = $cart->cart_content;
        // dd($cart);

        foreach ($cart as $item) {

            $product = SCProduct::where('slug', $item['slug'])->firstOrFail();

            $temp = [];
            $temp['quantity'] = $item['quantity'];
            $temp['item'] = SCProductResource::make($product);

            $cartItems[] = $temp;
        }

        return $cartItems;
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
        $request->validate([
            "items" => "required",
            "shipping_address" => "required",
            "billing_address" => "required",
            "shipping_method" => "required",
            "total_amount" => "required",
            'tracking_number' => "required",
            'transaction_id' => "required",
            "discount" => "required",
            "user_id" => "required",
            "order_number" => "required",
            "status" => "required",
            "payment_status" => "required",
            "shipping_status" => "required",
            "payment_method" => "required"
        ]);

        $user = auth()->user();
        // Assuming you have a Cart model and it's already filled with items
        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        if (!$cart || $cart->items->isEmpty()) {
            throw new Exception("Cart is empty or not found.");
        }

        // Begin database transaction to ensure data integrity
        DB::beginTransaction();
        try {
            // Create a new Order
            $order = new SCOrder();
            $order->user_id = $user->id;
            $order->status = 'pending';
            $order->items = $cart->items;

            $order->shipping_address = $request->shipping_address;
            $order->billing_address = $request->billing_address;
            $order->shipping_method = $request->shipping_method;
            $order->total_amount = $request->total_amount;
            $order->tracking_number = $request->tracking_number;
            $order->transaction_id = $request->transaction_id;
            $order->discount = $request->discount;
            $order->user_id = $request->user_id;
            $order->order_number = $request->order_number;
            $order->status = $request->status;
            $order->payment_status = $request->payment_status;
            $order->shipping_status = $request->shipping_status;
            $order->payment_method = $request->payment_method;
            // Add other order details like shipping address, etc.
            $order->save();

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
