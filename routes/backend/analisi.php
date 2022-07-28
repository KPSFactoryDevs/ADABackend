<?php
/*
use App\Domains\Auth\Http\Controllers\Backend\Role\RoleController;
use App\Domains\Auth\Http\Controllers\Backend\User\DeactivatedUserController;
use App\Domains\Auth\Http\Controllers\Backend\User\DeletedUserController;
use App\Domains\Auth\Http\Controllers\Backend\User\UserController;
use App\Domains\Auth\Http\Controllers\Backend\User\UserPasswordController;
use App\Domains\Auth\Http\Controllers\Backend\User\UserSessionController;
use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use App\Models\Bilanci;
use App\Http\Controllers\Backend\AnalisiController;
use Tabuna\Breadcrumbs\Trail;

// All route names are prefixed with 'admin.auth'.
Route::group([
    'prefix' => 'analysis',
    'as' => 'analysis.',
    'middleware' => config('boilerplate.access.middleware.confirm'),
], function () {
    Route::group([
        'prefix' => 'analisi',
        'as' => 'analisi.',
    ], function () {
        Route::group([ 
        ], function () {
            Route::get('/create', [AnalisiController::class, 'create'])
                ->name('create')
                //->middleware('permission:admin.access.user.list|admin.access.user.deactivate|admin.access.user.clear-session|admin.access.user.impersonate|admin.access.user.change-password')
                ->breadcrumbs(function (Trail $trail) {
                    $trail->parent('admin.dashboard')
                        ->push(__('User Management'), route('admin.analysis.analisi.create'));
                });
			
			Route::get('/', [AnalisiController::class, 'index'])
                ->name('index')
                //->middleware('permission:admin.access.user.list|admin.access.user.deactivate|admin.access.user.clear-session|admin.access.user.impersonate|admin.access.user.change-password')
                ->breadcrumbs(function (Trail $trail) {
                    $trail->parent('admin.dashboard')
                        ->push(__('User Management'), route('admin.analysis.analisi.index'));
                });
			
 
				
			Route::post('/store', [AnalisiController::class, 'store'])
                ->name('store')
                //->middleware('permission:admin.access.user.list|admin.access.user.deactivate|admin.access.user.clear-session|admin.access.user.impersonate|admin.access.user.change-password')
                ->breadcrumbs(function (Trail $trail) {
                    $trail->parent('admin.dashboard')
                        ->push(__('User Management'), route('admin.analysis.analisi.store'));
                });

                Route::post('/store', [AnalisiController::class, 'store'])
                ->name('store')
                //->middleware('permission:admin.access.user.list|admin.access.user.deactivate|admin.access.user.clear-session|admin.access.user.impersonate|admin.access.user.change-password')
                ->breadcrumbs(function (Trail $trail) {
                    $trail->parent('admin.dashboard')
                        ->push(__('User Management'), route('admin.analysis.analisi.store'));
                });
 
			Route::group(['prefix' => '{bilancio}'], function () {
				Route::get('show', [AnalisiController::class, 'show'])
					->name('show');
				
				 Route::delete('/', [AnalisiController::class, 'destroy'])->name('destroy');
			});
        });

   
    });
 
});*/
