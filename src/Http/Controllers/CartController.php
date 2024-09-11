<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Binafy\LaravelCart\Models\Cart;
use Exception;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rahat1994\SparkCommerce\Concerns\CanUseDatabaseTransactions;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Illuminate\Support\Str;
use Rahat1994\SparkCommerce\Models\SCAnonymousCart;
use Rahat1994\SparkCommerce\Models\SCOrder;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Exceptions\VendorNotSameException;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanHandleAnonymousCart;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanHandleCart;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanHandleCoupon;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanRetriveUser;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCOrderResource;
use Rahat1994\SparkcommerceRestRoutes\Http\Controllers\SCBaseController;

class CartController extends SCBaseController
{
    use CanUseDatabaseTransactions;
    use CanRetriveUser;
    use CanHandleCart;
    use CanHandleAnonymousCart;
    use CanHandleCoupon;

    public $recordModel = SCProduct::class;
    // public function __construct()
    // {
    //     self::$recordModel = SCProduct::class;
    // }

    public function getCart(Request $request, $reference = null) : JsonResponse
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
        
        try{
            if ($user !== null) {
                return $this->addItemToCart($request);
            } 
    
            return $this->addItemToAnonymousCart($request, $reference);
        } catch (VendorNotSameException $exception){
            return response()->json(
                [
                    // TODO: Add a better message and internatiolization.
                    'message' => $exception->getMessage(),
                ],
                400
            );
        } catch(ModelNotFoundException $exception){
            return response()->json(
                [
                    // TODO: Add a better message and internatiolization.
                    "message" => "Unable to locate the resource you requested.",
                ],
                404
            );

        }        
        catch (\Throwable $th) {
            return response()->json(
                [
                    // TODO: Add a better message and internatiolization.
                    'message' => "An error occurred while adding the item to the cart.",
                ],
                400
            );
        }        
    }



    public function removeFromCart(Request $request, $slug, $reference)
    {
        $request->validate([
            'slug' => 'required|string',
            'reference' => 'string',
        ]);

        try {
            $user = $this->user();

            if ($user !== null) {
                return $this->removeItemFromCart($request, $slug);
            }             
            return $this->removeItemFromAnonymousCart($request, $slug, $reference);
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'message' => $th->getMessage(),
                ],
                400
            );
        }      
        
    }
    
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'reference' => 'string|sometimes',
        ]);
        $couponData = $this->couponData($request->coupon_code);

        if ($couponData) {

            $cart = $this->getCartAccordingToLoginType($request->reference);

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
