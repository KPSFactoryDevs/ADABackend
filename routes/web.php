<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FICOAuthController;
use App\Http\Controllers\SsoController;

Route::get('/', [\App\Http\Controllers\Frontend\HomeController::class, 'index'])
    ->name('index');

// SSO Callback (from KPS Suites)
Route::get('sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

Route::middleware('web')->group(function () {
    Route::get('/fic/connect',  [FICOAuthController::class, 'connect'])->name('fic.connect');
    Route::get('/fic/callback', [FICOAuthController::class, 'callback'])->name('fic.callback');
});