<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FICOAuthController;

Route::get('/', [\App\Http\Controllers\Frontend\HomeController::class, 'index'])
    ->name('index');

Route::middleware('web')->group(function () {
    Route::get('/fic/connect',  [FICOAuthController::class, 'connect'])->name('fic.connect');
    Route::get('/fic/callback', [FICOAuthController::class, 'callback'])->name('fic.callback');
});