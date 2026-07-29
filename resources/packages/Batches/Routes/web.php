<?php

use Illuminate\Support\Facades\Route;
use Packages\Batches\Http\Controllers\BatchController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
    Route::get('/batches/create', [BatchController::class, 'create'])->name('batches.create');
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
});
