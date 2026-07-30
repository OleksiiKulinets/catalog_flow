<?php

namespace CatFlow\Settings\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'settings');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'settings');
    }
}
