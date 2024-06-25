<?php

namespace Inmanturbo\ImportFieldMapper;

use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

class ImportFieldMapperServiceProvider extends ServiceProvider
{

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/import-field-mapper.php', 'import-field-mapper');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'field-mapper');

        $this->publishes([
            __DIR__.'/../config/field-mapper.php' => config_path('field-mapper.php'),
        ], 'config');

        $this->app->booted(function () {
            Volt::mount([
                __DIR__.'/../resources/views/livewire',
                __DIR__.'/../resources/views/pages',
            ]);
        });
    }
}

