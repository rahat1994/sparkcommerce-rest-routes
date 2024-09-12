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
    protected function checkoutWithItems($request, $user)
    {
                // Assuming you have a Cart model and it's already filled with items
                $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
                $items = $request->items;
        
                $total_amount = 0;
                
                $items = $this->beforeProcessingCheckoutCartItems($items);
                foreach ($items as $key => $item) {
                    try {
                        $product = $this->getRecordBySlug($item['slug']);
                    } catch (ModelNotFoundException $e) {
                        return response()->json(
                            [
                                'message' => 'resource not found',
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
                    $total_amount += ($product->getPrice() * $item['quantity']);
                }
                $modified_amount = $this->afterProcessingCheckoutCartItems($items, $total_amount);

                if($total_amount != $modified_amount && is_numeric($modified_amount)){
                    $total_amount = $modified_amount;
                }
                
                if (!$cart || $cart->items->isEmpty()) {
                    throw new Exception("Cart is empty or not found.");
                }
        
                // Begin database transaction to ensure data integrity
                
                try {
                    $this->callHook('afterCheckout');
                    $this->beginDatabaseTransaction();
                    // Create a new Order
                    

                    
                    $orderData = [
                        'user_id' => $user->id,
                        'status' => 'pending',
                        'items' => $cart->items,
                        'shipping_address' => json_encode($request->shipping_address),
                        'billing_address' => json_encode($request->billing_address),
                        'shipping_method' => json_encode($request->shipping_method),
                        'total_amount' => $total_amount,
                        'tracking_number' => Str::random(10),
                        'discount' => $request->discount,
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

    protected function afterProcessingCheckoutCartItems($items, $total_amount)
    {
        return $total_amount;
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
