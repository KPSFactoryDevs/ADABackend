<?php


namespace App\Helpers\CentraleRischi;

use App\Http\Requests;
use App;
use App\Models\Bilanci;
use App\Models\Account;
use App\Models\cr;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use phpDocumentor\Reflection\Types\This;

class CentraleRischiAggregateData
{
    public $_data = false;

    public function crJsonToArray($json)
    {


        $crData = array();
        $currentMonth = false;
        $currentYear = false;
        $currentIntermediario = 'false';
        $currentCreditType = false;

        $currentGarante = false;
        $isLeggenda = false;
        $isInframensile = false;
        $isSegnalazione = true;
        $cointestazione = false;
        $canStartReading = false;
        //            dd($jsonArray);

        $categoriaSezione = array(
            "RISCHI A SCADENZA" => 'Cassa',
            "RISCHI AUTOLIQUIDANTI" => 'Cassa',
            "RISCHI A REVOCA" => 'Cassa',
            "RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI" => 'Informativa',
            "SOFFERENZE" => 'Sofferenze',
            "GARANZIE CONNESSE CON OPERAZIONI DI NATURA COMMERCIALE" => 'Firma',
            "GARANZIE RICEVUTE" => 'Garanzie',
            'Garanti' => 'Garanti'
        );

        foreach ($json as $singleJsonArray) {
            foreach ($singleJsonArray as $key => $value) {
                $cleanRow = (object)array_filter((array)$value, 'strlen');


                foreach ($cleanRow as $singleRow) {

                    if (strpos($singleRow, 'Cointestazione: ') !== false && strpos($singleRow, 'cod. CR') !== true) {
                        $explodedCointestazione = explode('CR', $singleRow);
                        $cointestazione = trim($explodedCointestazione[1]);
                    }

                    if (strpos($singleRow, 'DATA DI RIFERIMENTO:') !== false) {
                        $date = explode(' ', next($cleanRow));

                        $month = $date[0];
                        $year = $date[1];

                        if (!$currentYear || $year != $currentYear) {
                            $currentYear = $year;
                            if (!array_key_exists($year, $crData)) {
                                $crData[$currentYear] = array();
                            }
                        }
                        if (!$currentMonth || $month != $currentMonth) {
                            $currentMonth = $month;
                        }
                        if (!array_key_exists($month, $crData[$currentYear])) {
                            $crData[$currentYear][$currentMonth] = array();
                        }
                    }
                    if ($singleRow == 'Intermediario:') {
                        $intermed = next($cleanRow);
                        if (!$currentIntermediario || $intermed != $currentIntermediario) {
                            $currentIntermediario = $intermed;
                            $isSegnalazione = false;

                            if (!array_key_exists($currentMonth, $crData[$currentYear])) {
                                $crData[$currentYear][$currentMonth] = array();
                                if (!array_key_exists($currentIntermediario, $crData[$currentYear][$currentMonth])) {
                                    $crData[$currentYear][$currentMonth][$currentIntermediario] = array();
                                }
                            }
                        }
                    }


                    if (strpos($singleRow, 'Categoria') !== false) {

                        $categoriaArray = array();
                        for ($i = 0; $i <= count((array)$cleanRow); $i++) {

                            $categoriaArray[next($cleanRow)] = array();
                        }
                        $currentCategoryArray = $categoriaArray;
                    }

                    if (strpos($singleRow, 'Di seguito si riportano le segnalazioni') !== false) {
                        $isSegnalazione = true;
                    }


                    if (
                        (strpos($singleRow, 'RISCHI A') !== false ||
                            strpos($singleRow, 'GARANZIE CONNESSE') !== false ||
                            strpos($singleRow, 'SOFFERENZE') !== false ||
                            strpos($singleRow, 'CREDITI') !== false ||
                            strpos($singleRow, 'GARANZIE RICEVUTE') !== false)
                    ) {

                        foreach ($currentCategoryArray as $arrKey => $arrValue) {
                            if ($arrKey == 'Categoria') {
                                $currentCategoryArray['Categoria'] = $singleRow;
                            } else {
                                $currentCategoryArray[$arrKey] = next($cleanRow);
                            }
                        }


                        if (array_key_exists($currentCategoryArray['Categoria'], $categoriaSezione)) {
                            $sezione = $categoriaSezione[$currentCategoryArray['Categoria']];
                            if ($isSegnalazione) {
                                $sezione = 'Segnalazioni';
                            }
                            $crData[$currentYear][$currentMonth][$currentIntermediario][$sezione][] = array_merge($currentCategoryArray, array('Cointestazione' => $cointestazione));
                        }
                    }


                    if (strpos($singleRow, 'Garante') !== false) {
                        if (!$currentGarante || $currentGarante != $singleRow) {
                            $currentGarante = $singleRow;

                            $garanteArray = array();
                            $garanteArray['Garante'] = array();
                            for ($i = 2; $i <= count((array)$cleanRow); $i++) {
                                $garanteArray[next($cleanRow)] = array();
                            }
                            $currentGaranteArray = $garanteArray;
                        }
                    }


                    if (strpos($singleRow, 'codice censito') !== false) {
                        if (isset($currentGaranteArray)) {
                            if (!$currentGarante || $currentGarante != $singleRow) {
                                foreach ($currentGaranteArray as $arrKey => $arrValue) {
                                    if ($arrKey == 'Garante') {
                                        $currentGaranteArray['Garante'] = $singleRow;
                                    } else {
                                        $currentGaranteArray[$arrKey] = next($cleanRow);
                                    }
                                }
                                $crData[$currentYear][$currentMonth][$currentIntermediario]['Garanti'][] = $currentGaranteArray;
                            }
                        }
                    }
                }
            }
        }

        return $crData;
    }
}
