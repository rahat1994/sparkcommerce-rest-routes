<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCProductResource;

trait CanInteractWithApiResources
{
    protected function getResourceClassMapping(): array
    {
        // TODO: move this array to config files
        return [
            SCProduct::class => SCProductResource::class,
        ];
    }
}
