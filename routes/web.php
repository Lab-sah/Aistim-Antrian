<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QueueController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Halaman publik: kiosk pelanggan dan display cabang.
Route::get('/kiosk/{service}', [PageController::class, 'kiosk'])->name('kiosk');
Route::get('/display/{branch}', [PageController::class, 'display'])->name('display');
Route::get('/display/{branch}/data', [PageController::class, 'displayData'])->name('display.data');
Route::post('/services/{service}/queues', [QueueController::class, 'store'])
    ->name('queues.store');

// Login petugas.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Halaman dan aksi petugas loket: harus login.
Route::middleware('auth')->group(function () {
    Route::get('/loket', [PageController::class, 'loket'])->name('loket');
    Route::post('/queues/call-next', [QueueController::class, 'callNext'])
        ->name('queues.call-next');
    Route::patch('/queues/{queue}/status', [QueueController::class, 'updateStatus'])
        ->name('queues.status');
});
