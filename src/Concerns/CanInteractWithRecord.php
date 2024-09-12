<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

use Illuminate\Database\Eloquent\Collection;

trait CanInteractWithRecord
{
    public $recordModel;
    protected function getRecordBySlug(string $slug, string $recordModel = null)
    {
        $recordModel = $recordModel ?? $this->recordModel;
        return $recordModel::where('slug', $slug)->firstOrFail();
    }

    protected function getRecordsByItemTypeAndSlugs(string $itemType, array $slugs): Collection
    {
        return $itemType::whereIn('slug', $slugs)->get();
    }

    protected function getRecordsWithRelationShip(array $relations): Collection
    {
        return $this->recordModel::with($relations)->get();
    }

    protected function getRecordsWhere(array $conditions): Collection
    {
        return $this->recordModel::where($conditions)->get();
    }
}
