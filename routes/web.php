<?php

use App\Http\Controllers\QueueController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Kiosk pelanggan: ambil nomor, tanpa login.
Route::post('/services/{service}/queues', [QueueController::class, 'store'])
    ->name('queues.store');

// Petugas loket: harus login.
Route::middleware('auth')->group(function () {
    Route::post('/queues/call-next', [QueueController::class, 'callNext'])
        ->name('queues.call-next');
    Route::patch('/queues/{queue}/status', [QueueController::class, 'updateStatus'])
        ->name('queues.status');
});
