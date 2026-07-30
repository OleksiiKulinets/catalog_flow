<?php

namespace CatFlow\Batch\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BatchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'batch');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'batch');
    }
}
