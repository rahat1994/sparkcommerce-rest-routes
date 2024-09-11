<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Http\Resources\SCMVProductResource;

trait CanInteractWithApiResources
{
    protected function getResourceClassMapping(): array
    {
        return [
            SCProduct::class => SCMVProductResource::class,
            // 'another_item_type' => AnotherResource::class, // Example of another item type
        ];
    }
}
