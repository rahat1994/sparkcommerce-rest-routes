<?php

namespace Rahat1994\SparkcommerceRestRoutes\Concerns;

trait CanCallHooks
{
    protected function callHook(string $hook, ...$args)
    {
        if (! method_exists($this, $hook)) {
            return null;
        }

        return call_user_func_array([$this, $hook], $args);
    }
}
