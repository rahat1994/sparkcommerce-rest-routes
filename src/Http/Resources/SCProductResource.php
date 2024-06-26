<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SCProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'product_image' => 'https://fastly.picsum.photos/id/63/5000/2813.jpg',
            'rating' =>[
                    'average' => 4.5,
                    'total' => 100
            ],
            'pricing' => [
                'price' => $this->price,
                'discounted_price' => $this->discounted_price,
                'discount' => $this->discount,
                'currency' => $this->currency,
            ],
        ];
    }
}
