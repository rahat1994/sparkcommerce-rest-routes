<?php

namespace Rahat1994\SparkcommerceRestRoutes\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Rahat1994\SparkcommerceRestRoutes\SparkcommerceRestRoutes
 */
class SparkcommerceRestRoutes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Rahat1994\SparkcommerceRestRoutes\SparkcommerceRestRoutes::class;
    }
}
