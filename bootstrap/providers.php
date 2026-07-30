<?php

use App\Providers\AppServiceProvider;
use CatFlow\Admin\Providers\AdminServiceProvider;
use CatFlow\Auth\Providers\AuthServiceProvider;
use CatFlow\Batch\Providers\BatchServiceProvider;
use CatFlow\File\Providers\FileServiceProvider;
use CatFlow\Prompt\Providers\PromptServiceProvider;
use CatFlow\Settings\Providers\SettingsServiceProvider;
use CatFlow\User\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    UserServiceProvider::class,
    AuthServiceProvider::class,
    SettingsServiceProvider::class,
    FileServiceProvider::class,
    PromptServiceProvider::class,
    BatchServiceProvider::class,
    AdminServiceProvider::class,
];
