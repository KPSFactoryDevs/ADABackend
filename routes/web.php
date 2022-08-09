<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\AnalisiController;
use App\Http\Controllers\AllertaController;
use App\Http\Controllers\SogliesController;
use App\Http\Controllers\BilanciController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\RoesController;

/*
 * Global Routes
 *
 * Routes that are used between both frontend and backend.
 */

// Switch between the included languages
Route::get('lang/{lang}', [LocaleController::class, 'change'])->name('locale.change');

/*
 * Frontend Routes
 */
Route::group(['as' => 'frontend.'], function () {
	includeRouteFiles(__DIR__ . '/frontend/');
});



/*
 * Backend Routes
 *
 * These routes can only be accessed by users with type `admin`
 */
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => 'admin'], function () {
	includeRouteFiles(__DIR__ . '/backend/');

	Route::group([
		'prefix' => 'pdf',
	], function () {

		Route::get('/allerta', 'App\Http\Controllers\PDFController@allerta')
			->name('pdf.allertaPDF');
		Route::get('/basicPDF/{bilancio?}', 'App\Http\Controllers\PDFController@basic')
			->name('pdf.basicPDF');
		Route::get('/sintetica', 'App\Http\Controllers\PDFController@sintetica')
			->name('pdf.sintetica');
		Route::get('/dettagliata', 'App\Http\Controllers\PDFController@dettagliata')
			->name('pdf.dettagliata');
	});


	Route::group([
		'prefix' => 'roes',
	], function () {
		Route::get('/', 'App\Http\Controllers\RoesController@index')
			->name('roes.roe.index');
		Route::get('/create', 'App\Http\Controllers\RoesController@create')
			->name('roes.roe.create');
		Route::get('/show/{roe}', 'App\Http\Controllers\RoesController@show')
			->name('roes.roe.show')->where('id', '[0-9]+');
		Route::get('/{roe}/edit', 'App\Http\Controllers\RoesController@edit')
			->name('roes.roe.edit')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\RoesController@store')
			->name('roes.roe.store');
		Route::put('roe/{roe}', 'App\Http\Controllers\RoesController@update')
			->name('roes.roe.update')->where('id', '[0-9]+');
		Route::delete('/roe/{roe}', 'App\Http\Controllers\RoesController@destroy')
			->name('roes.roe.destroy')->where('id', '[0-9]+');
	});

	Route::group([
		'prefix' => 'accounts',
	], function () {
		Route::get('/', 'App\Http\Controllers\AccountsController@index')
			->name('accounts.account.index');
		Route::get('/create', 'App\Http\Controllers\AccountsController@create')
			->name('accounts.account.create');
		Route::get('/show/{account}', 'App\Http\Controllers\AccountsController@showAzienda')
			->name('accounts.account.show')->where('id', '[0-9]+');
		Route::get('/{account}/edit', 'App\Http\Controllers\AccountsController@edit')
			->name('accounts.account.edit')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\AccountsController@store')
			->name('accounts.account.store');
		Route::put('account/{account}', 'App\Http\Controllers\AccountsController@update')
			->name('accounts.account.update')->where('id', '[0-9]+');
		Route::delete('/account/{account}', 'App\Http\Controllers\AccountsController@destroy')
			->name('accounts.account.destroy')->where('id', '[0-9]+');

		Route::get('/bilancio/{account}', 'App\Http\Controllers\AccountsController@bilancio')
			->name('accounts.account.bilancio');
		Route::get('/cr/{id}', 'App\Http\Controllers\AccountsController@cr')
			->name('accounts.account.cr');

		Route::post('/account/bilancio', 'App\Http\Controllers\AccountsController@recapBilancio')
			->name('accounts.account.recapbilancio');
		Route::post('/account/cr', 'App\Http\Controllers\AccountsController@recapCentraleRischi')
			->name('accounts.account.recapcr');

		Route::post('/account/storebilancio', 'App\Http\Controllers\AccountsController@storeBilancio')
			->name('accounts.account.storebilancio');
		Route::post('/account/storecr', 'App\Http\Controllers\AccountsController@storeCR')
			->name('accounts.account.storecr');

		Route::get('/account/showCR', 'App\Http\Controllers\AccountsController@showCR')
			->name('accounts.account.showCR');
	});

	Route::group([
		'prefix' => 'soglies',
	], function () {
		Route::get('/', 'App\Http\Controllers\SogliesController@index')
			->name('soglies.soglie.index');
		Route::get('/create', 'App\Http\Controllers\SogliesController@create')
			->name('soglies.soglie.create');
		Route::get('/show/{soglie}', 'App\Http\Controllers\SogliesController@show')
			->name('soglies.soglie.show');
		Route::get('/{soglie}/edit', 'App\Http\Controllers\SogliesController@edit')
			->name('soglies.soglie.edit');
		Route::post('/', 'App\Http\Controllers\SogliesController@store')
			->name('soglies.soglie.store');
		Route::put('soglie/{soglie}', 'App\Http\Controllers\SogliesController@update')
			->name('soglies.soglie.update');
		Route::delete('/soglie/{soglie}', 'App\Http\Controllers\SogliesController@destroy')
			->name('soglies.soglie.destroy');
	});

	Route::group([
		'prefix' => 'analisis',
	], function () {
		Route::get('/', 'App\Http\Controllers\AnalisisController@index')
			->name('analisis.analisi.index');
		Route::get('/create', 'App\Http\Controllers\AnalisisController@create')
			->name('analisis.analisi.create');
		Route::get('/show/{analisi}', 'App\Http\Controllers\AnalisisController@show')
			->name('analisis.analisi.show')->where('id', '[0-9]+');
		Route::get('/{analisi}/edit', 'App\Http\Controllers\AnalisisController@edit')
			->name('analisis.analisi.edit')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\AnalisisController@store')
			->name('analisis.analisi.store');
		Route::put('analisi/{analisi}', 'App\Http\Controllers\AnalisisController@update')
			->name('analisis.analisi.update')->where('id', '[0-9]+');
		Route::delete('/analisi/{analisi}', 'App\Http\Controllers\AnalisisController@destroy')
			->name('analisis.analisi.destroy')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\AnalisisController@analisiBasic')
			->name('analisis.analisi.basic');
	});



	Route::group([
		'prefix' => 'vocibilancios',
	], function () {
		Route::get('/', 'App\Http\Controllers\VocibilanciosController@index')
			->name('vocibilancios.vocibilancio.index');
		Route::get('/create', 'App\Http\Controllers\VocibilanciosController@create')
			->name('vocibilancios.vocibilancio.create');
		Route::get('/show/{vocibilancio}', 'App\Http\Controllers\VocibilanciosController@show')
			->name('vocibilancios.vocibilancio.show')->where('id', '[0-9]+');
		Route::get('/{vocibilancio}/edit', 'App\Http\Controllers\VocibilanciosController@edit')
			->name('vocibilancios.vocibilancio.edit')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\VocibilanciosController@store')
			->name('vocibilancios.vocibilancio.store');
		Route::put('vocibilancio/{vocibilancio}', 'App\Http\Controllers\VocibilanciosController@update')
			->name('vocibilancios.vocibilancio.update')->where('id', '[0-9]+');
		Route::delete('/vocibilancio/{vocibilancio}', 'App\Http\Controllers\VocibilanciosController@destroy')
			->name('vocibilancios.vocibilancio.destroy')->where('id', '[0-9]+');
	});


	Route::group([
		'prefix' => 'bilanci',
	], function () {
		Route::get('/', 'App\Http\Controllers\BilanciController@index')
			->name('bilanci.bilancio.index');
		Route::get('/allbilanci', 'App\Http\Controllers\BilanciController@datatable')
			->name('bilanci.bilancio.datatable');
		Route::get('/create', 'App\Http\Controllers\BilanciController@create')
			->name('bilanci.bilancio.create');
		Route::get('/show/{bilancio}', 'App\Http\Controllers\BilanciController@show')
			->name('bilanci.bilancio.show')->where('id', '[0-9]+');
		Route::get('/{bilancio}/edit', 'App\Http\Controllers\BilanciController@edit')
			->name('bilanci.bilancio.edit')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\BilanciController@store')
			->name('bilanci.bilancio.store');
		Route::post('/provvisorio', 'App\Http\Controllers\BilanciController@storeProvvisorio')
			->name('bilanci.bilancio.storeProvvisorio');
		Route::put('bilancio/{bilancio}', 'App\Http\Controllers\BilanciController@update')
			->name('bilanci.bilancio.update')->where('id', '[0-9]+');
		Route::delete('/bilancio/{bilancio}', 'App\Http\Controllers\BilanciController@destroy')
			->name('bilanci.bilancio.destroy')->where('id', '[0-9]+');
		Route::get('/delete/{bilancio}', 'App\Http\Controllers\BilanciController@delete')
			->name('bilanci.bilancio.delete')->where('id', '[0-9]+');
		Route::get('/bilancio/provvisorio', 'App\Http\Controllers\BilanciController@provvisorio')
			->name('bilanci.bilancio.provvisorio');
	});



	Route::group([
		'prefix' => 'indicis',
	], function () {
		Route::get('/', 'App\Http\Controllers\IndicisController@index')
			->name('indicis.indici.index');
		Route::get('/create', 'App\Http\Controllers\IndicisController@create')
			->name('indicis.indici.create');
		Route::get('/show/{indici}', 'App\Http\Controllers\IndicisController@show')
			->name('indicis.indici.show')->where('id', '[0-9]+');
		Route::get('/{indici}/edit', 'App\Http\Controllers\IndicisController@edit')
			->name('indicis.indici.edit')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\IndicisController@store')
			->name('indicis.indici.store');
		Route::put('indici/{indici}', 'App\Http\Controllers\IndicisController@update')
			->name('indicis.indici.update')->where('id', '[0-9]+');
		Route::delete('/indici/{indici}', 'App\Http\Controllers\IndicisController@destroy')
			->name('indicis.indici.destroy')->where('id', '[0-9]+');
	});

	Route::group([
		'prefix' => 'pesis',
	], function () {
		Route::get('/', 'App\Http\Controllers\PesiController@index')
			->name('pesis.pesi.index');
		Route::get('/create', 'App\Http\Controllers\PesiController@create')
			->name('pesis.pesi.create');
		Route::get('/show/{pesi}', 'App\Http\Controllers\PesiController@show')
			->name('pesis.pesi.show')->where('id', '[0-9]+');
		Route::get('/{pesi}/edit', 'App\Http\Controllers\PesiController@edit')
			->name('pesis.pesi.edit')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\PesiController@store')
			->name('pesis.pesi.store');
		Route::put('pesi/{pesi}', 'App\Http\Controllers\PesiController@update')
			->name('pesis.pesi.update')->where('id', '[0-9]+');
		Route::delete('/pesi/{pesi}', 'App\Http\Controllers\PesiController@destroy')
			->name('pesis.pesi.destroy')->where('id', '[0-9]+');
	});

	Route::group([
		'prefix' => 'ranges',
	], function () {
		Route::get('/', 'App\Http\Controllers\RangeController@index')
			->name('ranges.range.index');
		Route::get('/create', 'App\Http\Controllers\RangeController@create')
			->name('ranges.range.create');
		Route::get('/show/{range}', 'App\Http\Controllers\RangeController@show')
			->name('ranges.range.show')->where('id', '[0-9]+');
		Route::get('/{range}/edit', 'App\Http\Controllers\RangeController@edit')
			->name('ranges.range.edit')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\RangeController@store')
			->name('ranges.range.store');
		Route::put('range/{range}', 'App\Http\Controllers\RangeController@update')
			->name('ranges.range.update')->where('id', '[0-9]+');
		Route::delete('/range/{range}', 'App\Http\Controllers\RangeController@destroy')
			->name('ranges.range.destroy')->where('id', '[0-9]+');
	});


	Route::group([
		'prefix' => 'analisistype',
	], function () {
		Route::get('/', 'App\Http\Controllers\RangeController@index')
			->name('analisistypes.analisistype.index');
		Route::get('/create', 'App\Http\Controllers\RangeController@create')
			->name('analisistypes.analisistype.create');
		Route::get('/show/{range}', 'App\Http\Controllers\RangeController@show')
			->name('analisistypes.analisistype.show')->where('id', '[0-9]+');
		Route::get('/{range}/edit', 'App\Http\Controllers\RangeController@edit')
			->name('analisistypes.analisistype.edit')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\RangeController@store')
			->name('analisistypes.analisistype.store');
		Route::put('range/{range}', 'App\Http\Controllers\RangeController@update')
			->name('analisistypes.analisistype.update')->where('id', '[0-9]+');
		Route::delete('/range/{range}', 'App\Http\Controllers\RangeController@destroy')
			->name('analisistypes.analisistype.destroy')->where('id', '[0-9]+');
	});


	Route::group([
		'prefix' => 'voci',
	], function () {
		Route::get('/', 'App\Http\Controllers\VociController@index')
			->name('vocis.voci.index');
		Route::get('/create', 'App\Http\Controllers\VociController@create')
			->name('vocis.voci.create');
		Route::get('/show/{voci}', 'App\Http\Controllers\VociController@show')
			->name('vocis.voci.show')->where('id', '[0-9]+');
		Route::get('/{voci}/edit', 'App\Http\Controllers\VociController@edit')
			->name('vocis.voci.edit')->where('id', '[0-9]+');
		Route::post('/', 'App\Http\Controllers\VociController@store')
			->name('vocis.voci.store');
		Route::put('voci/{voci}', 'App\Http\Controllers\VociController@update')
			->name('vocis.voci.update')->where('id', '[0-9]+');
		Route::delete('/voci/{voci}', 'App\Http\Controllers\VociController@destroy')
			->name('vocis.voci.destroy')->where('id', '[0-9]+');
	});




	Route::group([
		'prefix' => 'centralerischi',
	], function () {

		Route::get('/create', 'App\Http\Controllers\CentraleRischiController@create')
			->name('cr.centralerischi.create');
		Route::post('/recap', 'App\Http\Controllers\CentraleRischiController@recap')
			->name('cr.centralerischi.recap');
		Route::get('/show', 'App\Http\Controllers\CentraleRischiController@show')
			->name('cr.centralerischi.show');

		Route::get('/allcr', 'App\Http\Controllers\CentraleRischiController@datatable')
			->name('cr.centralerischi.datatable');
		Route::post('/andamentale', 'App\Http\Controllers\CentraleRischiController@crAndamentale')
			->name('cr.centralerischi.andamentale');
		Route::get('/truncate', 'App\Http\Controllers\CentraleRischiController@truncateCR')
			->name('cr.centralerischi.truncate');
		Route::get('/dettagliata', 'App\Http\Controllers\CentraleRischiController@dettagliata')
			->name('cr.centralerischi.dettagliata');
	});

	Route::group([
		'prefix' => 'allerta',
	], function () {

		Route::get('/', 'App\Http\Controllers\AllertaController@index')
			->name('allerta.index');
		Route::post('allerta/selezione', 'App\Http\Controllers\AllertaController@selezione')
			->name('allerta.selezione');
		Route::post('allerta/analisi', 'App\Http\Controllers\AllertaController@analisi')
			->name('allerta.analisi');
		Route::get('/general/{bilancio}', 'App\Http\Controllers\AllertaController@allertaGeneral')
			->name('allerta.general');
		Route::post('/questionario', 'App\Http\Controllers\AllertaController@questionarioSistemaAllerta')
			->name('allerta.questionario');
		Route::post('/forward', 'App\Http\Controllers\AllertaController@forwardLooking')
			->name('allerta.forwardlooking');
		Route::get('/select', 'App\Http\Controllers\AllertaController@select')
			->name('allerta.select');
	});

	Route::group([
		'prefix' => 'sistemi',
	], function () {
		Route::get('/basic', 'App\Http\Controllers\SistemiAllertaController@indexBasic')
			->name('sistemi.basic.index');
		Route::get('/advanced', 'App\Http\Controllers\SistemiAllertaController@indexAdvanced')
			->name('sistemi.advanced.index');
		Route::get('/allerta/basic/{id}', 'App\Http\Controllers\SistemiAllertaController@basic')
			->name('sistemi.basic.basic')->where('id', '[0-9]+');
		Route::get('/allerta/advanced/{id}', 'App\Http\Controllers\SistemiAllertaController@advanced')
			->name('sistemi.advanced.advanced')->where('id', '[0-9]+');
	});
});





