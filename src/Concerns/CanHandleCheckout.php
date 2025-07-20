<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

use Binafy\LaravelCart\Models\Cart;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rahat1994\SparkCommerce\Models\SCOrder;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCOrderResource;

trait CanHandleCheckout
{
    protected function checkoutWithItems($request, $user, $discountArray = null)
    {
        try {
            // Assuming you have a Cart model and it's already filled with items
            $cart = $this->getUserCart($user->id);
            $items = $request->items;

            $totalAmount = 0;

            // this will set te vendorId for the cart items
            $items = $this->beforeProcessingCheckoutCartItems($items);
            $totalAmount = $this->getCartTotalAmount($cart);
            $amountAfterDiscount = $totalAmount - ($discountArray['discount'] ?? 0);

            $modified_amount = $this->afterProcessingCheckoutCartItems($items, $amountAfterDiscount);

            if ($amountAfterDiscount != $modified_amount && is_numeric($modified_amount)) {
                $amountAfterDiscount = $modified_amount;
            }

            if (!$cart || $cart->items->isEmpty()) {
                throw new Exception("Cart is empty or not found.");
            }

            // Begin database transaction to ensure data integrity

            $this->callHook('beforeCheckout');
            $this->beginDatabaseTransaction();
            // Create a new Order
            $discountData = [
                'coupon_code' => $request->coupon_code,
                'discount' => $discountArray['discount'] ?? 0,
                'total_amount' => $amountAfterDiscount,
            ];
            // dd($cart->items[0]->itemable->vendor_id);
            $orderData = [
                'user_id' => $user->id,
                'status' => 'pending',
                'items' => $cart->items,
                'shipping_address' => json_encode($request->shipping_address),
                'billing_address' => json_encode($request->billing_address),
                'shipping_method' => json_encode($request->shipping_method),
                'total_amount' => $amountAfterDiscount,
                'tracking_number' => Str::random(10),
                'discount' => json_encode($discountData),
            ];

            $orderData = $this->beforeOrderIsSaved($orderData);
            $order = SCOrder::create($orderData);
            $order = $this->afterOrderIsSaved($order);

            // Process payment and update order status if successful

            // Mark cart as processed or delete it
            $cart->delete(); // or mark as processed

            $this->commitDatabaseTransaction();

            $this->callHook('afterCheckout');
            // Send order confirmation to user
            return SCOrderResource::make($order);
        } catch (Exception $e) {
            $this->rollbackDatabaseTransaction();
            throw $e;
        }
    }

    protected function beforeProcessingCheckoutCartItems($items)
    {
        return $items;
    }

    protected function afterProcessingCheckoutCartItems($items, $totalAmount)
    {
        return $totalAmount;
    }

    protected function beforeOrderIsSaved(array $orderData): array
    {
        return $orderData;
    }

    protected function afterOrderIsSaved($order)
    {
        return $order;
    }
}
