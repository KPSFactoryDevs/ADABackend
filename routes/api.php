<?php

use Illuminate\Http\Request;
use App\Jobs\ElaborateLatestCR;
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
    Route::get('/analisiBilancioGeneral/{id}', 'App\Http\Controllers\AnalisisController@getAnalisiBilancioFull');
    Route::post('/analisiBilancioBasic', 'App\Http\Controllers\AnalisisController@storeAnalisiBilancioData');


    // BILANCI
    Route::post('/predefinito', 'App\Financial\Bilanci\Controllers\BilanciController@bilancioPredefinito');
    Route::post('/copiaBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@copiaBilancio');
    Route::post('/recapBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@recap');
    Route::post('/importBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@store');
    Route::get('/getBilancio/{id}', 'App\Financial\Bilanci\Controllers\BilanciController@show');
    Route::get('/getAllBilanci', 'App\Financial\Bilanci\Controllers\BilanciController@index');
    Route::get('/getLatestYears', 'App\Financial\Bilanci\Controllers\BilanciController@getLatestYears');
    Route::delete('/bilancio/{idBilancio}', 'App\Financial\Bilanci\Controllers\BilanciController@destroy');




    // CENTRALE RISCHI
    Route::get('/crAndamentale/{period}/{data_inizio?}/{data_fine?}/{inputBanks?}', 'App\Http\Controllers\CentraleRischiController@crAndamentale');
    Route::get('/crTrimestrale', 'App\Http\Controllers\CentraleRischiController@dettagliata');
    Route::post('/crRecap', 'App\Http\Controllers\CentraleRischiController@recap');
    Route::post('/importCr', 'App\Http\Controllers\CentraleRischiController@store');
    Route::get('/getDocuments', 'App\Http\Controllers\CentraleRischiController@getDocuments');
    Route::get('/getDocuments/{id}', 'App\Http\Controllers\CentraleRischiController@getDocumentsById');
    Route::delete('/deleteDocument/{idDocument}', 'App\Http\Controllers\CentraleRischiController@destroy');



    // SISTEMA DI ALLERTA
    Route::get('/generalAllerta/{id}/{idCr}', 'App\Http\Controllers\AllertaController@allertaGeneral');
    Route::post('/questionarioAsis', 'App\Http\Controllers\AllertaController@questionarioSistemaAllerta');
    Route::post('/forwardlooking', 'App\Http\Controllers\AllertaController@forwardLooking');


    // Utente
    Route::post('/createUser', 'App\Http\Controllers\Frontend\User\AccountController@store');

    // PDF MONKEY
    Route::get('/reportBasicPDF/{id}', 'App\Http\Controllers\PDFController@reportBasicPdf');
    Route::get('/reportAllerta/{idBilancio}/{idCr}', 'App\Http\Controllers\PDFController@reportAllerta');
    Route::get('/reportAndamentale/{years}', 'App\Http\Controllers\PDFController@reportCrAndamentale');

    // Companies
    Route::get('/company', 'App\Http\Controllers\CompaniesController@index');
    Route::get('/createCompany', 'App\Http\Controllers\CompaniesController@create');
    Route::get('/showCompany/{company}', 'App\Http\Controllers\CompaniesController@show');
    Route::post('/company', 'App\Http\Controllers\CompaniesController@store');
    Route::post('/editCompany/{company}', 'App\Http\Controllers\CompaniesController@update');
    Route::delete('/company/{company}', 'App\Http\Controllers\CompaniesController@destroy');
});
