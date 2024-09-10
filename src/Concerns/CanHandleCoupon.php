<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as FacadesLog;
use Rahat1994\SparkCommerce\Models\SCCoupon;

trait CanHandleCoupon
{
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'reference' => 'string|sometimes',
        ]);
        $couponData = $this->couponData($request->coupon_code);

        if ($couponData) {

            $cart = $this->getCartAccordingToLoginType($request->refernce);

            // now process the cart and apply the coupon

            return response()->json(
                [
                    'message' => 'Coupon applied successfully',
                    'cart' => [],
                ],
                200
            );
        } else {
            return response()->json(
                [
                    'message' => 'Invalid coupon code',
                    'cart' => [],
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
}
