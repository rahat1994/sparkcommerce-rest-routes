<?php
use App\Models\User;
use Rahat1994\SparkCommerce\Models\SCCategory;
use Rahat1994\SparkCommerce\Models\SCOrder;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCCategoryResource;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCOrderResource;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCProductResource;

// config for Rahat1994/SparkcommerceRestRoutes
return [
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
    'resource_class_mapping' => [
        SCProduct::class => SCProductResource::class,
        SCCategory::class => SCCategoryResource::class,
        SCOrder::class => SCOrderResource::class,
        User::class => \Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCUserResource::class,
    ]
];
