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
            'categories' => SCCategoryResource::collection($this->categories),
            'product_image' => ($this->hasMedia('product_image')) ? $this->getMedia('product_image')->first()->getUrl() : 'https://fastly.picsum.photos/id/63/5000/2813.jpg',
            'description' => $this->description,
            'gallery' => ($this->hasMedia('product_image_gallery')) ? $this->getMedia('product_image_gallery')->map(function ($item) {
                return $item->getUrl();
            }) : [],
            'rating' => [
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
