<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Binafy\LaravelCart\Models\Cart;
use Binafy\LaravelCart\Models\CartItem;
use Exception;
use Hashids\Hashids;
use Illuminate\Http\Request;
use Rahat1994\SparkCommerce\Models\SCAnonymousCart;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Http\Resources\SCMVProductResource;

trait CanHandleAnonymousCart
{
    protected function getHashIdObject()
    {
        $project = strval(config('app.name'));
        return new Hashids($project);
    }
    public function getAnonymousCart($reference)
    {
        
        $hashIdObj = $this->getHashIdObject();
        $anonymousCartId = $hashIdObj->decode($reference);
        if (empty($anonymousCartId)) {
            throw new Exception('Cart not found');
        }
        $cart = $this->getAnonymousCartObj($anonymousCartId);

        return $this->loadAnonymousCartWithAllItems($cart);
    }

    public function getAnonymousCartObj($anonymousCartId)
    {
        if (empty($anonymousCartId) || !isset($anonymousCartId[0])) {
            throw new \InvalidArgumentException('Invalid anonymous cart ID.');
        }

        try {
            return SCAnonymousCart::findOrFail($anonymousCartId[0]);
        } catch (ModelNotFoundException $e) {
            throw $e;
        }
    }

    public function addItemToAnonymousCart(Request $request, $reference = null){
        
        $hashIdObj = $this->getHashIdObject();
        
        // If no reference is provided, create a new anonymous cart
        if (is_null($reference)) {
            $cart = new SCAnonymousCart;
            $cart->cart_content = [];
            $cart->save();
            
            // Encode the cart ID to generate a reference
            $reference = $hashIdObj->encode($cart->id);
        }

        // Decode the reference to get the anonymous cart ID
        $anonymousCartId = $hashIdObj->decode($reference);

        // dd($anonymousCartId);
        if (empty($anonymousCartId)) {
            throw new Exception('Cart not found');
        }
        $anonymousCart = $this->getAnonymousCartObj($anonymousCartId);

        try {
            $record = $this->getRecordBySlug($request->slug);
            $cartItems = $anonymousCart->cart_content;

            // update the quantity if product already exists in the cart in json

            $productIndex = -1;
            foreach ($cartItems as $key => $item) {
                if ($item['slug'] === $record->slug && 
                    $item['itemable_type'] === $this->recordModel) {
                    $productIndex = $key;
                    break;
                }
            }
            $this->callHook('beforeModifyingAnonymousCart');
            $this->beginDatabaseTransaction();

            $quantity = $this->mutateQuantityBeforeUpdatingAnonymousCartItem($record, $request);

            if ($productIndex != -1) {                
                $cartItems[$productIndex]['quantity'] = $quantity;
            } else {
                $cartItems[] = [
                    'itemable_type' => $this->recordModel,
                    'slug' => $record->slug,
                    'quantity' => $quantity,
                ];
            }

            $anonymousCart->cart_content = $cartItems;
            $anonymousCart->reference = $reference;
            $anonymousCart->save();

            $this->commitDatabaseTransaction();
            $cart = $this->loadAnonymousCartWithAllItems($anonymousCart);
            $this->callHook('afterModifyingAnonymousCart');
            return response()->json(
                [
                    // TODO:: change the message and apply localization
                    'message' => 'Product added to cart successfully',
                    'reference' => $reference,
                    'cart' => $cart,
                ],
                200
            );
        } catch (ModelNotFoundException $e) {
            return response()->json(
                [
                    // TODO:: change the message and apply localization
                    'message' => 'resource not found',
                ],
                404
            );
        }        
        catch (\Throwable $th) {
            dd($th);
            return response()->json(
                [
                    // TODO:: change the message and apply localization
                    'message' => 'Something went wrong',
                ],
                400
            );
        }        
    }
    public function removeItemFromAnonymousCart(Request $request, $slug, $reference){

        try {
            $hashIdObj = $this->getHashIdObject();
            $anonymousCartId = $hashIdObj->decode($reference);

            $anonymousCart = $this->getAnonymousCartObj($anonymousCartId);

            $record = $this->getRecordBySlug($slug);           

            // check if product already exists in the cart
            $cartItems = $anonymousCart->cart_content;

            // update the quantity if product already exists in the cart in json
            $productIndex = -1;

            foreach ($cartItems as $key => $item) {
                if ($item['slug'] == $record->slug
                    && $item['itemable_type'] == $this->recordModel) {
                    $productIndex = $key;
                    break;
                }
            }

            $this->beginDatabaseTransaction();

            $this->callHook('beforeModifyingAnonymousCart');
            $cartItems = $this->removeItemFromAnonymousCartContent($cartItems, $productIndex);

            $anonymousCart->cart_content = $cartItems;
            $anonymousCart->save();

            $this->callHook('afterModifyingAnonymousCart');
            $this->commitDatabaseTransaction();

            $cart = $this->loadAnonymousCartWithAllItems($anonymousCart);

            return response()->json(
                [
                    // TODO: change the message and apply localization
                    'message' => 'Product removed from cart successfully',
                    'reference' => $reference,
                    'cart' => $cart,
                ],
                200
            );
        } 
        catch (ModelNotFoundException $e) {
            return response()->json(
                [   
                    // TODO: change the message and apply localization
                    'message' => 'resource not found',
                ],
                404
            );
        } 
        catch (\Throwable $th) {

            return response()->json(
                [
                    // TODO: change the message and apply localization
                    'message' => 'Cart not found'
                ],
                404
            );
        }
    }

    public function decodeAnonymousCartReferenceId($reference)
    {
        $hashIds = $this->getHashIdObject();
        $anonymousCartId = $hashIds->decode($reference);

        return $anonymousCartId;
    }


    private function loadAnonymousCartWithAllItems(SCAnonymousCart $cartContent)
    {
        $cartItems = [];
        $cartContent = $cartContent->cart_content;
        $slugsByType = [];
        $recorsByType = [];
        foreach ($cartContent as $item) {
            $slugsByType[$item['itemable_type']][] = $item['slug'];
        }
        $recourceClassMapping = $this->getResourceClassMapping();
        foreach ($slugsByType as $type => $slugs) {

            $records = $this->getRecordsByItemTypeAndSlugs($type, $slugs)->keyBy('slug');
            foreach($cartContent as $item){
                $cartableRecord = $records->get($item['slug']);
                $resourceClass = $recourceClassMapping[$type] ?? null;

                if($resourceClass === null){
                    continue;
                }

                if($cartableRecord){
                    $temp = [];
                    $temp['quantity'] = $item['quantity'];
                    $temp['item'] = $this->singleModelResource($cartableRecord);
                    $cartItems[] = $temp;
                }
            }
        }
        return $cartItems;
    }

    protected function mutateQuantityBeforeUpdatingAnonymousCartItem($cartItem, $request)
    {
        return $request->quantity;
    }

    protected function removeItemFromAnonymousCartContent($cartItems, $productIndex)
    {
        if ($productIndex == -1) {
            throw new Exception('Product not found in the cart');   
        }

        unset($cartItems[$productIndex]);
        return $cartItems;        
    }
}
