<?php

use Illuminate\Support\Facades\Route;
use Packages\Settings\Http\Controllers\ApiKeyController;
use Packages\Settings\Http\Controllers\LocaleController;
use Packages\Settings\Http\Controllers\ProfileController;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/api-key', [ApiKeyController::class, 'store'])->name('api-key.store');
    Route::delete('/api-key', [ApiKeyController::class, 'destroy'])->name('api-key.destroy');

    Route::patch('/locale', [LocaleController::class, 'update'])->name('locale.update');
});
