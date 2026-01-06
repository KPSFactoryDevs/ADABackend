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

    public $documentId;

    public $liveStatus;

    public function __construct($page, $documentId, $liveStatus)
    {
        $this->page = $page;
        $this->documentId = $documentId;

        $this->liveStatus = $liveStatus;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {


        try {
         
            $params = array();
            $params = [
                'documentId' => $this->documentId,
                'page'=> $this->page,
                'liveStatus'=> $this->liveStatus,
            ];

            app()->call(CentraleRischiController::class . '@' . 'recap',$params);
        } catch (\Exception $e) {
          dump($e->getMessage(), $e->getTraceAsString());
        }

        

    }

    public function failed(Throwable $exception)
    {
        Log::debug($exception);
    }
}
