<?php

use CatFlow\Admin\Http\Controllers\Settings\ApiKeyController;
use CatFlow\Admin\Http\Controllers\Settings\LocaleController;
use CatFlow\Admin\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/api-key', [ApiKeyController::class, 'store'])->name('api-key.store');
    Route::delete('/api-key', [ApiKeyController::class, 'destroy'])->name('api-key.destroy');

    Route::patch('/locale', [LocaleController::class, 'update'])->name('locale.update');
});
