<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rahat1994\SparkCommerce\Models\SCCategory;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCCategoryResource;

class CategoryController extends Controller
{
    public function index(Request $request, $vendor_id)
    {
        $categories = SCCategory::where('vendor_id', $vendor_id)
            ->with('childrenRecursive')
            ->whereNull('parent_id')
            ->get();
        return SCCategoryResource::collection($categories);
    }

    public function show($slug)
    {
        return 'Category Show';
    }
}
