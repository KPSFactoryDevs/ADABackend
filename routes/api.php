<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});


Route::group(['middleware' => ['cors', 'json.response']], function () {

    Route::post('/login', 'App\Http\Controllers\Auth\ApiAuthController@login')->name('login.api');
    Route::post('/logout', 'App\Http\Controllers\Auth\ApiAuthController@logout')->name('logout.api');

    // ANALISI DEL BILANCIO
    Route::get('/analisiBilancioGeneral/{id}', 'App\Http\Controllers\AnalisisController@show');
	Route::post('/analisiBilancioGeneral', 'App\Http\Controllers\AnalisisController@store');
	

    // BILANCI
 	Route::post('/copiaBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@copiaBilancio');
    Route::post('/recapBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@recap');
    Route::post('/importBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@store');
    Route::get('/getBilancio/{id}', 'App\Financial\Bilanci\Controllers\BilanciController@show');
    Route::get('/getAllBilanci', 'App\Financial\Bilanci\Controllers\BilanciController@index');
	Route::get('/getLatestYears', 'App\Financial\Bilanci\Controllers\BilanciController@getLatestYears');
		
    // CENTRALE RISCHI
    Route::get('/crAndamentale/{period}', 'App\Http\Controllers\CentraleRischiController@crAndamentale');
    Route::get('/crTrimestrale', 'App\Http\Controllers\CentraleRischiController@dettagliata');
    Route::get('/crRecap', 'App\Http\Controllers\CentraleRischiController@recap');
    Route::post('/importCr', 'App\Http\Controllers\CentraleRischiController@store');
	Route::get('/getDocuments', 'App\Http\Controllers\CentraleRischiController@getDocuments');
	Route::get('/getDocuments/{id}', 'App\Http\Controllers\CentraleRischiController@getDocumentsById');
    
    // SISTEMA DI ALLERTA
    Route::get('/generalAllerta/{id}', 'App\Http\Controllers\AllertaController@allertaGeneral');
    // GET LAST SCORES BILANCIO - CR - ALLERTA
    Route::get('/getScores', 'App\Http\Controllers\AllertaController@getAllScores');

	// Utente
    Route::post('/createUser', 'App\Http\Controllers\Frontend\User\AccountController@store');
	
});


// Route::group(['middleware' => ['web']], function () {

//     // ANALISI DEL BILANCIO
//     Route::get('/analisiBilancioGeneral/{id}', 'App\Http\Controllers\AnalisisController@show');

//     // BILANCI
//     Route::post('/recapBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@recap');
//     Route::post('/importBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@store');
//     Route::get('/getBilancio/{id}', 'App\Financial\Bilanci\Controllers\BilanciController@show');
//     Route::get('/getAllBilanci', 'App\Financial\Bilanci\Controllers\BilanciController@index');
    
//     // CENTRALE RISCHI
//     Route::get('/crAndamentale/{period}', 'App\Http\Controllers\CentraleRischiController@crAndamentale');
//     Route::get('/crTrimestrale', 'App\Http\Controllers\CentraleRischiController@dettagliata');
//     Route::get('/crRecap', 'App\Http\Controllers\CentraleRischiController@recap');
//     Route::post('/importCr', 'App\Http\Controllers\CentraleRischiController@store');
    
//     // SISTEMA DI ALLERTA
//     Route::get('/generalAllerta/{id}', 'App\Http\Controllers\AllertaController@allertaGeneral');
    
// });
