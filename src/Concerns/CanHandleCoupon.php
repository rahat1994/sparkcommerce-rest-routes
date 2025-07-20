<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

use App\Models\User;
use Binafy\LaravelCart\Models\Cart;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Boolean;
use Rahat1994\SparkCommerce\Models\SCCoupon;

trait CanHandleCoupon
{

    protected $isCouponProductSpecific = false;

    protected function couponData(string $couponCode): SCCoupon
    {
        $conditions = [
            'coupon_code' => $couponCode,
        ];
        $couponConditions = $this->getCouponConditions($conditions);
        return $this->getRecordWhere($couponConditions, SCCoupon::class);
    }

    protected function getCouponConditions(array $conditions): array
    {
        return $conditions;
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
        // $couponUsageLimit = $coupon->usage_limit;

        // if ($couponUsageLimit !== 0 && $couponUsageLimit <= $coupon->usage_count) {
        //     throw new Exception('Coupon usage limit has been reached');
        // }
    }

    protected function checkUsageLimitPerUser(User $user, SCCoupon $coupon)
    {

        // DB::table('coupon_user')
        //     ->where('coupon_id', $coupon->id)
        //     ->where('user_id', $user->id)->select('usage_count')->first();
        // if the user has used the coupon more than the usage limit per user, throw an exception

        // if ($couponUsageLimit !== 0 && $couponUsageLimit <= $couponUsageLimit) {
        //     throw new Exception('Coupon usage limit per user has been reached');
        // }
    }

    protected function checkCouponIncludedProducts($cart, SCCoupon $coupon)
    {
        $couponIncludedProductIds = $this->getCouponIncludedProductIds($coupon->id);
        if ($couponIncludedProductIds->isEmpty()) {
            return;
        }
        $this->isCouponProductSpecific = true;
        $cartContainsCouponIncludedProducts = false;

        $cartItems = $cart->items;

        foreach ($cartItems as $cartItem) {
            if ($couponIncludedProductIds->contains($cartItem->itemable_id)) {
                $cartContainsCouponIncludedProducts = true;
                break;
            }
        }

        if (!$cartContainsCouponIncludedProducts) {
            throw new Exception('Cart does not contain any of the products included in the coupon');
        }
    }

    protected function getCouponIncludedProductIds($coupnId)
    {
        $tableName = config('sparkcommerce.table_prefix') . config('sparkcommerce.coupon_included_products_table_name');

        return DB::table($tableName)
            ->where('coupon_id', $coupnId)
            ->pluck('product_id');
    }

    protected function applyCoupon($cart, SCCoupon $coupon)
    {
        $user = $this->user();
        $couponType = $coupon->coupon_type;

        // check the end date & start date of the coupon
        // check the minimum amount of the coupon
        // check the maximum amount of the coupon


        // check the usage limit of the coupon
        // $couponUsageLimit = $coupon->usage_limit;

        // check the usage limit per user of the coupon
        // check if the coupon has included products

        // check the coupon type
        if ($couponType == 'fixed_cart_discount') {
            $cart->total_amount = $cart->total_amount - $coupon->coupon_amount;
        } else if ($couponType == 'percentage_discount') {
            $cart->total_amount = $cart->total_amount - ($cart->total_amount * $coupon->coupon_amount / 100);
        }

        // check if the coupon is apply once
        // check if the coupon is apply repeatedly
    }

    protected function calculateDiscount($total_amount, SCCoupon $coupon, Cart $cart)
    {

        $couponType = $coupon->coupon_type;
        $discount = 0;
        $couponIncludedProductIds = $this->getCouponIncludedProductIds($coupon->id);
        // dd($couponIncludedProductIds);
        // identify if the coupn is product specific
        // if the coupon is product specific, then find out the total amount added to cart for that product
        // then subtract the amount and send it back
        // if the coupn is not product specific, then calculate the discount based on the total amount

        // for product based coupon send a product wise discount breakdown.

        if ($this->isCouponProductSpecific) {
            $cartItems = $cart->items;
            $discountBreakdown = [];
            // dd($couponIncludedProductIds->contains(1));

            foreach ($cartItems as $cartItem) {
                // print_r($couponIncludedProductIds);
                // print_r($cart->itemable_id);
                // print_r($couponIncludedProductIds->contains($cart->itemable_id));

                // Check if the cart item is included;
                if (!$couponIncludedProductIds->contains($cartItem->itemable_id)) {
                    continue;
                }

                $product = $cartItem->itemable;
                $productTotalAmount = $product->getPrice() * $cartItem->quantity;
                $productDiscount = 0;
                if ($couponType == 'fixed_cart_discount') {
                    $productDiscount = $coupon->coupon_amount;
                } else if ($couponType == 'percentage_discount') {
                    $productDiscount = $productTotalAmount * $coupon->coupon_amount / 100;
                }
                $discountBreakdown[] = [
                    'slug' => $product->slug,
                    'product_total_amount' => $productTotalAmount,
                    'product_discount' => $productDiscount
                ];
                $discount += $productDiscount;
            }
            return [
                'discount' => $discount,
                'discount_breakdown' => $discountBreakdown
            ];
        }

        if ($couponType == 'fixed_cart_discount') {
            $discount = $coupon->coupon_amount;
        } else if ($couponType == 'percentage_discount') {
            $discount = $total_amount * $coupon->coupon_amount / 100;
        }

        return $discount;
    }

    protected function removeCoupon($cart, SCCoupon $coupon)
    {
        $user = $this->user();
        $couponType = $coupon->coupon_type;

        // check the end date & start date of the coupon
        // check the minimum amount of the coupon
        // check the maximum amount of the coupon

    }
}
