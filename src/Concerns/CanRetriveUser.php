<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

use Illuminate\Support\Facades\Auth;

trait CanRetriveUser
{
        protected function user()
    {
        return Auth::guard('sanctum')->user();
    }
}
