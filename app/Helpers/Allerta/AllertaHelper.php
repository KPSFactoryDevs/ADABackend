<?php

namespace App\Helpers\Allerta;

use App\Helpers\CentraleRischi\CrExtractorHelper;
use Illuminate\Support\Facades\DB;
use App\Models\cr;
use DateTime;
use Illuminate\Support\Facades\Date;

class AllertaHelper
{
    private $crExtractorHelper;
    public $_period = false;
    public $_countMonths = false;
    public $_documentId = false;

    public function getPunteggioCR($alerts)
    {
        $scoreCR = 0;

        if (!$alerts['1']) {
            $scoreCR += 0.04;
        }
        if (!$alerts['2']) {
            $scoreCR += 0.045;
        }
        if (!$alerts['3']) {
            $scoreCR += 0.04;
        }
        if (!$alerts['4']) {
            $scoreCR += 0.05;
        }
        if (!$alerts['5']) {
            $scoreCR += 0.05;
        }
        if (!$alerts['6']) {
            $scoreCR += 0.04;
        }
        if (!$alerts['7']) {
            $scoreCR += 0.04;
        }
        if (!$alerts['8']) {
            $scoreCR += 0.04;
        }
        if (!$alerts['9']) {
            $scoreCR += 0.06;
        }
        if (!$alerts['10']) {
            $scoreCR += 0.06;
        }
        if (!$alerts['11']) {
            $scoreCR += 0.068;
        }
        if (!$alerts['12']) {
            $scoreCR += 0.068;
        }
        if (!$alerts['13']) {
            $scoreCR += 0.068;
        }
        if (!$alerts['14']) {
            $scoreCR += 0.091;
        }
        if (!$alerts['15']) {
            $scoreCR += 0.12;
        }
        if (!$alerts['16']) {
            $scoreCR += 0.12;
        }
        return $scoreCR;
    }

    public function setPeriod($period)
    {
        $this->_period = $period;
    }

    public function getPeriod()
    {
        return $this->_period;
    }

    public function addMonthsToCount()
    {
        if (!$this->_countMonths) {
            $this->setCountMonths();
        }

        $this->_countMonths++;
    }
    public function setCountMonths()
    {
        $this->_countMonths = 0;
    }

    public function getCountMonths()
    {
        return $this->_countMonths;
    }

    public function setDocumentId($documentId)
    {
        $this->_documentId = $documentId;
    }

    public function getDocumentId()
    {
        return $this->_documentId;
    }

    public function setCrExtractor($CrHelper)
    {
        $this->crExtractorHelper = $CrHelper;
    }

    public function buildPeriodArray()
    {
        if (!$this->_period) {
            return false;
        }

        $periodsContainer = array();
        $queryPeriodArray = array();
        $this->setCountMonths();
        foreach ($this->_period as $anno => $mesi) {
            foreach ($mesi as $mese => $index) {
                $this->addMonthsToCount();
                $queryPeriodArray['anno'] = $anno;
                $queryPeriodArray['mese'] = $mese;
                $periodsContainer[] = $queryPeriodArray;
            }
        }

        return $periodsContainer;
    }

    public function getTotaleAffidamentiPerFirma($categories, $latestYear, $latestMonth)
    {
        return cr::groupBy('nome_banca')
            ->groupBy('categoria')
            ->selectRaw("nome_banca, categoria, SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")
            ->where('document_id', $this->_documentId)
            ->where('anno', $latestYear)->where('sezione', 'Firma')
            ->where('mese', $latestMonth)
            ->whereIn('categoria', $categories)
            ->get()
            ->toArray();
    }

    public function getLastTwoMonths()
    {
        // $cont = 0;
        $trimestrePeriod = $this->getTrimestrePeriod($this->crExtractorHelper->getPeriod());

        $lastYear = array_key_last($trimestrePeriod);
        $lastMonth = array_key_last($trimestrePeriod[$lastYear]);
        unset($trimestrePeriod[$lastYear][$lastMonth]);
        $secondLastYear = array_key_last($trimestrePeriod);
        $secondLastMonth = array_key_last($trimestrePeriod[$secondLastYear]);

        $periods[$secondLastYear][$secondLastMonth] = null;
        $periods[$lastYear][$lastMonth] = null;

        return $periods;
    }

    public function getTriennioPeriod($periods, $banks)
    {
        $crHelper = new CrExtractorHelper;
        $crHelper->setPeriod($periods);
        // $cleanCR = $crHelper->getAllDataToArray($banks);

        $latestYear = array_key_last($periods);
        $latestMonth = array_key_last($periods[$latestYear]);
        $lastDate = new DateTime(cr::select('date')
            ->where('anno', '=', $latestYear)
            ->where('mese', '=', $latestMonth)
            ->where('document_id', $this->_documentId)
            ->first()->date);

        $triennio = new DateTime($lastDate->format('Y-m-d'));
        $triennio = $triennio->modify('-35 months');

        $triennioQuery = json_decode(DB::table('crs')
            ->select('anno', 'mese', 'date')
            ->where('document_id', $this->_documentId)
            ->whereRaw("date between '" . $triennio->format('Y-m-01') . "' and '" . $lastDate->format('Y-m-t') . "'")
            ->groupBy('date', 'anno', 'mese')
            ->orderBy('date')
            ->get(), true);

        $triennioPeriod = array();

        foreach ($triennioQuery as $index => $value) {
            $triennioPeriod[$value['anno']][$value['mese']] = null;
        }

        return $triennioPeriod;
    }

    public function getTrimestrePeriod($periods)
    {
        $crHelper = new CrExtractorHelper;
        $crHelper->setPeriod($periods);

        // $latestYear = array_key_last($periods);
        //  $latestMonth = array_key_last($periods[$latestYear]);

        $trimestrePeriod = array();

        $tmp = cr::select('anno', 'mese', 'date')->where('document_id', $this->_documentId)->orderBy('date', 'desc')->distinct()->take(3)->get();


        $tmp = cr::select('anno', 'mese', 'date')->where('document_id', $this->_documentId)->orderBy('date', 'desc')->distinct()->take(3)->get();


        for ($i = 0; $i <= 2; $i++) {
            if (isset($tmp[$i])) {
                $trimestrePeriod[$tmp[$i]->anno][$tmp[$i]->mese] = null;
            }
        }

        return $trimestrePeriod;
    }

    public function getLastYearPeriod($lastYear, $lastMonth)
    {
        $lastDate = (cr::select('date')
            ->where('anno', '=', $lastYear)
            ->where('mese', '=', $lastMonth)
            ->where('document_id', $this->_documentId)
            ->first())->date;
        $dateVar = explode('-', $lastDate);

        $ultimoAnno = ((int)$dateVar[0] - 1) . '-' . $dateVar[1] . '-' . $dateVar[2];

        $annoQuery = json_decode(DB::table('crs')
            ->select('anno', 'mese', 'date')
            ->where('document_id', $this->_documentId)
            ->whereRaw("date between '" . $ultimoAnno . "' and '" . $lastDate . "'")
            ->groupBy('date', 'anno', 'mese')
            ->orderBy('date')
            ->get(), true);

        $lastPeriod = array();

        foreach ($annoQuery as $index => $value) {
            $lastPeriod[$value['anno']][$value['mese']] = null;
        }
        return $lastPeriod;
    }

    public function getSconfiniSignificativi($banks)
    {
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );

        $period = $this->crExtractorHelper->buildPeriodArray();
        $allMonthsCount = $this->crExtractorHelper->getCountMonths();
        $affidamenti = array();

        foreach ($period as $index => $queryPeriodArray) {
            $CentraleRischiModel =
                cr::selectRaw('utilizzato, anno, mese, nome_banca, categoria')
                ->where('document_id', $this->_documentId)
                ->whereIn('nome_banca', $banks)
                ->whereIn('categoria', $categories)
                ->whereRaw('CAST(utilizzato as SIGNED) >= 0')
                ->where('sezione', 'Cassa')
                ->groupBy(array('utilizzato', 'anno', 'mese', 'nome_banca', 'categoria'))
                ->get();

            foreach ($CentraleRischiModel as $singleIndex => $singleModel) {
                $affidamenti[$singleModel->nome_banca] = isset($affidamenti[$singleModel->nome_banca]) ? $affidamenti[$singleModel->nome_banca] + $singleModel->utilizzato : $singleModel->utilizzato;
            }
        }

        foreach ($affidamenti as $singleBank => $data) {
            $affidamenti[$singleBank] = $data / $allMonthsCount;
        }

        foreach ($period as $index => $queryPeriodArray) {
            $CentraleRischiModel =
                cr::selectRaw('nome_banca, accordato_operativo, utilizzato')
                ->where('document_id', $this->_documentId)
                ->whereIn('nome_banca', array_keys($affidamenti))
                ->whereIn('categoria', $categories)
                ->whereRaw('CAST(utilizzato as SIGNED) > CAST(accordato_operativo as SIGNED)')
                ->where('sezione', 'Cassa')
                ->get();


            foreach ($CentraleRischiModel as $singleIndex => $singleModel) {
                if (((float)$singleModel->accordato_operativo - (float)$singleModel->utilizzato) > ($affidamenti[$singleModel->nome_banca] / 100)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getMediaAccordatoOperativo($period, $categories)
    {
        // $mediaAccordatoOp = array();

        $periodStart = new DateTime((cr::select('date')
            ->where('document_id', $this->_documentId)
            ->where('anno', array_key_first($period))
            ->where('mese', array_key_first($period[array_key_first($period)]))
            ->first())->date);

        $periodEnd = new DateTime((cr::select('date')
            ->where('document_id', $this->_documentId)
            ->where('anno', array_key_last($period))
            ->where('mese', array_key_last($period[array_key_last($period)]))
            ->first())->date);

        $accordatoModel = cr::selectRaw('nome_banca as Banca, SUM(accordato_operativo) as Accordato')
            ->where('document_id', $this->_documentId)
            ->where('date', '>=', $periodStart->format('Y-m-01'))
            ->where('date', '<=', $periodEnd->format('Y-m-t'))
            ->whereIn('categoria', $categories)
            ->groupBy('nome_banca')
            ->get()
            ->toArray();

        $allMonthsCount = $this->getMonthsCountFromPeriod($period);

        $mediaAccordato = array();

        foreach ($accordatoModel as $label => $bankData) {
            $mediaAccordato[$bankData['Banca']] = $bankData['Accordato'] / $allMonthsCount;
        }

        return $mediaAccordato;
    }

    public function getMediaAccordatoOperativoGeneral($period, $categories, $monthsCount)
    {
        $mediaAccordato = 0;

        foreach ($period as $singleYear => $months) {
            foreach ($months as $singleMonth => $patonza) {
                $accordatoModel = cr::selectRaw('SUM(accordato_operativo) as Accordato')
                    ->where('document_id', $this->_documentId)
                    ->where('anno', $singleYear)
                    ->where('mese', $singleMonth)
                    ->whereIn('categoria', $categories)
                    ->where('accordato_operativo', '>', 0)
                    ->get()->first()
                    ->toArray();

                $mediaAccordato = ($mediaAccordato) + $accordatoModel['Accordato'];
            }
        }

        return $mediaAccordato / $monthsCount;
    }

    public function getAccordatoOperativo($period, $categories)
    {
        // $mediaAccordatoOp = array();

        $periodStart = new DateTime((cr::select('date')
            ->where('document_id', $this->_documentId)
            ->where('anno', array_key_first($period))
            ->where('mese', array_key_first($period[array_key_first($period)]))
            ->first())->date);

        $periodEnd = new DateTime((cr::select('date')
            ->where('document_id', $this->_documentId)
            ->where('anno', array_key_last($period))
            ->where('mese', array_key_last($period[array_key_last($period)]))
            ->first())->date);

        $accordatoModel = cr::selectRaw('nome_banca as Banca, SUM(accordato_operativo) as Accordato')
            ->where('document_id', $this->_documentId)
            ->where('date', '>=', $periodStart->format('Y-m-01'))
            ->where('date', '<=', $periodEnd->format('Y-m-t'))
            ->whereIn('categoria', $categories)
            ->groupBy('nome_banca')
            ->get()->toArray();

        $mediaAccordato = array();

        foreach ($accordatoModel as $label => $bankData) {
            $mediaAccordato[$bankData['Banca']] = $bankData['Accordato'];
        }

        return $mediaAccordato;
    }

    public function getAnalisiCRUno($banks)
    {
        $sofferenze = $this->crExtractorHelper->getSofferenze($banks);
        $sconfini = $this->crExtractorHelper->getTotaleSconfini($banks);
        $countBanks = $this->crExtractorHelper->getCountBanks($banks);
        $creditiPassatiPerdita = $this->crExtractorHelper->getCreditiPassatiPerdita($banks);
        $scoringCR = $this->crExtractorHelper->getScoring($banks, $countBanks, $sconfini, $sofferenze, $creditiPassatiPerdita);

        if ($scoringCR <= 0.5) {
            return true;
        } else {
            return false;
        }
    }

    public function getAnalisiCRDue($banks)
    {
        $sconfini = $this->crExtractorHelper->getTotaleSconfini($banks);

        return ($sconfini['SconfiniTotali'] > 2 || $this->getSconfiniSignificativi($banks));
    }

    public function getAnalisiCRTre($banks)
    {
        $sconfini = $this->crExtractorHelper->getTotaleSconfini($banks);
        $sconfiniUltimiDueMesi = array();

        if (isset($sconfini['Categorie']['RISCHI A SCADENZA']) && $sconfini['Categorie']['RISCHI A SCADENZA'] > 2) {
            return true;
        }

        $lastTwoMonths = $this->getLastTwoMonths();

        $periods = $lastTwoMonths;

        foreach ($periods as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $nothing) {
                $items = cr::where('anno', $singleYear)
                    ->where('document_id', $this->_documentId)
                    ->where('mese', $singleMonth)
                    ->where('categoria', 'RISCHI A SCADENZA')
                    ->whereRaw('CAST(utilizzato AS SIGNED) > CAST(accordato_operativo AS SIGNED)')
                    ->get();

                foreach ($items as $singleItem => $data) {
                    $sconfiniUltimiDueMesi[$data->nome_banca][$data->categoria] = isset($sconfiniUltimiDueMesi[$data->nome_banca][$data->categoria]) ? ($sconfiniUltimiDueMesi[$data->nome_banca][$data->categoria]) + 1 : 1;

                    if ($sconfiniUltimiDueMesi[$data->nome_banca][$data->categoria] >= 2) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function getMonthsCountFromPeriod($period)
    {
        $cont = 0;
        foreach ($period as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $smt) {
                $cont++;
            }
        }
        return $cont;
    }

    public function periodToDateInterval($period)
    {
        $periodStart = array('Year' => array_key_first($period), 'Month' => array_key_first($period[array_key_first($period)]));

        $startDate = cr::where([['anno', $periodStart['Year']], ['mese', $periodStart['Month']]])->where('document_id', $this->_documentId)->get()->first()->date;

        $periodEnd = array('Year' => array_key_last($period), 'Month' => array_key_last($period[array_key_last($period)]));

        $endDate = cr::where([['anno', $periodEnd['Year']], ['mese', $periodEnd['Month']]])->where('document_id', $this->_documentId)->get()->first()->date;

        return array('start' => $startDate, 'end' => $endDate);
    }

    public function getAnalisiCRQuattro($triennioPeriod, $trimestrePeriod, $latestYear, $latestMonth, $categories)
    {
        $periods = $this->crExtractorHelper->getPeriod();

        // $arrayGaranzie = array();

        $firstYear = array_key_first($periods);
        $firstYearMonth = array_key_first($periods[$firstYear]);
        $earlyYearDate = new DateTime((cr::select('date')->where('anno', $firstYear)->where('document_id', $this->_documentId)->where('mese', $firstYearMonth)->first())->date);

        $lastMonthDate = new DateTime((cr::select('date')->where('anno', $latestYear)->where('document_id', $this->_documentId)->where('mese', $latestMonth)->first())->date);

        $triennioYear = array_key_first($triennioPeriod);
        $threeYearsMonth = array_key_first($triennioPeriod[$triennioYear]);
        $threeYearsDate = new DateTime((cr::select('date')->where('anno', $triennioYear)->where('document_id', $this->_documentId)->where('mese', $threeYearsMonth)->first())->date);

        $trimestreYear = array_key_first($trimestrePeriod);
        $trimestreYearMonth = array_key_first($trimestrePeriod[$trimestreYear]);
        $trimestreYearDate = new DateTime((cr::select('date')->where('anno', $trimestreYear)->where('document_id', $this->_documentId)->where('mese', $trimestreYearMonth)->first())->date);

        $totGaranzie = array(
            "Anno" => (cr::selectRaw("SUM(garanzia) as totGaranzia")->whereRaw("date between '" . $earlyYearDate->format('Y-m-01') . "' and '" . $lastMonthDate->format('Y-m-t') . "'")->where('document_id', $this->_documentId)->where('sezione', 'Garanti')->get()->toArray())[0]['totGaranzia'],
            "Triennio" => ((cr::selectRaw("SUM(garanzia) as totGaranzia")->whereRaw("date between '" . $threeYearsDate->format('Y-m-01') . "' and '" . $lastMonthDate->format('Y-m-t') . "'")->where('document_id', $this->_documentId)->where('sezione', 'Garanti')->get()->toArray())[0]['totGaranzia'] / 36) * 12,
            "Trimestre" => ((cr::selectRaw("SUM(garanzia) as totGaranzia")->whereRaw("date between '" . $trimestreYearDate->format('Y-m-01') . "' and '" . $lastMonthDate->format('Y-m-t') . "'")->where('document_id', $this->_documentId)->where('sezione', 'Garanti')->get()->toArray())[0]['totGaranzia'] / $this->getMonthsCountFromPeriod($trimestrePeriod)),
            "UltimoMese" => (cr::selectRaw("SUM(garanzia) as totGaranzia")->where("anno", $latestYear)->where('mese', $latestMonth)->where('sezione', 'Garanti')->get()->toArray())[0]['totGaranzia']
        );

        if ($totGaranzie["Triennio"] != 0) {
            if ((- (1 - $totGaranzie["Anno"] / $totGaranzie["Triennio"])) > 0.10) {
                $totAccordato = array(
                    "Anno" => (cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo")->whereRaw("date between '" . $earlyYearDate->format('Y-m-01') . "' and '" . $lastMonthDate->format('Y-m-t') . "'")->where('document_id', $this->_documentId)->whereIn('categoria', $categories)->get()->toArray())[0]['totAccordatoOperativo'],
                    "Triennio" => ((cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo")->whereRaw("date between '" . $threeYearsDate->format('Y-m-01') . "' and '" . $lastMonthDate->format('Y-m-t') . "'")->where('document_id', $this->_documentId)->whereIn('categoria', $categories)->get()->toArray())[0]['totAccordatoOperativo'] / 36) * 12,
                );
                if ((- (1 - $totAccordato["Anno"] / $totAccordato["Triennio"])) > 0) {
                    return true;
                }
            }
        }
        if ($totGaranzie["Trimestre"] != 0) {
            if ((- (1 - $totGaranzie["UltimoMese"] / $totGaranzie["Trimestre"])) > 0.10) {
                $totAccordato = array(
                    "Trimestre" => ((cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo")->whereRaw("date between '" . $trimestreYearDate->format('Y-m-01') . "' and '" . $lastMonthDate->format('Y-m-t') . "'")->whereIn('categoria', $categories)->where('document_id', $this->_documentId)->get()->toArray())[0]['totAccordatoOperativo'] / $this->getMonthsCountFromPeriod($trimestrePeriod)),
                    "UltimoMese" => (cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo")->where("anno", $latestYear)->where('mese', $latestMonth)->whereIn('categoria', $categories)->where('document_id', $this->_documentId)->get()->toArray()[0]['totAccordatoOperativo'])
                );
                if ((- (1 - $totAccordato["UltimoMese"] / $totAccordato["Trimestre"])) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAnalisiCRCinque($lastYearPeriod)
    {
        $aumentiGaranzie = array();

        foreach ($lastYearPeriod as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $platanoPicchiatore) {
                $currentMonth = new DateTime((cr::select('date')->where('anno', $singleYear)->where('document_id', $this->_documentId)->where('mese', $singleMonth)->first())->date);
                $nextMonth = (new DateTime($currentMonth->format('Y-m-d')));
                $nextMonth = $nextMonth->modify('+1 month');

                $joins = DB::table('crs as t1')
                    ->join('crs as t2', 't1.nome_banca', '=', 't2.nome_banca')
                    ->selectRaw('t1.nome_banca as banca,  t1.document_id as document1, t2.document_id as document2, t1.garanzia as garantito1, t2.garanzia as garantito2, t1.date as date1, t2.date as date2, t1.categoria as cat1, t2.categoria as cat2, t1.localizzazione as loc1, t2. localizzazione as loc2, t1.garantito as nomeGarantito1, t2.garantito as nomeGarantito2, t1.stato_rapporto as statoRapporto1, t2.stato_rapporto as statoRapporto2, t1.tipo_garanzia as tipoGaranzia1, t2.tipo_garanzia as tipoGaranzia2, t1.codice_coint as coint1, t2.codice_coint as coint2')
                    ->where('t1.categoria', 'GARANZIE RICEVUTE')
                    ->where('t2.categoria', 'GARANZIE RICEVUTE')
                    ->where('t1.document_id', $this->_documentId)
                    ->where('t2.document_id', $this->_documentId)
                    ->whereRaw("t1.date between '" . $currentMonth->format('Y-m-01') . "' and '" . $currentMonth->format('Y-m-t') . "'")
                    ->whereRaw("t2.date between '" . $nextMonth->format('Y-m-01') . "' and '" . $nextMonth->format('Y-m-t') . "'")
                    ->get();

                foreach ($joins as $label => $singleJoin) {
                    if ($singleJoin->coint1 == $singleJoin->coint2 && $singleJoin->loc1 == $singleJoin->loc2 && $singleJoin->nomeGarantito1 == $singleJoin->nomeGarantito2 && $singleJoin->statoRapporto1 == $singleJoin->statoRapporto2 && $singleJoin->tipoGaranzia1 == $singleJoin->tipoGaranzia2) {
                        if ((float)str_replace('.', '', $singleJoin->garantito2) > (float)str_replace('.', '', $singleJoin->garantito1)) {
                            $aumentiGaranzie[$singleJoin->banca][$singleJoin->date1] = isset($aumentiGaranzie[$singleJoin->banca][$singleJoin->date1]) ? ($aumentiGaranzie[$singleJoin->banca][$singleJoin->date1]) + 1 : 1;
                            if ($aumentiGaranzie[$singleJoin->banca] > 2) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public function getAnalisiCRSei($lastYearPeriod, $categories, $banks)
    {
        $aumentiImpagati = array();
        $impagatiPerBanca = array();
        $utilizzatoPerBanca = array();

        foreach ($lastYearPeriod as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $platanoPicchiatore) {
                $currentMonth = new DateTime((cr::select('date')->where('anno', $singleYear)->where('document_id', $this->_documentId)->where('mese', $singleMonth)->first())->date);
                $nextMonth = (new DateTime($currentMonth->format('Y-m-d')));
                $nextMonth = $nextMonth->modify('+1 month');

                $sum = $this->crExtractorHelper->getPesiAffidamentiPerBanca($categories, $singleYear, $singleMonth, $banks);

                foreach ($banks as $singleBank) {
                    $utilizzatoPerBanca[$singleBank] = 0;
                }
                foreach ($sum as $index => $tmpData) {
                    $utilizzatoPerBanca[$tmpData['nome_banca']] = isset($utilizzatoPerBanca[$tmpData['nome_banca']]) ? ($utilizzatoPerBanca[$tmpData['nome_banca']]) + $tmpData['totUtilizzato'] : $tmpData['totUtilizzato'];
                }

                $joins = DB::table('crs as t1')
                    ->join('crs as t2', 't1.nome_banca', '=', 't2.nome_banca')
                    ->selectRaw('t1.nome_banca as banca,  t1.document_id as document1, t2.document_id as document2, t1.importo_garantito as garantito1, t2.importo_garantito as garantito2, t1.date as date1, t2.date as date2, t1.localizzazione as loc1, t2.localizzazione as loc2, t1.divisa as divisa1, t2.divisa as divisa2, t1.categoria as cat1, t2.categoria as cat2')
                    ->where('t1.stato_rapporto', 'Crediti impagati')
                    ->where('t2.stato_rapporto', 'Crediti impagati')
                    ->where('t1.sezione', 'Informativa')
                    ->where('t2.sezione', 'Informativa')
                    ->where('t1.document_id', $this->_documentId)
                    ->where('t2.document_id', $this->_documentId)
                    ->whereIn('t1.categoria', array('RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI'))
                    ->whereIn('t2.categoria', array('RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI'))
                    ->whereRaw("t1.date between '" . $currentMonth->format('Y-m-01') . "' and '" . $currentMonth->format('Y-m-t') . "'")
                    ->whereRaw("t2.date between '" . $nextMonth->format('Y-m-01') . "' and '" . $nextMonth->format('Y-m-t') . "'")
                    ->get();

                foreach ($joins as $label => $singleJoin) {
                    $impagatiPerBanca[$singleJoin->banca] = isset($impagatiPerBanca[$singleJoin->banca]) ? ($impagatiPerBanca[$singleJoin->banca]) + ((float)$singleJoin->garantito1) : (float)$singleJoin->garantito1;

                    if ($singleJoin->divisa1 == $singleJoin->divisa2 && $singleJoin->loc1 == $singleJoin->loc2) {

                        if ((float)$singleJoin->garantito2 > (float)$singleJoin->garantito1) {
                            $aumentiImpagati[$singleJoin->banca] = isset($aumentiImpagati[$singleJoin->banca]) ? ($aumentiImpagati[$singleJoin->banca]) + 1 : 1;
                            if ($aumentiImpagati[$singleJoin->banca] > 2) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        foreach ($impagatiPerBanca as $singleBank => $totImpagati) {
            if (($utilizzatoPerBanca[$singleBank] / $totImpagati) > 0.25) {
                return true;
            }
        }

        return false;
    }

    public function getAnalisiCRSette($lastYearPeriod)
    {
        $allMonthsCount = $this->crExtractorHelper->getCountMonths();
        $categories = array(
            'RISCHI A REVOCA',
            'RISCHI A SCADENZA'
        );

        foreach ($categories as $singleCategoria) {
            foreach ($lastYearPeriod as $singleYear => $multipleMonths) {
                foreach ($multipleMonths as $singleMonth => $platanoPicchiatore) {
                    $currentMonth = new DateTime((cr::select('date')->where('anno', $singleYear)->where('document_id', $this->_documentId)->where('mese', $singleMonth)->first())->date);
                    $nextMonth = (new DateTime($currentMonth->format('Y-m-d')));
                    $nextMonth = $nextMonth->modify('+1 month');

                    $sum[] = cr::selectRaw("SUM(accordato_operativo) as mediaAccordatoOperativo, nome_banca")->where('document_id', $this->_documentId)->where('anno', $singleYear)->where('mese', $singleMonth)->where('categoria', $singleCategoria)->groupBy('nome_banca')->get()->toArray();

                    foreach ($sum as $index => $banksArray) {
                        foreach ($banksArray as $bankIndex => $bankData) {
                            $accordatoMedioMensile[$bankData['nome_banca']] = isset($accordatoMedioMensile[$bankData['nome_banca']]) ? $accordatoMedioMensile[$bankData['nome_banca']] + $bankData["mediaAccordatoOperativo"] : $bankData["mediaAccordatoOperativo"];
                        }
                    }
                }
            }
        }
        foreach ($accordatoMedioMensile as $bank => $totValue) {
            $accordatoMedioMensile[$bank] = $totValue / $allMonthsCount;
        }

        $aumentiAccordato = array();

        foreach ($categories as $singleCategoria) {
            foreach ($lastYearPeriod as $singleYear => $multipleMonths) {
                foreach ($multipleMonths as $singleMonth => $platanoPicchiatore) {
                    $currentMonth = new DateTime((cr::select('date')->where('anno', $singleYear)->where('document_id', $this->_documentId)->where('mese', $singleMonth)->first())->date);
                    $nextMonth = (new DateTime($currentMonth->format('Y-m-d')));
                    $nextMonth = $nextMonth->modify('+1 month');

                    $joins = DB::table('crs as t1')
                        ->join('crs as t2', 't1.nome_banca', '=', 't2.nome_banca')
                        ->selectRaw('t1.nome_banca as banca,  t1.document_id as document1, t2.document_id as document2, t1.accordato_operativo as accordato1, t2.accordato_operativo as accordato2, t1.date as date1, t2.date as date2, t1.localizzazione as loc1, t2.localizzazione as loc2, t1.divisa as divisa1, t2.divisa as divisa2, t1.categoria as cat1, t2.categoria as cat2')
                        ->where('t1.categoria', $singleCategoria)
                        ->where('t2.categoria', $singleCategoria)
                        ->where('t1.document_id', $this->_documentId)
                        ->where('t2.document_id', $this->_documentId)
                        ->whereRaw("t1.date between '" . $currentMonth->format('Y-m-01') . "' and '" . $currentMonth->format('Y-m-t') . "'")
                        ->whereRaw("t2.date between '" . $nextMonth->format('Y-m-01') . "' and '" . $nextMonth->format('Y-m-t') . "'")
                        ->get();

                    foreach ($joins as $label => $singleJoin) {
                        if ($singleJoin->divisa1 == $singleJoin->divisa2 && $singleJoin->loc1 == $singleJoin->loc2) {
                            if ((float)$singleJoin->accordato2 > (float)$singleJoin->accordato1) {
                                if (((float)$singleJoin->accordato2 - (float)$singleJoin->accordato1) > ($accordatoMedioMensile[$singleJoin->banca] / 5)) {
                                    return true;
                                }
                                $aumentiAccordato[$singleJoin->banca] = isset($aumentiAccordato[$singleJoin->banca]) ? ($aumentiAccordato[$singleJoin->banca]) + 1 : 1;
                                if ($aumentiAccordato[$singleJoin->banca] > 2) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public function getAnalisiCROtto($lastYearPeriod, $latestYear, $latestMonth, $trimestrePeriod, $triennioPeriod)
    {
        // $periods = $this->crExtractorHelper->getPeriod();

        $categories = array(
            'RISCHI A SCADENZA'
        );

        $lastMonthDate = new DateTime((cr::select('date')
            ->where('document_id', $this->_documentId)
            ->where('anno', $latestYear)
            ->where('mese', $latestMonth)
            ->first())->date);

        $trimestreDate = new DateTime($lastMonthDate->format('Y-m-d'));
        $trimestreDate = $trimestreDate->modify('-2 months');

        $lastYearDate = new DateTime($lastMonthDate->format('Y-m-d'));
        $lastYearDate = $lastYearDate->modify('-11 months');

        $lastThreeYearsDate = new DateTime($lastMonthDate->format('Y-m-d'));
        $lastThreeYearsDate = $lastThreeYearsDate->modify('-35 months');

        $accordatoScadenzaTrimestre = cr::selectRaw("SUM(accordato_operativo) as somma")
            ->where('date', '>=', $trimestreDate->format('Y-m-01'))
            ->where('date', '<=', $lastMonthDate->format('Y-m-t'))
            ->where('document_id', $this->_documentId)
            ->where('categoria', 'RISCHI A SCADENZA')
            ->where('tipo_attivita', '<>', 'Leasing')
            ->where('sezione', 'Cassa')
            ->get()->first()->somma / $this->getMonthsCountFromPeriod($trimestrePeriod);

        $accordatoScadenzaUltimoMese = cr::selectRaw("SUM(accordato_operativo) as somma")
            ->where('anno', $latestYear)
            ->where('mese', $latestMonth)
            ->where('document_id', $this->_documentId)
            ->where('categoria', 'RISCHI A SCADENZA')
            ->where('tipo_attivita', '<>', 'Leasing')
            ->where('sezione', 'Cassa')
            ->get()->first()->somma;

        $accordatoScadenzaUltimoAnno = cr::selectRaw("SUM(accordato_operativo) as somma")
            ->where('date', '>=', $lastYearDate->format('Y-m-01'))
            ->where('date', '<=', $lastMonthDate->format('Y-m-t'))
            ->where('document_id', $this->_documentId)
            ->where('categoria', 'RISCHI A SCADENZA')
            ->where('tipo_attivita', '<>', 'Leasing')
            ->where('sezione', 'Cassa')
            ->get()->first()->somma;

        $accordatoScadenzaUltimiTreAnni = (cr::selectRaw("SUM(accordato_operativo) as somma")
            ->where('date', '>=', $lastThreeYearsDate->format('Y-m-01'))
            ->where('date', '<=', $lastMonthDate->format('Y-m-t'))
            ->where('document_id', $this->_documentId)
            ->where('categoria', 'RISCHI A SCADENZA')
            ->where('tipo_attivita', '<>', 'Leasing')
            ->where('sezione', 'Cassa')
            ->get()->first()->somma / 36) * 12;

        $rapportoTrimestrale = $accordatoScadenzaTrimestre == 0 ? 1 : - (1 - ($accordatoScadenzaUltimoMese / $accordatoScadenzaTrimestre));
        $rapportoTriennale = $accordatoScadenzaUltimiTreAnni == 0 ? 1 : - (1 - ($accordatoScadenzaUltimoAnno / $accordatoScadenzaUltimiTreAnni));

        return ($rapportoTrimestrale > 0.20 || $rapportoTriennale > 0.20);
    }

    public function getAnalisiCRNove($lastYearPeriod, $latestYear, $latestMonth)
    {
        $categories = array(
            'RISCHI A REVOCA'
        );

        $aumentiUtilizzato = array();
        $importiAumenti = array();

        foreach ($categories as $singleCategoria) {
            foreach ($lastYearPeriod as $singleYear => $multipleMonths) {
                foreach ($multipleMonths as $singleMonth => $platanoPicchiatore) {
                    $currentMonth = new DateTime((cr::select('date')->where('document_id', $this->_documentId)->where('anno', $singleYear)->where('mese', $singleMonth)->first())->date);
                    $nextMonth = (new DateTime($currentMonth->format('Y-m-d')));
                    $nextMonth = $nextMonth->modify('+1 month');

                    $joins = DB::table('crs as t1')
                        ->join('crs as t2', 't1.nome_banca', '=', 't2.nome_banca')
                        ->selectRaw('t1.nome_banca as banca,  t1.document_id as document1, t2.document_id as document2, t1.utilizzato as utilizzato1, t2.utilizzato as utilizzato2, t1.date as date1, t2.date as date2, t1.localizzazione as loc1, t2.localizzazione as loc2, t1.divisa as divisa1, t2.divisa as divisa2, t1.categoria as cat1, t2.categoria as cat2')
                        ->where('t1.categoria', $singleCategoria)
                        ->where('t2.categoria', $singleCategoria)
                        ->where('t1.document_id', $this->_documentId)
                        ->where('t2.document_id', $this->_documentId)
                        ->whereRaw("t1.date between '" . $currentMonth->format('Y-m-01') . "' and '" . $currentMonth->format('Y-m-t') . "'")
                        ->whereRaw("t2.date between '" . $nextMonth->format('Y-m-01') . "' and '" . $nextMonth->format('Y-m-t') . "'")
                        ->get();

                    foreach ($joins as $label => $singleJoin) {
                        if ($singleJoin->divisa1 == $singleJoin->divisa2 && $singleJoin->loc1 == $singleJoin->loc2) {
                            if ((float)$singleJoin->utilizzato2 > (float)$singleJoin->utilizzato1) {
                                $aumentiUtilizzato[$singleJoin->banca] = isset($aumentiUtilizzato[$singleJoin->banca]) ? ($aumentiUtilizzato[$singleJoin->banca]) + 1 : 1;

                                $importiAumenti[$singleJoin->banca][] = (float)$singleJoin->utilizzato1 == 0 ? 100 : (((float)$singleJoin->utilizzato2 - (float)$singleJoin->utilizzato1) / (float)$singleJoin->utilizzato1) * 100;
                            }
                        }
                    }
                }
            }
        }
        foreach ($aumentiUtilizzato as $singleBank => $aumenti) {
            if ($aumenti > 4) {
                if (array_sum($importiAumenti[$singleBank]) / count($importiAumenti[$singleBank]) > 20) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAnalisiCRDieci($lastYearPeriod, $latestYear, $latestMonth)
    {
        $categories = array(
            'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI',
            'RISCHI AUTOLIQUIDANTI'
        );

        $periods = $this->crExtractorHelper->buildPeriodArray();

        $utilizzatoModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $utilizzatoModel->orWhere(function ($query) use ($queryPeriodArray, $categories) {
                $query->where($queryPeriodArray);
                $query->whereIn('categoria', $categories);
                $query->where('utilizzato', '!=', "");
            })->where('document_id', $this->_documentId);
        }

        $utilizzatoModel = $utilizzatoModel
            ->groupBy('date', 'anno', 'mese', 'utilizzato', 'divisa', 'localizzazione', 'nome_banca', 'categoria', 'accordato_operativo')
            ->selectRaw('anno, mese, utilizzato ,divisa , localizzazione, nome_banca , categoria , date, accordato_operativo')
            ->orderBy('date')
            ->get();

        $sortedModel = array();

        foreach ($utilizzatoModel as $singleMode => $modelData) {
            $dateObj = new DateTime($modelData->date);
            $sortedModel[$dateObj->format('Y')][$dateObj->format('m')][$modelData->nome_banca] = $modelData;
        }

        $conteggioAumenti = array();
        $rapportoAffidamenti = array();

        foreach ($sortedModel as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $multipleBanks) {
                foreach ($multipleBanks as $singleBank => $singleBankData) {
                    $currentMonth = new DateTime($singleYear . '-' . $singleMonth);

                    $nextMonth = new DateTime($currentMonth->format('Y-m'));

                    $nextMonth = $nextMonth->modify('+1 month');

                    if (isset($sortedModel[$nextMonth->format('Y')][$nextMonth->format('m')][$singleBank])) {
                        $currentBankModel = array(
                            'Banca' => $sortedModel[$currentMonth->format('Y')][$currentMonth->format('m')][$singleBank]->nome_banca,
                            'Divisa' => $sortedModel[$currentMonth->format('Y')][$currentMonth->format('m')][$singleBank]->divisa,
                            'Localizzazione' => $sortedModel[$currentMonth->format('Y')][$currentMonth->format('m')][$singleBank]->localizzazione,
                            'Categoria' => $sortedModel[$currentMonth->format('Y')][$currentMonth->format('m')][$singleBank]->categoria,
                            'Utilizzato' => (float)$sortedModel[$currentMonth->format('Y')][$currentMonth->format('m')][$singleBank]->utilizzato
                        );

                        $nextBankModel = array(
                            'Banca' => $sortedModel[$nextMonth->format('Y')][$nextMonth->format('m')][$singleBank]->nome_banca,
                            'Divisa' => $sortedModel[$nextMonth->format('Y')][$nextMonth->format('m')][$singleBank]->divisa,
                            'Localizzazione' => $sortedModel[$nextMonth->format('Y')][$nextMonth->format('m')][$singleBank]->localizzazione,
                            'Categoria' => $sortedModel[$nextMonth->format('Y')][$nextMonth->format('m')][$singleBank]->categoria,
                            'Utilizzato' => (float)$sortedModel[$nextMonth->format('Y')][$nextMonth->format('m')][$singleBank]->utilizzato
                        );

                        if ($currentBankModel['Banca'] == $nextBankModel['Banca'] && $currentBankModel['Divisa'] == $nextBankModel['Divisa'] && $currentBankModel['Localizzazione'] == $nextBankModel['Localizzazione'] && $currentBankModel['Categoria'] == $nextBankModel['Categoria']) {
                            if (($nextBankModel['Utilizzato'] - $currentBankModel['Utilizzato']) > ($currentBankModel['Utilizzato'] * (15 / 100)) || $nextBankModel['Utilizzato'] > $currentBankModel['Utilizzato'] && $currentBankModel['Utilizzato'] == 0) {
                                $conteggioAumenti[$currentBankModel['Banca']] = isset($conteggioAumenti[$currentBankModel['Banca']]) ? ($conteggioAumenti[$currentBankModel['Banca']]) + 1 : 1;
                                if ($conteggioAumenti[$currentBankModel['Banca']] >= 4) {
                                    return true;
                                }
                            }
                        }
                    }
                    if ((float)$singleBankData->accordato_operativo != 0) {
                        $rapportoAffidamenti[$singleBank] = isset($rapportoAffidamenti[$singleBank]) ? ($rapportoAffidamenti[$singleBank]) + ((float)$singleBankData->utilizzato / (float)$singleBankData->accordato_operativo) : ((float)$singleBankData->utilizzato / (float)$singleBankData->accordato_operativo);
                    }
                }
            }
        }

        foreach ($rapportoAffidamenti as $bankName => $rapporto) {
            $rapportoAffidamenti[$bankName] = $rapporto / $this->crExtractorHelper->getCountMonths();
            if ($rapportoAffidamenti[$bankName] > 0.98) {
                return true;
            }
        }
        return false;
    }

    public function getAnalisiCRUndici($triennioPeriod, $trimestrePeriod, $lastYearPeriod, $latestYear, $latestMonth, $categories)
    {
        $accordatoRevocaTrimestre = $this->getMediaAccordatoOperativoGeneral($trimestrePeriod, $categories, $this->getMonthsCountFromPeriod($trimestrePeriod));
        $accordatoRevocaTriennio = ($this->getMediaAccordatoOperativoGeneral($triennioPeriod, $categories, 36)) * 12;

        $accordatoRevocaAnno = $this->getMediaAccordatoOperativoGeneral($lastYearPeriod, $categories, 1);
        $accordatoRevocaUltimoMese = $this->getMediaAccordatoOperativoGeneral(array($latestYear => array($latestMonth => null)), $categories, 1);

        if ($accordatoRevocaTriennio != 0) {
            if (- (1 - ($accordatoRevocaAnno / $accordatoRevocaTriennio)) > 0.05) {
                return true;
            }
        }

        if ($accordatoRevocaTrimestre != 0) {
            if (- (1 - ($accordatoRevocaUltimoMese / $accordatoRevocaTrimestre)) > 0.05) {
                return true;
            }
        }

        return false;
    }

    public function getAnalisiCRDodici($triennioPeriod, $trimestrePeriod, $lastYearPeriod, $latestYear, $latestMonth, $categories, $banks)
    {
        $accordatoRevocaTrimestre = $this->getMediaAccordatoOperativo($trimestrePeriod, $categories);
        $accordatoRevocaTriennio = $this->getMediaAccordatoOperativo($triennioPeriod, $categories);

        $accordatoRevocaAnno = array();
        $accordatoRevocaUltimoMese = array();

        foreach ($lastYearPeriod as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $patata) {

                $totaleAffidamenti = $this->crExtractorHelper->getTotaleAffidamenti($categories, $singleYear, $singleMonth, $banks);

                for ($i = 0; $i < count($totaleAffidamenti); $i++) {
                    $accordatoRevocaAnno[$totaleAffidamenti[$i]['nome_banca']] = isset($accordatoRevocaAnno[$totaleAffidamenti[$i]['nome_banca']]) ? ($accordatoRevocaAnno[$totaleAffidamenti[$i]['nome_banca']]) + $totaleAffidamenti[$i]['totAccordatoOperativo'] : $totaleAffidamenti[$i]['totAccordatoOperativo'];
                }
            }
        }
        foreach (array($latestYear => array($latestMonth => null)) as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $patata) {

                $totaleAffidamenti = $this->crExtractorHelper->getTotaleAffidamenti($categories, $singleYear, $singleMonth, $banks);

                for ($i = 0; $i < count($totaleAffidamenti); $i++) {
                    $accordatoRevocaUltimoMese[$totaleAffidamenti[$i]['nome_banca']] = isset($accordatoRevocaUltimoMese[$totaleAffidamenti[$i]['nome_banca']]) ? ($accordatoRevocaUltimoMese[$totaleAffidamenti[$i]['nome_banca']]) + $totaleAffidamenti[$i]['totAccordatoOperativo'] : $totaleAffidamenti[$i]['totAccordatoOperativo'];
                }
            }
        }
        foreach ($accordatoRevocaAnno as $singleBank => $revocaData) {
            if (isset($accordatoRevocaTriennio[$singleBank])) {
                if (($accordatoRevocaTriennio[$singleBank] - $revocaData) > (($accordatoRevocaTriennio[$singleBank]) / 25) * 100) {
                    return true;
                }
            }
        }
        foreach ($accordatoRevocaUltimoMese as $singleBank => $revocaData) {
            if (isset($accordatoRevocaTrimestre[$singleBank])) {
                if (($accordatoRevocaTrimestre[$singleBank] - $revocaData) > (($accordatoRevocaTrimestre[$singleBank]) / 25) * 100) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAnalisiCRTredici($triennioPeriod, $trimestrePeriod, $lastYearPeriod, $latestYear, $latestMonth, $categories, $banks)
    {
        $accordatoRevocaTrimestre = $this->getMediaAccordatoOperativo($trimestrePeriod, $categories);
        $accordatoRevocaTriennio = $this->getMediaAccordatoOperativo($triennioPeriod, $categories);

        $accordatoRevocaAnno = array();
        $accordatoRevocaUltimoMese = array();

        foreach ($lastYearPeriod as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $patata) {

                $totaleAffidamenti = $this->crExtractorHelper->getTotaleAffidamenti($categories, $singleYear, $singleMonth, $banks);

                for ($i = 0; $i < count($totaleAffidamenti); $i++) {
                    $accordatoRevocaAnno[$totaleAffidamenti[$i]['nome_banca']] = isset($accordatoRevocaAnno[$totaleAffidamenti[$i]['nome_banca']]) ? ($accordatoRevocaAnno[$totaleAffidamenti[$i]['nome_banca']]) + $totaleAffidamenti[$i]['totAccordatoOperativo'] : $totaleAffidamenti[$i]['totAccordatoOperativo'];
                }
            }
        }
        foreach (array($latestYear => array($latestMonth => null)) as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $patata) {

                $totaleAffidamenti = $this->getTotaleAffidamentiPerFirma($categories, $singleYear, $singleMonth);

                for ($i = 0; $i < count($totaleAffidamenti); $i++) {
                    $accordatoRevocaUltimoMese[$totaleAffidamenti[$i]['nome_banca']] = isset($accordatoRevocaUltimoMese[$totaleAffidamenti[$i]['nome_banca']]) ? ($accordatoRevocaUltimoMese[$totaleAffidamenti[$i]['nome_banca']]) + $totaleAffidamenti[$i]['totAccordatoOperativo'] : $totaleAffidamenti[$i]['totAccordatoOperativo'];
                }
            }
        }
        foreach ($accordatoRevocaAnno as $singleBank => $revocaData) {
            if (isset($accordatoRevocaTriennio[$singleBank])) {
                if (($accordatoRevocaTriennio[$singleBank] - $revocaData) > (($accordatoRevocaTriennio[$singleBank]) / 25) * 100) {
                    return true;
                }
            }
        }
        foreach ($accordatoRevocaUltimoMese as $singleBank => $revocaData) {
            if (isset($accordatoRevocaTrimestre[$singleBank])) {
                if (($accordatoRevocaTrimestre[$singleBank] - $revocaData) > (($accordatoRevocaTrimestre[$singleBank]) / 25) * 100) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAnalisiCRQuattordici($banks)
    {
        return (count($this->crExtractorHelper->getTotaleSconfini($banks)['SconfiniOltre90Giorni']) > 0 || count($this->crExtractorHelper->getTotaleSconfini($banks)['SconfiniOltre180Giorni']) > 0);
    }
    public function getAnalisiCRQuindici($banks)
    {
        return ($this->crExtractorHelper->getGaranzieEsitoNegativo($banks) > 0);
    }
    public function getAnalisiCRSedici($banks)
    {
        return (count($this->crExtractorHelper->getSofferenze($banks)) > 0 || count($this->crExtractorHelper->getCreditiPassatiPerdita($banks)) > 0);
    }

    public function getQuestionarioAsis($bilancioId, $documentId)
    {
        $getQuestionarioAsis = DB::table('questionario')->where('document_id', $documentId)->where('bilancio_id', $bilancioId)->get();

        return $getQuestionarioAsis;
    }

    public function getQuestionarioToBe($bilancioId, $documentId)
    {
        $getQuestionarioToBe = DB::table('forwardlooking')->where('document_id', $documentId)->where('bilancio_id', $bilancioId)->get();

        return $getQuestionarioToBe;
    }

    public function getScores($punteggioCR, $bilancioData, $scoreASIS, $ASISfinalScore, $scoreFL)
    {

        if ($punteggioCR >= 0 && $punteggioCR < 0.14) {
            $resultCentraleRischi = "Default";
        } else if ($punteggioCR >= 0.14 && $punteggioCR < 0.28) {
            $resultCentraleRischi = "Situazione Grave";
        } else if ($punteggioCR >= 0.28 && $punteggioCR < 0.42) {
            $resultCentraleRischi = "Alert";
        } else if ($punteggioCR >= 0.42 && $punteggioCR < 0.56) {
            $resultCentraleRischi = "Rischio alert";
        } else if ($punteggioCR >= 0.56 && $punteggioCR < 0.70) {
            $resultCentraleRischi = "Fragilità elevata";
        } else if ($punteggioCR >= 0.70 && $punteggioCR < 0.85) {
            $resultCentraleRischi = "Fragilità";
        } else if ($punteggioCR >= 0.85 && $punteggioCR <= 1) {
            $resultCentraleRischi = "Solidità";
        }

        $resultAnalisiBilancio = "N/A";

        if ($bilancioData['Giudizi']['Score'] >= 0 && $bilancioData['Giudizi']['Score'] < 0.14) {
            $resultAnalisiBilancio = "Default";
        } else if ($bilancioData['Giudizi']['Score'] >= 0.14 && $bilancioData['Giudizi']['Score'] < 0.28) {
            $resultAnalisiBilancio = "Situazione Grave";
        } else if ($bilancioData['Giudizi']['Score'] >= 0.28 && $bilancioData['Giudizi']['Score'] < 0.42) {
            $resultAnalisiBilancio = "Alert";
        } else if ($bilancioData['Giudizi']['Score'] >= 0.42 && $bilancioData['Giudizi']['Score'] < 0.56) {
            echo "Rischio alert";
        } else if ($bilancioData['Giudizi']['Score'] >= 0.56 && $bilancioData['Giudizi']['Score'] < 0.70) {
            $resultAnalisiBilancio = "Fragilità elevata";
        } else if ($bilancioData['Giudizi']['Score'] >= 0.70 && $bilancioData['Giudizi']['Score'] < 0.85) {
            $resultAnalisiBilancio = "Fragilità";
        } else if ($bilancioData['Giudizi']['Score'] >= 0.85 && $bilancioData['Giudizi']['Score'] <= 1) {
            $resultAnalisiBilancio = "Solidità";
        }

        if ($scoreASIS['1'] >= 0 && $scoreASIS['1'] < 0.14) {
            $resultMinacceRapportiCommerciali = "Default";
        } else if ($scoreASIS['1'] >= 0.14 && $scoreASIS['1'] < 0.28) {
            $resultMinacceRapportiCommerciali = "Situazione Grave";
        } else if ($scoreASIS['1'] >= 0.28 && $scoreASIS['1'] < 0.42) {
            $resultMinacceRapportiCommerciali = "Alert";
        } else if ($scoreASIS['1'] >= 0.42 && $scoreASIS['1'] < 0.56) {
            $resultMinacceRapportiCommerciali = "Rischio alert";
        } else if ($scoreASIS['1'] >= 0.56 && $scoreASIS['1'] < 0.70) {
            $resultMinacceRapportiCommerciali = "Fragilità elevata";
        } else if ($scoreASIS['1'] >= 0.70 && $scoreASIS['1'] < 0.85) {
            $resultMinacceRapportiCommerciali = "Fragilità";
        } else if ($scoreASIS['1'] >= 0.85 && $scoreASIS['1'] <= 1) {
            $resultMinacceRapportiCommerciali = "Solidità";
        } else {
            $resultMinacceRapportiCommerciali = "N/A";
        }


        if ($scoreASIS['2'] >= 0 && $scoreASIS['2'] < 0.14) {
            $resultMinacceGestioneAziendale = "Default";
        } else if ($scoreASIS['2'] >= 0.14 && $scoreASIS['2'] < 0.28) {
            $resultMinacceGestioneAziendale = "Situazione Grave";
        } else if ($scoreASIS['2'] >= 0.28 && $scoreASIS['2'] < 0.42) {
            $resultMinacceGestioneAziendale = "Alert";
        } else if ($scoreASIS['2'] >= 0.42 && $scoreASIS['2'] < 0.56) {
            $resultMinacceGestioneAziendale = "Rischio alert";
        } else if ($scoreASIS['2'] >= 0.56 && $scoreASIS['2'] < 0.70) {
            $resultMinacceGestioneAziendale = "Fragilità elevata";
        } else if ($scoreASIS['2'] >= 0.70 && $scoreASIS['2'] < 0.85) {
            $resultMinacceGestioneAziendale = "Fragilità";
        } else if ($scoreASIS['2'] >= 0.85 && $scoreASIS['2'] <= 1) {
            $resultMinacceGestioneAziendale = "Solidità";
        } else {
            $resultMinacceGestioneAziendale = "N/A";
        }


        if ($scoreASIS['3'] >= 0 && $scoreASIS['3'] < 0.14) {
            $resultMinacceEventiPregiudizievoli = "Default";
        } else if ($scoreASIS['3'] >= 0.14 && $scoreASIS['3'] < 0.28) {
            $resultMinacceEventiPregiudizievoli = "Situazione Grave";
        } else if ($scoreASIS['3'] >= 0.28 && $scoreASIS['3'] < 0.42) {
            $resultMinacceEventiPregiudizievoli = "Alert";
        } else if ($scoreASIS['3'] >= 0.42 && $scoreASIS['3'] < 0.56) {
            $resultMinacceEventiPregiudizievoli = "Rischio alert";
        } else if ($scoreASIS['3'] >= 0.56 && $scoreASIS['3'] < 0.70) {
            $resultMinacceEventiPregiudizievoli = "Fragilità elevata";
        } else if ($scoreASIS['3'] >= 0.70 && $scoreASIS['3'] < 0.85) {
            $resultMinacceEventiPregiudizievoli = "Fragilità";
        } else if ($scoreASIS['3'] >= 0.85 && $scoreASIS['3'] <= 1) {
            $resultMinacceEventiPregiudizievoli = "Solidità";
        } else {
            $resultMinacceEventiPregiudizievoli = "N/A";
        }


        if ($scoreASIS['4'] >= 0 && $scoreASIS['4'] < 0.14) {
            $resultMinacceRischiCaratteristici = "Default";
        } else if ($scoreASIS['4'] >= 0.14 && $scoreASIS['4'] < 0.28) {
            $resultMinacceRischiCaratteristici = "Situazione Grave";
        } else if ($scoreASIS['4'] >= 0.28 && $scoreASIS['4'] < 0.42) {
            $resultMinacceRischiCaratteristici = "Alert";
        } else if ($scoreASIS['4'] >= 0.42 && $scoreASIS['4'] < 0.56) {
            $resultMinacceRischiCaratteristici = "Rischio alert";
        } else if ($scoreASIS['4'] >= 0.56 && $scoreASIS['4'] < 0.70) {
            $resultMinacceRischiCaratteristici = "Fragilità elevata";
        } else if ($scoreASIS['4'] >= 0.70 && $scoreASIS['4'] < 0.85) {
            $resultMinacceRischiCaratteristici = "Fragilità";
        } else if ($scoreASIS['4'] >= 0.85 && $scoreASIS['4'] <= 1) {
            $resultMinacceRischiCaratteristici = "Solidità";
        } else {
            $resultMinacceRischiCaratteristici = "N/A";
        }

        if ($ASISfinalScore) {
            $ASISScore = $ASISfinalScore['Giudizio'];
        } else {
            $ASISScore = 'N/A';
        }

        if ($scoreFL) {
            $scoreGiudizioFL = $scoreFL['Giudizio'];
        }

        $data = [
            'resultCentraleRischi' => $resultCentraleRischi,
            'resultAnalisiBilancio' => $resultAnalisiBilancio,
            'resultMinacceRapportiCommerciali' => $resultMinacceRapportiCommerciali,
            'resultMinacceGestioneAziendale' => $resultMinacceGestioneAziendale,
            'resultMinacceEventiPregiudizievoli' => $resultMinacceEventiPregiudizievoli,
            'resultMinacceRischiCaratteristici' => $resultMinacceRischiCaratteristici,
            'ASISScore' => $ASISScore,
            'scoreGiudizioFL' => $scoreGiudizioFL
        ];

        return $data;
    }

    public function getGeneralScore($bilancioData, $scoreCR, $scoreASIS, $scoreFL)
    {

        $ASISfinalScore = array("Score" => ($bilancioData['Giudizi']['Score'] * 0.25) + ($scoreCR * 0.25) + ($scoreASIS['1'] * 0.1) + ($scoreASIS['2'] * 0.1) + ($scoreASIS['3'] * 0.15) + ($scoreASIS['4'] * 0.15));

        $rangeGiudizi = array(
            0 => array("Min" => 0, "Max" => 0.14, "Giudizio" => "Default"),
            1 => array("Min" => 0.14, "Max" => 0.28, "Giudizio" => "Situazione Grave"),
            2 => array("Min" => 0.28, "Max" => 0.42, "Giudizio" => "Alert"),
            3 => array("Min" => 0.42, "Max" => 0.56, "Giudizio" => "Rischio alert"),
            4 => array("Min" => 0.56, "Max" => 0.70, "Giudizio" => "Fragilità elevata"),
            5 => array("Min" => 0.70, "Max" => 0.85, "Giudizio" => "Fragilità"),
            6 => array("Min" => 0.85, "Max" => 1, "Giudizio" => "Solidità")
        );

        foreach ($rangeGiudizi as $index => $ranges) {
            if ($ASISfinalScore["Score"] >= $ranges["Min"] && $ASISfinalScore["Score"] < $ranges["Max"]) {
                $ASISfinalScore["Giudizio"] = $ranges['Giudizio'];
                $ASISfinalScore["Index"] = $index;
            }
        }

        $generalScore = array();

        if ($scoreASIS['4'] < 0.75) {
            $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] - 1]['Giudizio'];
            $generalScore["Index"] = $ASISfinalScore["Index"] - 1;
        } else {
            $generalScore = $ASISfinalScore;
        }


        if ($scoreFL['Giudizio'] == 'Miglioramento') {
            if ($generalScore["Index"] != 6) {
                $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] + 1]['Giudizio'];
                $generalScore["Index"] = $generalScore["Index"] + 1;
            }
        } else if ($scoreFL['Giudizio'] == 'Peggioramento') {
            if ($generalScore["Index"] != 0) {
                $generalScore["Giudizio"] = $rangeGiudizi[$ASISfinalScore["Index"] - 1]['Giudizio'];
                $generalScore["Index"] = $generalScore["Index"] - 1;
            }
        }

        return $generalScore;
    }

    public function getDate($idCr)
    {
        $crs = cr::select('anno', 'mese', 'date')->where('document_id', $idCr)->distinct()->orderBy('date', 'asc')->get();

        for ($i = (count($crs) - 12 >= 0) ? count($crs) - 12 : 0; $i < count($crs); $i++) {
            $periods[$crs[$i]->anno][$crs[$i]->mese] = null;
        }

        $latestYear = array_key_last($periods);
        $latestMonth = array_key_last($periods[$latestYear]);

        $crData = array();

        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );

        $earliestYear = array_key_first($periods);
        $earliestMonth = array_key_first($periods[$earliestYear]);

        $upperBoundDate = new DateTime((cr::select('date')->where('anno', $earliestYear)->where('mese', $earliestMonth)->where('document_id', $idCr)->get()->first())->date);
        $lowerBoundDate = new DateTime($upperBoundDate->format('Y-m-d'));
        $lowerBoundDate = $lowerBoundDate->modify('-11 months');

        $data = [
            'crs' => $crs,
            'periods' => $periods,
            'latestYear' => $latestYear,
            'latestMonth' => $latestMonth,
            'crData' => $crData,
            'categories' => $categories,
            'earliestYear' => $earliestYear,
            'earliestMonth' => $earliestMonth,
            'upperBoundDate' => $upperBoundDate,
            'lowerBoundDate' => $lowerBoundDate
        ];

        return $data;
    }


    public function getArrayQuestionarioAsIs($id, $idCr)
    {
        $arrayQuestionario = array();
        $questionario = DB::table('questionario')->where('document_id', $idCr)->where('bilancio_id', $id)->get();
        foreach ($questionario as $item => $data) {
            $arrayQuestionario[$data->parameter]['Result'] = $data->result;
            $arrayQuestionario[$data->parameter]['Details'] = $data->details == null ? '' : $data->details;
        }

        return $arrayQuestionario;
    }

    public function getArrayQuestionarioToBe($id, $idCr)
    {
        $arrayForwardLooking = array();
        $forwardLooking = DB::table('forwardlooking')->get();
        foreach ($forwardLooking as $item => $data) {
            $arrayForwardLooking[$data->question] = $data->answer;
        }

        return $arrayForwardLooking;
    }

    public function valutazioneFL($arrayForwardLooking)  //api
    {
        $scoreFL = 0;
        $peso = 0.08333;
        $giudizio = '';

        foreach ($arrayForwardLooking as $label => $value) {
            switch ($value) {
                case 1:
                    $scoreFL += (0 * $peso);
                    break;
                case 2:
                    $scoreFL += (1 * $peso);
                    break;
                case 3:
                    $scoreFL += (-1 * $peso);
                    break;
            }
        }

        if ($scoreFL >= -1 && $scoreFL <= -0.17) {
            $giudizio = 'Peggioramento';
        } else if ($scoreFL >= -0.016 && $scoreFL <= 0.32) {
            $giudizio = 'Stabilità';
        } else if ($scoreFL >= 0.33 && $scoreFL <= 1) {
            $giudizio = 'Miglioramento';
        }

        return array("Valore" => $scoreFL, "Giudizio" => $giudizio);
    }


    public function valutazioneQuestionarioQualitativo($arrayQuestionario)
    {
        $pesi = $this->getPesiASIS();
        $scoreASIS = [];

        foreach ($arrayQuestionario as $label => $result) {
            $explodedLabel = explode('-', $label)[0];
            $value = $pesi[$label]['peso'] * $pesi[$label]['score'][$result['Result']];
            $scoreASIS[$explodedLabel] = $value;
        }

        return $scoreASIS;
    }

    public function getPesiASIS()
    {
        return array(
            "1-1" => array("peso" => 0.09, "score" => array("Si" => -0.5, "No" => 1)),
            "1-2" => array("peso" => 0.15, "score" => array("Si" => -0.5, "No" => 1)),
            "1-3" => array("peso" => 0.15, "score" => array("Si" => -0.5, "No" => 1)),
            "1-4" => array("peso" => 0.15, "score" => array("Si" => -0.5, "No" => 1)),
            "1-5" => array("peso" => 0.15, "score" => array("Si" => -0.5, "No" => 1)),
            "1-6" => array("peso" => 0.11, "score" => array("Si" => -0.5, "No" => 1)),
            "1-7" => array("peso" => 0.11, "score" => array("Si" => -0.5, "No" => 1)),
            "1-8" => array("peso" => 0.09, "score" => array("Si" => -0.5, "No" => 1)),

            "2-1" => array("peso" => 0.11, "score" => array("Si" => -0.7, "No" => 1)),
            "2-2" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),
            "2-3" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),
            "2-4" => array("peso" => 0.11, "score" => array("Si" => -1, "No" => 1)),
            "2-5" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),
            "2-6" => array("peso" => 0.11, "score" => array("Si" => -0.7, "No" => 1)),
            "2-7" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),
            "2-8" => array("peso" => 0.0, "score" => array("Si" => -0.5, "No" => 1)),
            "2-9" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),
            "2-10" => array("peso" => 0.1, "score" => array("Si" => -0.5, "No" => 1)),

            "3-1" => array("peso" => 0.3, "score" => array("Si" => -0.7, "No" => 1)),
            "3-2" => array("peso" => 0.3, "score" => array("Si" => -0.5, "No" => 1)),
            "3-3" => array("peso" => 0.2, "score" => array("Si" => -0.5, "No" => 1)),
            "3-4" => array("peso" => 0.2, "score" => array("Si" => -0.5, "No" => 1)),

            "4-1" => array("peso" => 0.166, "score" => array("Si" => -0.5, "No" => 1)),
            "4-2" => array("peso" => 0.166, "score" => array("Si" => -0.5, "No" => 1)),
            "4-3" => array("peso" => 0.156, "score" => array("Si" => -0.5, "No" => 1)),
            "4-4" => array("peso" => 0.166, "score" => array("Si" => -0.5, "No" => 1)),
            "4-5" => array("peso" => 0.18, "score" => array("Si" => -1, "No" => 1)),
            "4-6" => array("peso" => 0.166, "score" => array("Si" => -0.5, "No" => 1))
        );
    }
}
