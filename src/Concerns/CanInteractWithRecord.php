<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;
use Illuminate\Database\Eloquent\Collection;

trait CanInteractWithRecord
{
    public $recordModel;
    protected function getRecordBySlug(string $slug)
    {
        // dd($this->recordModel);
        return $this->recordModel::where('slug', $slug)->firstOrFail();
    }

    protected function getRecordsByItemTypeAndSlugs(string $itemType, array $slugs): Collection
    {
        return $itemType::whereIn('slug', $slugs)->get();
    }
}
