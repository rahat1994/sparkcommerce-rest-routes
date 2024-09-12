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
        $categories = $this->recordModel::whereNull('parent_id')->with('children_recursive')->get();
        $this->callHook('beforeIndex', $request, $categories);
        return SCCategoryResource::collection($categories);
    }
}
