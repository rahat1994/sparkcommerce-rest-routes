<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SCCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->name
        ];
    }
}
