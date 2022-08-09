<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Http\Controllers\CentraleRischiController;
use Illuminate\Http\Request;

use Illuminate\Bus\Batchable;
use Throwable;

class ElaborateLatestCR implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $page;

    public function __construct($page)
    {
        $this->page = $page;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

            $params = array();
            $params = [
                'page'=> $this->page
            ];

            app()->call(CentraleRischiController::class . '@' . 'recap',$params);

    }

    public function failed(Throwable $exception)
    {

        Log::debug($exception);


    }
}
