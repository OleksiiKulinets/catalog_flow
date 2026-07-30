<?php

namespace CatFlow\Prompt\Providers;

use Illuminate\Support\ServiceProvider;

class PromptServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
