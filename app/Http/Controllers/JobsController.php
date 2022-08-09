<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;
use App\Jobs\ElaborateLatestCR;

class CompaniesController extends Controller
{


    public function runCentraleRischiJob()
    {
        $this->batchHolder = Bus::batch([])->then(function (Batch $batch) {
            // All jobs completed successfully...
        })->catch(function (Batch $batch, Throwable $e) {
            // First batch job failure detected...
        })->finally(function (Batch $batch) {
            // The batch has finished executing...
        })->name('Page_Extraction')->dispatch();
        for ($x = 1; $x <= 10; $x++) {
            ElaborateLatestCR::dispatch($x);
            $this->batchHolder->add(new ElaborateLatestCR($x));
        }
        return $this->batchHolder;
    }


}
