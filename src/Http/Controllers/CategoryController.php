<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Illuminate\Http\Request;
use Rahat1994\SparkCommerce\Models\SCCategory;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCCategoryResource;

class CategoryController extends SCBaseController
{
    public $recordModel = SCCategory::class;
    public function index(Request $request)
    {
        try {
            $categories = $this->recordModel::whereNull('parent_id')->with('children_recursive')->get();
            $modifiedCategories =  $this->callHook('beforeFetchCategoryList', $request, $categories);
            $categories = $modifiedCategories ?? $categories;
            return $this->resourceCollection($categories);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }
}
