<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

trait CanCallHooks
{
    protected function callHook(string $hook): void
    {
        if (! method_exists($this, $hook)) {
            return;
        }

        $this->{$hook}();
    }
}
