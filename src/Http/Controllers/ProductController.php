<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Http\Resources\SCMVProductResource;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCProductResource;

class ProductController extends SCBaseController
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'integer',
            'categories' => 'string',
        ]);

        $categorySlugs = $request->get('categories') === null
            ? []
            : explode(',', $request->get('categories', ''));

        if (empty($categorySlugs)) {
            $products = SCProduct::with('categories')->paginate(10);
            return SCProductResource::collection($products);
        }

        $products = SCProduct::with('categories')
            ->when($categorySlugs, function ($query) use ($categorySlugs) {
                return $query->whereHas('categories', function ($query) use ($categorySlugs) {
                    $query->whereIn('slug', $categorySlugs);
                });
            })
            ->paginate(10);

        // $products = SCProduct::with('categories')->paginate(10);

        return SCProductResource::collection($products);
    }

    public function show($slug)
    {
        $product = SCProduct::where('slug', $slug)->with('sCMVVendor', 'categories')->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return SCMVProductResource::make($product);
    }
}
