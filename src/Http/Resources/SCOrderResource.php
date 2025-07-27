<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use PHPUnit\Framework\MockObject\Stub\ReturnReference;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Http\Resources\SCMVProductResource;

class SCOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'status' => $this->status,
            'items' => $this->serializeItems($this->items),
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'shipping_method' => $this->shipping_method,
            'total_amount' => $this->total_amount,
            'tracking_number' => $this->tracking_number,
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'meta' => $this->meta,
            'created_at' => $this->created_at
        ];
    }

    private function serializeItems($items)
    {
        $products = [];
        $itemQuantity = [];
        // dd($items);
        foreach ($items as $item) {
            if ($item['itemable_type'] === 'Rahat1994\SparkCommerce\Models\SCProduct') {
                $products[] = $item['itemable_id'];
                $itemQuantity[$item['itemable_id']] = $item['quantity'];
            }
        }
        // dd($products);
        $serializedItems = [];
        $products = SCProduct::whereIn('id', array_values($products))->get();

        $products->map(function ($product) use ($itemQuantity, &$serializedItems) {
            $serializedItems[] = [
                'quantity' => $itemQuantity[$product->id],
                'item' => SCMVProductResource::make($product),
            ];
        });

        return $serializedItems;
    }
}
