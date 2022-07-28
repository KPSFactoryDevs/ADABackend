<?php

declare(strict_types = 1);

namespace App\Charts;

use Chartisan\PHP\Chartisan;
use ConsoleTVs\Charts\BaseChart;
use Illuminate\Http\Request;

class SampleChart extends BaseChart
{
    /**
     * Handles the HTTP request for the given chart.
     * It must always return an instance of Chartisan
     * and never a string or an array.
     */
    public function handler($papero): Chartisan
    {
        $papero = 4;
        return Chartisan::build()
            ->labels(['Un', 'Dos', 'Tres'])
            ->dataset('Sample', [1, 2, $papero]);
    }
}
