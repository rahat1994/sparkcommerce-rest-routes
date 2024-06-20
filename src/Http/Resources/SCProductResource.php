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
            'background_image' => 'https://fastly.picsum.photos/id/63/5000/2813.jpg',
            'logo' => 'https://fastly.picsum.photos/id/63/5000/2813.jpg',
            'category' => $this->category,
            'address' => 'Dhaka, Bangladesh',
        ];
    }
}
