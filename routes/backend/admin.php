<?php

use App\Http\Controllers\Backend\DashboardController;
use Tabuna\Breadcrumbs\Trail;
use App\Models\Account;

// All route names are prefixed with 'admin.'.
Route::redirect('/', '/admin/dashboard', 301);
Route::get('dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->breadcrumbs(function (Trail $trail) {
        $trail->push(__('Home'), route('admin.dashboard'));
    });

Route::get('/faq', [DashboardController::class, 'faq'])->name('faq');
Route::get('/Home', [DashboardController::class, 'Home']);
Route::get('/Home', 'App\Http\Controllers\BilanciController@fileHome')
->name('bilanci.bilancio.fileHome');

Route::get('/page2', [DashboardController::class, 'page2']);
Route::get('/page3', [DashboardController::class, 'page3']);
Route::get('/manutenzione', [DashboardController::class, 'manutenzione']);



