<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dispute History Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/audits/{audit}/history', [App\Http\Controllers\DisputeHistoryController::class, 'addEntry'])
        ->name('audits.history.add');
    Route::get('/audits/{audit}/history', [App\Http\Controllers\DisputeHistoryController::class, 'getHistory'])
        ->name('audits.history.get');
});