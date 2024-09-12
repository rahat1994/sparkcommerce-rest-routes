<?php

namespace Rahat1994\SparkcommerceRestRoutes\Http\Controllers;

use Illuminate\Routing\Controller;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanCallHooks;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanInteractWithApiResources;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanInteractWithRecord;
use Rahat1994\SparkcommerceRestRoutes\Concerns\CanPrittfyExceptions;
class SCBaseController extends Controller
{
    use CanCallHooks;
    use CanPrittfyExceptions;
    use CanInteractWithRecord;
    use CanInteractWithApiResources;
}