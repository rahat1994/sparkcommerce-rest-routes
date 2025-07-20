<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Binafy\LaravelCart\Models\Cart;
use Binafy\LaravelCart\Models\CartItem;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;
use Rahat1994\SparkCommerce\Concerns\CanUseDatabaseTransactions;
use Rahat1994\SparkCommerce\Models\SCAnonymousCart;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Exceptions\VendorNotSameException;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanHandleAnonymousCart;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanHandleCart;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanHandleCheckout;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanHandleCoupon;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanRetriveUser;
use Rahat1994\SparkcommerceRestRoutes\Http\Controllers\SCBaseController;
use Illuminate\Support\Facades\Validator;

class CartController extends SCBaseController
{
    use CanUseDatabaseTransactions;
    use CanRetriveUser;
    use CanHandleCart;
    use CanHandleAnonymousCart;
    use CanHandleCoupon;
    use CanHandleCheckout;

    public $recordModel = SCProduct::class;

    public function getCart(Request $request, $reference = null): JsonResponse
    {
        $request->validate([
            'reference' => 'nullable|string',
        ]);

        try {
            $cart = $this->getCartAccordingToLoginType($reference);
            return response()->json(['cart' => $cart], 200);
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error retrieving cart',
                    'cart' => []
                ],
                500
            );
        }
    }

    public function getCartAccordingToLoginType($reference)
    {
        $user = $this->user();
        if (null !== $user) {
            return $this->getCartWithItemObjects($user->id);
        }

        try {
            return $this->getAnonymousCart($reference);
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


    public function addToCart(Request $request, $reference = null)
    {
        $request->validate([
            'slug' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'replace_existing' => 'boolean'
        ]);

        $replaceExisting = is_null($request->replace_existing) ? false : $request->replace_existing;

        $user = $this->user();

        try {
            if ($user !== null) {
                return $this->addItemToCart($request);
            }

            return $this->addItemToAnonymousCart($request, $reference);
        } catch (VendorNotSameException $exception) {
            return response()->json(
                [
                    // TODO: Add a better message and internatiolization.
                    'message' => $exception->getMessage(),
                ],
                400
            );
        } catch (ModelNotFoundException $exception) {
            return response()->json(
                [
                    // TODO: Add a better message and internatiolization.
                    "message" => "Unable to locate the resource you requested.",
                ],
                404
            );
        } catch (\Throwable $th) {
            return response()->json(
                [
                    // TODO: Add a better message and internatiolization.
                    'message' => "An error occurred while adding the item to the cart.",
                ],
                400
            );
        }
    }



    public function removeFromCart(Request $request, $slug, $reference = null)
    {
        $validatedData = Validator::make(
            [
                'slug' => $slug,
                'reference' => $reference,
            ],
            [
                'slug' => 'required|string',
                'reference' => 'string|nullable',
            ]
        )->validate();

        try {
            $user = $this->user();

            if ($user !== null) {
                return $this->removeItemFromCart($request, $validatedData['slug']);
            }
            return $this->removeItemFromAnonymousCart($request, $validatedData['slug'], $validatedData['reference']);
        } catch (ModelNotFoundException $exception) {
            return response()->json(
                [
                    // TODO: Add a better message and internatiolization.
                    "message" => "Unable to locate the resource you requested.",
                ]
            );
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'message' => $th->getMessage(),
                ],
                400
            );
        }
    }

    public function associateAnonymousCart(Request $request)
    {

        $request->validate([
            'reference' => 'required|string',
        ]);
        // this controller method will be used to associate the anonymous cart with the user cart

        try {
            $user = $this->user();

            if (null === $user) {
                return response()->json(
                    [
                        'message' => 'User not found',
                        'cart' => []
                    ],
                    404
                );
            }

            $reference = $request->reference;
            $anonymousCartId = $this->decodeAnonymousCartReferenceId($reference);

            if (empty($anonymousCartId)) {
                return response()->json(
                    [
                        // TODO: Add a better message and internatiolization.
                        'message' => 'Anonnymous cart not found',
                    ],
                    404
                );
            }

            $anonymousCart = SCAnonymousCart::findOrFail($anonymousCartId[0]);

            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

            $cartItems = $anonymousCart->cart_content;

            foreach ($cartItems as $item) {
                $product = $this->getRecordBySlug($item['slug']);

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

            $anonymousCart->delete();

            $cart = $this->loadCartWithAllItems($cart);

            return response()->json(
                [
                    'message' => 'Cart associated successfully',
                    'cart' => $cart,
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => 'Error associating cart',
                    'cart' => []
                ],
                500
            );
        }
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $user = $this->user();

        try {
            $result = $this->validateAndApplyCoupon($user, $request->coupon_code);
            //there should be system where cart is linked to a coupon.
            return response()->json(
                [
                    'message' => 'Valid Coupon',
                    'cart' => $result['cart'],
                    'discount' => $result['discount'],
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'message' => $e->getMessage(),
                ],
                400
            );
        }
    }

    protected function validateAndApplyCoupon($user, $couponCode)
    {

        $cart  = $this->getUserCart($user->id);
        if($couponCode === null || $couponCode === '') {
            return [
                'cart' => $cart,
                'discount' => 0,
            ];
        }
        // here we can use this method to check if the coupon can be applied to the cart
        // before running anymore validations
        [$shouldContinue, $message] = $this->couponValidationShouldContinue($cart, $couponCode);

        if (!$shouldContinue) {
            throw new Exception($message);
        }

        $couponData = $this->couponData($couponCode);
        $this->checkDateConstraint($couponData);
        $this->checkCartTotalCostConstraint($cart, $couponData);
        $this->checkCouponUsageLimit($couponData);
        $this->checkUsageLimitPerUser($user, $couponData);
        $this->checkCouponIncludedProducts($cart, $couponData);
        $totalAmount = $this->getCartTotalAmount($cart);

        $discount = $this->calculateDiscount($totalAmount, $couponData, $cart);
        return [
            'cart' => $cart,
            'discount' => $discount,
        ];
    }

    protected function couponValidationShouldContinue($cart, $couponCode)
    {
        return [true, ''];
    }

    public function checkout(Request $request)
    {
        $request->validate([
            "items" => "required|array",
            "items.*.slug" => "required|string",
            "items.*.quantity" => "required|integer|min:1",
            "shipping_address" => "required",
            "billing_address" => "required",
            "shipping_method" => "required",
            "total_amount" => "required",
            "coupon_code" => "sometimes|string",
            "payment_method" => "required",
            "transaction_id" => "required",
        ]);

        $user = $this->user();

        try {
            $couponResult = $this->validateAndApplyCoupon($user, $request->coupon_code);
            $discountArray = $couponResult['discount'];
            return $this->checkoutWithItems($request, $user, $discountArray);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(
                [
                    // TODO: Add a better message and internatiolization.
                    'message' => "Something went wrong while processing the order.",
                    'cart' => []
                ],
                500
            );
        }
    }

    public function afterOrderIsSaved($order){

    }
}
