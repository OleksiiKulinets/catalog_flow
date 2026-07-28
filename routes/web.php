<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/api-key', [ApiKeyController::class, 'store'])->name('api-key.store');
    Route::delete('/api-key', [ApiKeyController::class, 'destroy'])->name('api-key.destroy');

    Route::patch('/locale', [LocaleController::class, 'update'])->name('locale.update');
});

require __DIR__.'/auth.php';
