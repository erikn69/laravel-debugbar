<?php

use Barryvdh\Debugbar\Middleware\DebugbarEnabled;

$routeConfig = [
    'namespace' => 'Barryvdh\Debugbar\Controllers',
    'prefix' => app('config')->get('debugbar.route_prefix'),
    'domain' => app('config')->get('debugbar.route_domain'),
    'middleware' => array_merge(app('config')->get('debugbar.route_middleware', []), [DebugbarEnabled::class]),
];

app('router')->group($routeConfig, function ($router) {
    $router->get('open', 'OpenHandlerController@handle')->name('debugbar.openhandler');
    $router->delete('cache/{key}/{tags?}', 'CacheController@delete')->name('debugbar.cache.delete');
    $router->get('clockwork/{id}', 'OpenHandlerController@clockwork')->name('debugbar.clockwork');
    $router->get('assets/stylesheets', 'AssetController@css')->name('debugbar.assets.css');
    $router->get('assets/javascript', 'AssetController@js')->name('debugbar.assets.js');

    if (class_exists(\Laravel\Telescope\Telescope::class)) {
        $router->get('telescope/{id}', 'TelescopeController@show')->name('debugbar.telescope');
    }
});
