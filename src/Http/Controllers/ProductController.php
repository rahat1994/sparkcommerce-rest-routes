<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Http\Resources\SCMVProductResource;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCProductResource;

class ProductController extends SCBaseController
{
    public $recordModel = SCProduct::class;

    public function index(Request $request)
    {
        $request->validate([
            'page' => 'integer',
            'categories' => 'string',
        ]);

        try {
            $categorySlugs = $request->get('categories') === null
                ? []
                : explode(',', $request->get('categories', ''));

            if (empty($categorySlugs)) {
                $products = $this->recordModel::with('categories')->paginate(10);
                return SCProductResource::collection($products);
            }

            $products = $this->recordModel::with('categories')
                ->when($categorySlugs, function ($query) use ($categorySlugs) {
                    return $query->whereHas('categories', function ($query) use ($categorySlugs) {
                        $query->whereIn('slug', $categorySlugs);
                    });
                })
                ->paginate(10);
            $modifiedProducts = $this->callHook('afterFetchingProducts', $products);
            $products = $modifiedProducts ?? $products;

            return SCProductResource::collection($products);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function show($slug)
    {
        try {
            $product = $this->recordModel::where('slug', $slug)->with('sCMVVendor', 'categories')->firstOrFail();

            $modifiedProduct = $this->callHook('beforeShow', $product);
            $product = $modifiedProduct ?? $product;

            return SCMVProductResource::make($product);
        } catch (\Throwable $th) {

            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}
