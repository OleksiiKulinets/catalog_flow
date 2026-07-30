<?php

namespace CatFlow\Admin\Providers;

use CatFlow\Admin\View\Components\AppLayout;
use CatFlow\Admin\View\Components\GuestLayout;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Boot the Admin package: the HTTP layer for every other package
     * (controllers, requests, routes) plus the shared presentation shell
     * (layouts, generic Blade components, welcome page).
     *
     * Routes are not registered here: bootstrap/app.php's withRouting()
     * already loads Admin's Routes/admin-routes.php as the app's single web
     * routes file, wrapped in the "web" middleware group.
     */
    public function boot(): void
    {
        View::addLocation(__DIR__.'/../Resources/views');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'admin');

        // These live outside the App\View\Components namespace Laravel scans
        // by default, so <x-app-layout>/<x-guest-layout> need registering explicitly.
        Blade::component('app-layout', AppLayout::class);
        Blade::component('guest-layout', GuestLayout::class);
    }
}
