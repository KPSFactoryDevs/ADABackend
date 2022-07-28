<?php

use App\Domains\Auth\Http\Controllers\Backend\Role\RoleController;
use App\Domains\Auth\Http\Controllers\Backend\User\DeactivatedUserController;
use App\Domains\Auth\Http\Controllers\Backend\User\DeletedUserController;
use App\Domains\Auth\Http\Controllers\Backend\User\UserController;
use App\Domains\Auth\Http\Controllers\Backend\User\UserPasswordController;
use App\Domains\Auth\Http\Controllers\Backend\User\UserSessionController;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Models\Bilanci;
use App\Financial\Bilanci\Controllers\BilanciController;
use Tabuna\Breadcrumbs\Trail;
use App\Http\Controllers\AnalisisController;
// All route names are prefixed with 'admin.auth'.
Route::group([
    'prefix' => 'analysis',
    'as' => 'analysis.'
], function () {
    Route::group([
        'prefix' => 'bilanci',
        'as' => 'bilanci.',
    ], function () {
        Route::group([
        ], function () {
            Route::get('/import', [BilanciController::class, 'create'])
                ->name('create')
                //->middleware('permission:admin.access.user.list|admin.access.user.deactivate|admin.access.user.clear-session|admin.access.user.impersonate|admin.access.user.change-password')
                ->breadcrumbs(function (Trail $trail) {
                    $trail->parent('admin.dashboard')
                        ->push(__('User Management'), route('admin.analysis.bilanci.create'));
                });

            Route::get('/', [BilanciController::class, 'index'])
                ->name('index')
                //->middleware('permission:admin.access.user.list|admin.access.user.deactivate|admin.access.user.clear-session|admin.access.user.impersonate|admin.access.user.change-password')
                ->breadcrumbs(function (Trail $trail) {
                    $trail->parent('admin.dashboard')
                        ->push(__('User Management'), route('admin.analysis.bilanci.index'));
                });

            Route::post('/recap', [BilanciController::class, 'recap'])
                ->name('recap')
                //->middleware('permission:admin.access.user.list|admin.access.user.deactivate|admin.access.user.clear-session|admin.access.user.impersonate|admin.access.user.change-password')
                ->breadcrumbs(function (Trail $trail) {
                    $trail->parent('admin.dashboard')
                        ->push(__('User Management'), route('admin.analysis.bilanci.recap'));
                });

            Route::get('/showCurrent/{bilancio}',[BilanciController::class, 'showCurrent'])
                ->name('showCurrent')->where('id', '[0-9]+');

            Route::get('/showAnalisys/{bilancio}',[AnalisisController::class, 'show'])
                ->name('showAnalisys')->where('id', '[0-9]+');




            Route::post('/store', [BilanciController::class, 'store'])
                ->name('store')
                //->middleware('permission:admin.access.user.list|admin.access.user.deactivate|admin.access.user.clear-session|admin.access.user.impersonate|admin.access.user.change-password')
                ->breadcrumbs(function (Trail $trail) {
                    $trail->parent('admin.dashboard')
                        ->push(__('User Management'), route('admin.analysis.bilanci.store'));
                });

            Route::group(['prefix' => '{bilancio}'], function () {
                Route::get('show', [BilanciController::class, 'show'])
                    ->name('show');

                Route::delete('/', [BilanciController::class, 'destroy'])->name('destroy');
            });
        });


    });

});
