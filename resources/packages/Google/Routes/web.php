<?php

use Illuminate\Support\Facades\Route;
use Packages\Google\Http\Controllers\GoogleOAuthController;

Route::middleware('guest')->group(function () {
    Route::get('/auth/google/redirect', [GoogleOAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback'])->name('google.callback');
});
