<?php

use CatFlow\Admin\Http\Controllers\Batch\BatchController;
use Illuminate\Support\Facades\Route;

Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
Route::get('/batches/create', [BatchController::class, 'create'])->name('batches.create');
Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
