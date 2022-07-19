<?php

namespace MouYong\LaravelDoc;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MouYong\LaravelDoc\Http\Controllers\OpenapiController;

class LaravelDocServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/yapi.php', 'yapi');

        $this->publishes([
            __DIR__.'/../config/yapi.php' => config_path('yapi.php'),
        ], 'laravel-doc-config');

        $this->publishes([
            __DIR__.'/../stubs/Tests/Yapi' => base_path('tests/Yapi'),
        ], 'laravel-doc-yapi');

        $this->registerRoute();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function registerRoute()
    {
        if (! config('yapi.openapi.enable', true)) {
            return;
        }
        
        if (config('yapi.openapi.route.enable', true)) {
            Route::middleware(config('yapi.openapi.route.middleware', []))
                ->any(config('yapi.openapi.route.path', 'openapi'), [OpenapiController::class, 'show']);
        }
    }
}