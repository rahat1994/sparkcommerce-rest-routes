<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Binafy\LaravelCart\Models\Cart;
use Binafy\LaravelCart\Models\CartItem;
use Exception;
use Hashids\Hashids;
use Illuminate\Container\Attributes\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Http\Resources\SCMVProductResource;
use Illuminate\Support\Str;
use Pest\Plugins\Verbose;
use Rahat1994\SparkCommerce\Models\SCAnonymousCart;
use Rahat1994\SparkCommerce\Models\SCCoupon;
use Rahat1994\SparkCommerce\Models\SCOrder;
use Rahat1994\SparkcommerceMultivendor\Models\SCMVVendor;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCOrderResource;

class CartController extends Controller
{

    protected function user()
    {
        return Auth::guard('sanctum')->user();
    }

    public function getUsersCart(int $userId)
    {
        $cart = Cart::query()->firstOrCreate(['user_id' => $userId]);
        return $this->loadCartWithAllItems($cart);
    }

    public function getAnonymousCart($refernce)
    {
        $project = strval(config("app.name"));
        $hashIds = new Hashids($project);
        $anonymousCartId = $hashIds->decode($refernce);
        // dd($anonymousCartId);
        if (empty($anonymousCartId)) {
            throw new Exception('Cart not found');
        }
        $cart = SCAnonymousCart::findOrFail($anonymousCartId[0]);

        return $this->loadAnonymousCartWithAllItems($cart);
    }

    public function getCartAccordingToLoginType($refernce)
    {
        $user = $this->user();
        if ($user !== null) {
            return $this->getUsersCart($user->id);
        } else {
            try {
                return $this->getAnonymousCart($refernce);
            } catch (\Throwable $th) {
                return response()->json(
                    [
                        'message' => 'Error retrieving cart',
                        'cart' => []
                    ],
                    500
                );
            }
        }
    }

    public function getCart(Request $request, $refernce = null)
    {
        try {
            $cart = $this->getCartAccordingToLoginType($refernce);
            return response()->json(['cart' => $cart], 200);
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'message' => 'Error retrieving cart',
                    'cart' => []
                ],
                500
            );
        }
    }

    public function addToCart(Request $request, $refernce = null)
    {
        $request->validate([
            'slug' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'replace_existing' => 'boolean'
        ]);

        $replaceExisting = is_null($request->replace_existing) ? false : true;

        $user = $this->user();
        if ($user !== null) {
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
                            'cart' => $this->loadCartWithAllItems($cart)
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

    public function productHasDifferentVendor($product, $cart)
    {
        $cartItems = $cart->items()->get();
        foreach ($cartItems as $item) {
            if ($item->itemable->vendor_id != $product->vendor_id) {
                return true;
            }
        }
        return false;
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
                    unset($cartItems[$productIndex]);
                } else {
                    return response()->json(
                        [
                            'message' => 'Product not found in cart',
                            'refernce' => $refernce
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
                        'cart' => $cart
                    ],
                    200
                );
            } catch (\Throwable $th) {

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

    public function decodeAnonymousCartReferenceId($refernce)
    {
        $project = strval(config("app.name"));
        $hashIds = new Hashids($project);
        $anonymousCartId = $hashIds->decode($refernce);

        return $anonymousCartId;
    }

    public function clearUserCart(Request $request)
    {
        $user = $this->user();

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

    public function associateAnonymousCart(Request $request)
    {

        $request->validate([
            'reference' => 'required|string',
        ]);
        // this controller method will be used to associate the anonymous cart with the user cart

        $refernce = $request->reference;
        $anonymousCartId = $this->decodeAnonymousCartReferenceId($refernce);

        if (empty($anonymousCartId)) {
            throw new Exception('Cart not found');
        }

        $anonymosCart = SCAnonymousCart::findOrFail($anonymousCartId[0]);

        $user = $this->user();

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
            $temp['item'] = SCMVProductResource::make($product);

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
            $temp['item'] = SCMVProductResource::make($item->itemable);

            $cartItems[] = $temp;
        });

        return $cartItems;
    }

    public function validateCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'reference' => 'string|sometimes'
        ]);
        $couponData = $this->couponData($request->coupon_code);

        if ($couponData) {

            $cart = $this->getCartAccordingToLoginType($request->refernce);

            // now process the cart and apply the coupon



            return response()->json(
                [
                    'message' => 'Coupon applied successfully',
                    'cart' => []
                ],
                200
            );
        } else {
            return response()->json(
                [
                    'message' => 'Invalid coupon code',
                    'cart' => []
                ],
                400
            );
        }
    }

    protected function couponData(string $couponCode)
    {
        try {
            $coupon = SCCoupon::where('coupon_code', $couponCode)
                ->firstOrFail();
        } catch (\Throwable $th) {
            FacadesLog::error($th->getMessage());
            return false;
        }

        // validate coupons
        return false;
    }

    protected function applyCoupon($cart, $coupon)
    {
        // apply coupon to cart
        $couponType = $coupon->coupon_type;

        // check the end date & start date of the coupon
        $couponEndDate = $coupon->end_date;
        $couponStartDate = $coupon->start_date;

        if ($couponEndDate < now() || $couponStartDate > now()) {
            throw new Exception('Coupon is not valid');
        }
        // check the minimum amount of the coupon
        $cartTotalAmount = $cart->total_amount;
        $couponMinimumAmount = $coupon->minimum_amount;

        if ($cartTotalAmount < $couponMinimumAmount) {
            throw new Exception('Cart total amount is less than the minimum amount required for the coupon');
        }

        // check the maximum amount of the coupon
        $couponMaximumAmount = $coupon->maximum_amount;

        if ($cartTotalAmount > $couponMaximumAmount) {
            throw new Exception('Cart total amount is greater than the maximum amount required for the coupon');
        }

        // check the usage limit of the coupon
        $couponUsageLimit = $coupon->usage_limit;




        // check the usage limit per user of the coupon

        // check the coupon type
        if ($couponType == 'fixed') {
            $cart->total_amount = $cart->total_amount - $coupon->coupon_amount;
        } else {
            $cart->total_amount = $cart->total_amount - ($cart->total_amount * $coupon->coupon_amount / 100);
        }
    }

    public function checkout(Request $request)
    {
        // dd($request->all());
        $request->validate([
            "items" => "required|array",
            "shipping_address" => "required",
            "billing_address" => "required",
            "shipping_method" => "required",
            "total_amount" => "required",
            "discount" => "sometimes",
            "payment_method" => "required",
            "transaction_id" => "required",
        ]);

        $user = $this->user();
        // Assuming you have a Cart model and it's already filled with items
        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
        $items = $request->items;

        $total_amount = 0;
        $vendor_id = null;

        foreach ($items as $key => $item) {
            try {
                $product = SCProduct::where('slug', $item['slug'])->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return response()->json(
                    [
                        'message' => 'Product not found',
                        'cart' => []
                    ],
                    404
                );
            } catch (\Throwable $th) {
                return response()->json(
                    [
                        'message' => $th->getMessage(),
                        'cart' => []
                    ],
                    404
                );
            }
            $vendor_id = $product->vendor_id;
            $total_amount += ($product->getPrice() * $item['quantity']);
        }

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

            $order->shipping_address = json_encode($request->shipping_address);
            $order->billing_address = json_encode($request->billing_address);
            $order->shipping_method = json_encode($request->shipping_method);
            $order->total_amount = $total_amount;

            $order->tracking_number = Str::random(10);

            $order->discount = $request->discount;
            $order->user_id = $user->id;
            $order->vendor_id = $vendor_id;
            // $order->order_number = $request->order_number;

            // $order->transaction_id = $request->transaction_id;
            // $order->payment_status = $request->payment_status;
            // $order->shipping_status = 'pending';
            // $order->payment_method = $request->payment_method;
            // Add other order details like shipping address, etc.
            $order->save();

            // Process payment and update order status if successful

            // Mark cart as processed or delete it
            $cart->delete(); // or mark as processed

            DB::commit();

            // Send order confirmation to user
            return SCOrderResource::make($order);

            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
