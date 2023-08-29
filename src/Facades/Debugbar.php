<?php

namespace Barryvdh\Debugbar\Facades;

use DebugBar\DataCollector\DataCollectorInterface;

/**
 * @mixing \Barryvdh\Debugbar\LaravelDebugbar
 */
class Debugbar extends \Illuminate\Support\Facades\Facade
{
    /**
     * {@inheritDoc}
     */
    protected static function getFacadeAccessor()
    {
        return \Barryvdh\Debugbar\LaravelDebugbar::class;
    }
}
