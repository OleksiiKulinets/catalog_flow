<?php

use App\Providers\AppServiceProvider;
use Packages\Auth\Providers\AuthServiceProvider;
use Packages\Batches\Providers\BatchesServiceProvider;
use Packages\Dashboard\Providers\DashboardServiceProvider;
use Packages\Google\Providers\GoogleServiceProvider;
use Packages\Settings\Providers\SettingsServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    DashboardServiceProvider::class,
    BatchesServiceProvider::class,
    SettingsServiceProvider::class,
    GoogleServiceProvider::class,
];
