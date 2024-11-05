<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Rahat1994\SparkCommerce\Models\SCCoupon;

trait CanHandleCoupon
{
    protected function couponData(string $couponCode): SCCoupon
    {
        return SCCoupon::where('coupon_code', $couponCode)->firstOrFail();
    }

    protected function checkDateConstraint(SCCoupon $coupon)
    {
        $couponEndDate = $coupon->end_date;
        $couponStartDate = $coupon->start_date;

        if (Carbon::now()->greaterThan($couponEndDate) || Carbon::now()->lessThan($couponStartDate)) {
            throw new Exception('Coupon is not valid');
        }
    }

    protected function checkCartTotalCostConstraint($cart, SCCoupon $coupon)
    {
        $cartTotalAmount = $this->getCartTotalAmount($cart);
        $couponMinimumAmount = $coupon->minimum_amount;

        if ($couponMinimumAmount !== null && $cartTotalAmount < $couponMinimumAmount) {
            throw new Exception('Cart total amount is less than the minimum amount required for the coupon. Minimum amount required: ' . $couponMinimumAmount);
        }

        $couponMaximumAmount = $coupon->maximum_amount;

        if ($couponMaximumAmount !== null && $cartTotalAmount > $couponMaximumAmount) {
            throw new Exception('Cart total amount is greater than the maximum amount required for the coupon. Maximum amount allowed: ' . $couponMaximumAmount);
        }
    }

    protected function checkCouponUsageLimit($coupon)
    {
        $couponUsageLimit = $coupon->usage_limit;

        if ($couponUsageLimit !== 0 && $couponUsageLimit <= $coupon->usage_count) {
            throw new Exception('Coupon usage limit has been reached');
        }
    }

    protected function applyCoupon($cart, SCCoupon $coupon)
    {
        $user = $this->user();
        $couponType = $coupon->coupon_type;

        // check the end date & start date of the coupon
        // check the minimum amount of the coupon
        // check the maximum amount of the coupon


        // check the usage limit of the coupon
        $couponUsageLimit = $coupon->usage_limit;

        // check the usage limit per user of the coupon
        $couponUsageLimit = $coupon->usage_limit_per_user;

        DB::table('coupon_user')
            ->where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)->select('usage_count')->first();
        // if the user has used the coupon more than the usage limit per user, throw an exception

        if ($couponUsageLimit !== 0 && $couponUsageLimit <= $couponUsageLimit) {
            throw new Exception('Coupon usage limit per user has been reached');
        }

        // check if the coupon has included products
        $couponIncludedProducts = $coupon->includedProducts;
        dd($couponIncludedProducts);
        if ($couponIncludedProducts->isNotEmpty()) {
            $cartItems = $cart->items;

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->itemable;
            }
        }

        // check the coupon type
        if ($couponType == 'fixed') {
            $cart->total_amount = $cart->total_amount - $coupon->coupon_amount;
        } else {
            $cart->total_amount = $cart->total_amount - ($cart->total_amount * $coupon->coupon_amount / 100);
        }
    }
}
