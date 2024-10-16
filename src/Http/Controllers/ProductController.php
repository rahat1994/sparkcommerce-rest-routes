<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use App\Models\Book;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceMultivendorRestRoutes\Http\Resources\SCMVProductResource;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCProductResource;

class ProductController extends SCBaseController
{
    public $recordModel = Book::class;

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
                $this->getProducts($request);
            }

            $products = $this->recordModel::with('categories')
                ->when($categorySlugs, function ($query) use ($categorySlugs) {
                    return $query->whereHas('categories', function ($query) use ($categorySlugs) {
                        $query->whereIn('slug', $categorySlugs);
                    });
                });
            return $this->getProducts($request, $products);

        } catch (\Throwable $th) {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    public function getProducts(Request $request, $builder = null){
        $modifiedRequest = $this->callHook('beforeFetchingProducts', $request);
        $request = $modifiedRequest ?? $request;

        if ($builder) {
            $products = $builder->paginate(10);
        } else {
            $products = $this->recordModel::with('categories')->paginate(10);
        }        

        $modifiedProducts = $this->callHook('afterFetchingProducts', $products);
        $products = $modifiedProducts ?? $products;
        return $this->resourceCollection($products);
    }

    public function show($slug)
    {
        try {
            $modifiedRequest = $this->callHook('beforeFetchingProducts', $slug);
            $slug = $modifiedRequest ?? $slug;

            $product = $this->recordModel::where('slug', $slug)->with('sCMVVendor', 'categories')->firstOrFail();

            $modifiedProduct = $this->callHook('afterFetchingProducts', $product);

            if ( null !== $modifiedProduct && $modifiedProduct instanceof $this->recordModel) {
                $product = $modifiedProduct;
            }

            return $this->singleModelResource($product);
        } catch(ModelNotFoundException $e) {
            return response()->json(['message' => 'resource not found'], 404);

        }
        catch (\Throwable $th) {

            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}
