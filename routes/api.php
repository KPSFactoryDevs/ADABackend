<?php

use Illuminate\Http\Request;
use App\Jobs\ElaborateLatestCR;
use App\Http\Controllers\CompaniesController;
// routes/api.php
use App\Http\Controllers\ClientsController;
use App\Http\Controllers\FICOAuthController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\DbMetaController;
use App\Http\Controllers\AIController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankTransactionController;
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

    Route::post('/assistant', [AIController::class, 'queryAI']);

    Route::post('/login', 'App\Http\Controllers\Auth\ApiAuthController@login')->name('login.api');
    Route::post('/logout', 'App\Http\Controllers\Auth\ApiAuthController@logout')->name('logout.api');

    // ANALISI DEL BILANCIO
    Route::get('/analisiBilancioGeneral/{id}', 'App\Http\Controllers\AnalisisController@getAnalisiBilancioFull');
    Route::post('/analisiBilancioBasic', 'App\Http\Controllers\AnalisisController@storeAnalisiBilancioData');


    // BILANCI
    Route::post('/predefinito', 'App\Financial\Bilanci\Controllers\BilanciController@bilancioPredefinito');
    Route::post('/copiaBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@copiaBilancio');
    Route::post('/recapBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@recap');

    Route::get('/recapForAi/{documentId}', 'App\Financial\Bilanci\Controllers\BilanciController@recapForAi');


    Route::post('/importBilancio', 'App\Financial\Bilanci\Controllers\BilanciController@store');
    Route::get('/getBilancio/{id}', 'App\Financial\Bilanci\Controllers\BilanciController@show');
    Route::get('/getAllBilanci', 'App\Financial\Bilanci\Controllers\BilanciController@index');
    Route::get('/getLatestYears', 'App\Financial\Bilanci\Controllers\BilanciController@getLatestYears');
    Route::delete('/bilancio/{idBilancio}', 'App\Financial\Bilanci\Controllers\BilanciController@destroy');
    Route::get('/getBilanciDocuments', 'App\Financial\Bilanci\Controllers\BilanciController@getDocuments');
    Route::post('/missingVoices', 'App\Financial\Bilanci\Controllers\BilanciController@missingVoices');
    Route::post('/updateMissingVoices', 'App\Financial\Bilanci\Controllers\BilanciController@updateMissingVoices');
    Route::post('/getDSCRAnalisi', 'App\Http\Controllers\AnalisisController@getDSCRAnalisi');
    Route::post('/getAgenziaEntrateAlert', 'App\Http\Controllers\AnalisisController@getAgenziaEntrateAlert');
    Route::post('/getInpsAlert', 'App\Http\Controllers\AnalisisController@getInpsAlert');
    Route::post('/getRetribuzioniAlert', 'App\Http\Controllers\AnalisisController@getRetribuzioniAlert');
    Route::post('/getFornitoriAlert', 'App\Http\Controllers\AnalisisController@getFornitoriAlert');
    Route::post('/getRiscossione', 'App\Http\Controllers\AnalisisController@getRiscossione');

    Route::get('/getSettori', 'App\Helpers\Bilanci\BilanciHelper@getSettori');

    Route::post('/setting', 'App\Financial\Bilanci\Controllers\BilanciController@modalitySetting');


    // CENTRALE RISCHI
    Route::get('/crAndamentale/{period}/{data_inizio?}/{data_fine?}/{inputBanks?}', 'App\Http\Controllers\CentraleRischiController@crAndamentale');
    Route::get('/crTrimestrale', 'App\Http\Controllers\CentraleRischiController@dettagliata');
    Route::post('/crRecap', 'App\Http\Controllers\CentraleRischiController@recap');
    Route::post('/importCr', 'App\Http\Controllers\CentraleRischiController@store');
    Route::get('/getCrDocuments', 'App\Http\Controllers\CentraleRischiController@getDocuments');
    Route::get('/getDocuments/{id}', 'App\Http\Controllers\CentraleRischiController@getDocumentsById');
    Route::delete('/deleteDocument/{idDocument}', 'App\Http\Controllers\CentraleRischiController@destroy');



    // SISTEMA DI ALLERTA
    Route::get('/generalAllerta/{id}/{idCr}/{userId}', 'App\Http\Controllers\AllertaController@allertaGeneral');
    Route::post('/questionarioAsis', 'App\Http\Controllers\AllertaController@questionarioSistemaAllerta');
    Route::post('/forwardlooking', 'App\Http\Controllers\AllertaController@forwardLooking');


    // Utente
    Route::post('/createUser', 'App\Http\Controllers\Frontend\User\AccountController@store');

    // PDF MONKEY
    Route::get('/reportBasicPDF/{id}', 'App\Http\Controllers\PDFController@templateReportBasic');
    Route::get('/reportAllerta/{idBilancio}/{idCr}', 'App\Http\Controllers\PDFController@reportAllerta');
    Route::get('/reportAndamentale/{years}', 'App\Http\Controllers\PDFController@reportCrAndamentale');

 
});


Route::group(['middleware' => ['cors','json.response','auth:api']], function () {
    // Companies protette
    Route::get('/company', [CompaniesController::class, 'index']);
    Route::post('/company', [CompaniesController::class, 'store']);
    Route::get('/company/{company}', [CompaniesController::class, 'show']);
    Route::post('/company/{company}', [CompaniesController::class, 'update']);
    Route::delete('/company/{company}', [CompaniesController::class, 'destroy']);
});



Route::middleware('auth:api')->group(function () {
    Route::get('/clients', [ClientsController::class, 'index']);
    Route::post('/clients', [ClientsController::class, 'store']);
    Route::get('/clients/{client}', [ClientsController::class, 'show']);
    Route::put('/clients/{client}', [ClientsController::class, 'update']);
    Route::delete('/clients/{client}', [ClientsController::class, 'destroy']);
});




Route::middleware('auth:api')->group(function () {
    Route::get('/invoices', [InvoicesController::class, 'index']);
    Route::post('/invoices/import', [InvoicesController::class, 'importFromCloud']);
});




Route::middleware(['cors','json.response','auth:api'])->group(function () {
    Route::post('/fic/prepare',   [\App\Http\Controllers\FICOAuthController::class, 'prepare']);
    Route::get('/fic/status',     [\App\Http\Controllers\FICOAuthController::class, 'status']);
    Route::post('/fic/import',    [\App\Http\Controllers\FICOAuthController::class, 'import']);
    Route::post('/fic/disconnect',[\App\Http\Controllers\FICOAuthController::class, 'disconnect']);
});


Route::get('/db/schema', [DbMetaController::class, 'schema']); // puoi metterla sotto auth se preferisci
Route::post('/assistant', [AIController::class, 'queryAI']);   // già ce l'hai


Route::group(['middleware' => ['cors', 'json.response']], function () {
    Route::post('/assistant', [\App\Http\Controllers\AIController::class, 'queryAI']);
});



Route::middleware('auth:api')->group(function () {
    Route::get('/bank-accounts', [BankAccountController::class, 'index']);               // ?company_id=103
    Route::get('/bank-accounts/{id}', [BankAccountController::class, 'show']);
    Route::get('/bank-accounts/{id}/transactions', [BankTransactionController::class, 'index']); // filtri
});