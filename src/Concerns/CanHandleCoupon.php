<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;
use Rahat1994\SparkCommerce\Models\SCCoupon;

trait CanHandleCoupon
{
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

    protected function applyCoupon($cart, SCCoupon $coupon)
    {
        $user = $this->user();
        $couponType = $coupon->coupon_type;

        // check the end date & start date of the coupon
        $couponEndDate = $coupon->end_date;
        $couponStartDate = $coupon->start_date;

        if (Carbon::now()->greaterThan($couponEndDate) || Carbon::now()->lessThan($couponStartDate)) {
            throw new Exception('Coupon is not valid');
        }
        // check the minimum amount of the coupon
        $cartTotalAmount = $cart->total_amount;
        $couponMinimumAmount = $coupon->minimum_amount;

        if ($couponMinimumAmount !== null && $cartTotalAmount < $couponMinimumAmount) {
            throw new Exception('Cart total amount is less than the minimum amount required for the coupon');
        }

        // check the maximum amount of the coupon
        $couponMaximumAmount = $coupon->maximum_amount;

        if ($couponMaximumAmount!== null && $cartTotalAmount > $couponMaximumAmount) {
            throw new Exception('Cart total amount is greater than the maximum amount required for the coupon');
        }

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
