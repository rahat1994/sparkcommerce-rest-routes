<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Illuminate\Routing\Controller;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Http\Resources\SCMVProductResource;

class ProductController extends Controller
{

    public function show($slug)
    {
        $product = SCProduct::where('slug', $slug)->with('sCMVVendor', 'categories')->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return SCMVProductResource::make($product);
    }
}
