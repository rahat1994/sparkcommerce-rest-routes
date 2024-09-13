<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;
use Rahat1994\SparkCommerce\Models\SCCategory;
use Rahat1994\SparkCommerce\Models\SCOrder;
use Rahat1994\SparkCommerce\Models\SCProduct;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCCategoryResource;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCOrderResource;
use Rahat1994\SparkcommerceRestRoutes\Http\Resources\SCProductResource;

trait CanInteractWithApiResources
{
    protected function getResourceClassMapping()
    {
        // TODO: move this array to config files
        return [
            SCProduct::class => SCProductResource::class,
            SCCategory::class => SCCategoryResource::class,
            SCOrder::class => SCOrderResource::class,
        ];
    }

    protected function getResourceClass($model)
    {
        $mapping = $this->getResourceClassMapping();
        return $mapping[$model] ?? null;
    }

    protected function resourceCollection($data, $model = null)
    {
        // dd($this->getResourceClassMapping());
        $model = $model ?? $this->recordModel;
        $resourceClass = $this->getResourceClass($model);
        return $resourceClass::collection($data);
    }

    protected function singleModelResource($data, $model = null)
    {
        $model = $model ?? $this->recordModel;
        $resourceClass = $this->getResourceClass($model);
        return $resourceClass::make($data);
    }
}
