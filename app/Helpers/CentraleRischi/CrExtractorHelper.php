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

class CrExtractorHelper
{
    public $_period = false;
    public $_documentId = false;
    public $_countMonths = false;
    public $_countBanks = false;
    public $_scaduti = false;
    public $_impagati = false;
    public $_rowSofferenze = false;
    public $_rowGaranzieRicevute = false;
    public $_tensioni = false;
    public $_pregiudizievoli = false;
    public $_contestazioni = false;
    public $_finalScore = false;
    public $_sconfiniOltre90 = false;
    public $_sconfiniOltre180 = false;
    public $_creditiPerdita = false;
    public $_sofferenze = false;
    public $_garanzieEsitoNegativo = false;
    public $numeroSconfiniTotali = 0;
    private $_currentRow = false;
    private $_previousRow = false;
    public $_alertImpagati = false;


    public function setPeriod($period)
    {
        $this->_period = $period;
    }

    public function getPeriod()
    {
        return $this->_period;
    }

    public function setDocumentId($documentId)
    {
        $this->_documentId = $documentId;
    }

    public function getDocumentId()
    {
        return $this->_documentId;
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

    public function addBankToCount()
    {
        if (!$this->_countMonths) {
            $this->setContBanks();
        }

        $this->_countBanks++;
    }

    public function setContBanks()
    {
        $this->_countBanks = 0;
    }

    public function getCountBanks($banks)
    {
        if ($this->_countBanks) {
            return $this->_countBanks;
        }

        $data = $this->getAllData($banks)->get()->groupBy(array('nome_banca'))->toArray();

        $this->_countBanks = count($data);

        return $this->_countBanks;
    }

    private function getDatabaseCleanKeys()
    {
        $dbkeys = array(
            'anno' => 'anno',
            'mese' => 'mese',
            'nome_banca' => 'nome_banca',
            'sezione' => 'sezione',
            'categoria' => 'categoria',
            'Localizzazione' => 'localizzazione',
            'Durata Originaria' => 'durata_originaria',
            'Durata Residua' => 'durata_residua',
            'Divisa' => 'divisa',
            'Import Export' => 'import_export',
            'Tipo Attività' => 'tipo_attivita',
            'Stato Rapporto' => 'stato_rapporto',
            'Tipo Garanzia' => 'tipo_garanzia',
            'Ruolo Affidato' => 'ruolo_affidato',
            'Accordato' => 'accordato',
            'Accordato Operativo' => 'accordato_operativo',
            'Utilizzato' => 'utilizzato',
            'Saldo Medio' => 'saldo_medio',
            'Importo Garantito' => 'importo_garantito',
            'Valore Garanzia' => 'garanzia',
            'Garantito' => 'garantito',
        );

        return $dbkeys;
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

    public function getAllData($banks)
    {
        $queryPeriodArray = array();
        $CentraleRischiModel = DB::table('crs');
        $periods = $this->buildPeriodArray();


        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
            });
        }

        $dbkeys = $this->getDatabaseCleanKeys();

        $selectString = array();
        foreach ($dbkeys as $labelCorrect => $dbone) {
            $selectString[] .= $dbone . ' as ' . $labelCorrect;
        }
        $CentraleRischiModel->select($selectString);
        $test = $CentraleRischiModel->orderBy('anno');


        return $test;
    }

    public function getAllDataToArray($banks)
    {
        $data = $this->getAllData($banks)->get()->groupBy(array('anno', 'mese', 'nome_banca', 'sezione', 'categoria'))->toArray();
        $data = json_decode(json_encode($data), true);


        return $data;
    }

    public function testSconfini($banks)
    {
        $periods = $this->buildPeriodArray();
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->whereRaw('CAST(accordato_operativo as SIGNED) < CAST(utilizzato as SIGNED)');
                $query->whereIn('categoria', $categories);
            });
        }
        $sconfiniTotali = $CentraleRischiModel->select(DB::raw('anno, mese, nome_banca, categoria, accordato_operativo, utilizzato'))
            ->orderBy('date')
            ->get()
            ->toArray();

        return $sconfiniTotali;
    }

    public function getTotaleSconfiniOld($banks)
    {
        $cleanData = array();
        $periods = $this->buildPeriodArray();
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->whereRaw('CAST(accordato_operativo as SIGNED) < CAST(utilizzato as SIGNED)');
                $query->whereIn('categoria', $categories);
            });
        }
        $sconfiniTotali = $CentraleRischiModel->select(DB::raw('categoria, count(*) as sconfiniTotali'))
            ->groupBy(array('categoria'))
            ->get()
            ->toArray();

        foreach ($sconfiniTotali as $label => $data) {
            $cleanData['Categorie'][$data->categoria] = $data->sconfiniTotali;
        }

        $cleanData['SconfiniTotali'] = $sconfiniTotali;


        // Sconfini entro i 90 giorni

        $CentraleRischiModel = DB::table('crs as t');
        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where($queryPeriodArray)
                    ->whereIn('nome_banca', $banks)
                    ->whereIn('categoria', $categories)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date - interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 3 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa')
                            ->whereExists(function ($query) {
                                $query->select('*')
                                    ->from('crs as t3')
                                    ->whereRaw('t3.date = t2.date - interval 1 month AND CAST(t3.accordato_operativo as SIGNED) < CAST(t3.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
	AND t2.categoria = t3.categoria
	AND t2.mese != t3.mese
    AND t2.nome_banca = t3.nome_banca
	AND t2.localizzazione = t3.localizzazione
	AND t2.divisa = t3.divisa');
                            })->whereExists(function ($query) {
                                $query->select('*')
                                    ->from('crs as t3')
                                    ->whereRaw('t3.date = t2.date - interval 2 month AND CAST(t3.accordato_operativo as SIGNED) < CAST(t3.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
	AND t2.categoria = t3.categoria
	AND t2.mese != t3.mese
    AND t2.nome_banca = t3.nome_banca
	AND t2.localizzazione = t3.localizzazione
	AND t2.divisa = t3.divisa');
                            });
                    });
            });
        }
        $sconfiniEntro90Giorni = $CentraleRischiModel
            ->get()
            ->sortBy('date')
            ->toArray();
        $cleanData['SconfiniEntro90Giorni'] = $sconfiniEntro90Giorni;


        // Sconfini oltre i 90 giorni ed entro i 180 giorni
        $CentraleRischiModel = DB::table('crs as t');
        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where($queryPeriodArray)
                    ->whereIn('categoria', $categories)
                    ->whereIn('nome_banca', $banks)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date - interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 2 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 3 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 5 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    });
            });
        }
        $sconfiniOltre90Giorni = $CentraleRischiModel
            ->get()
            ->sortBy('date')
            ->toArray();
        $cleanData['SconfiniOltre90Giorni'] = $sconfiniOltre90Giorni;
        if (count($sconfiniOltre90Giorni) > 0) {
            $this->sconfiniOltre90 = true;
        }

        // Sconfini oltre i 180 giorni
        $CentraleRischiModel = DB::table('crs as t');
        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where($queryPeriodArray)
                    ->whereIn('categoria', $categories)
                    ->whereIn('nome_banca', $banks)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    /* non contiamo il mese precedente perchè potrebbe esserre uno sconfino lunghissimo */
                    ->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date - interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 2 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 3 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 4 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 5 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.nome_banca = t2.nome_banca
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    });
            });
        }
        $sconfiniOltre180Giorni = $CentraleRischiModel
            ->get()
            ->sortBy('date')
            ->toArray();
        $cleanData['SconfiniOltre180Giorni'] = $sconfiniOltre180Giorni;
        if (count($sconfiniOltre180Giorni) > 0) {
            $this->_sconfiniOltre180 = true;
        }


        $cleanData['PresenzaSconfini'] = false;

        if ($cleanData['SconfiniTotali']) {
            $cleanData['PresenzaSconfini'] = true;
        }

        $this->numeroSconfiniTotali = 0;
        $countBanks = $this->getCountBanks($banks);
        $allMonthsCount = $this->getCountMonths();
        $this->_tensioni['RISCHI AUTOLIQUDIANTI'] = false;
        $this->_tensioni['RISCHI A SCADENZA'] = false;
        $this->_tensioni['RISCHI A REVOCA'] = false;

        foreach ($cleanData['SconfiniTotali'] as $singleSconfino) {
            $this->numeroSconfiniTotali += $singleSconfino->sconfiniTotali;
            if ($countBanks == 1) {
                if ($allMonthsCount <= 12) {
                    if ($singleSconfino->sconfiniTotali >= 2) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 12) {
                    if ($singleSconfino->sconfiniTotali >= 5) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 24) {
                    if ($singleSconfino->sconfiniTotali >= 8) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                }
            } else if ($countBanks > 1) {
                if ($allMonthsCount <= 12) {
                    if ($singleSconfino->sconfiniTotali >= 3) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 12) {
                    if ($singleSconfino->sconfiniTotali >= 6) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 24) {
                    if ($singleSconfino->sconfiniTotali >= 12) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                }
            }
        }

        if (isset($this->_tensioni) && is_array($this->_tensioni)) {
            $cleanData['Tensioni'] = $this->_tensioni;
        }

        $cleanData['SconfiniTotali'] = $this->numeroSconfiniTotali;

        return $cleanData;
    }

    public function getTotaleSconfini($banks)
    {
        $cleanData = array();
        $periods = $this->buildPeriodArray();
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );

        $CentraleRischiModel = DB::table('crs');


        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where($queryPeriodArray);
                $query->whereIn('nome_banca', $banks);
                $query->where('document_id', $this->_documentId);
                $query->whereRaw('CAST(accordato_operativo as SIGNED) < CAST(utilizzato as SIGNED)');
                $query->whereIn('categoria', $categories);
            });
        }
        $sconfiniTotali = $CentraleRischiModel->select(DB::raw('categoria, count(*) as sconfiniTotali'))
            ->groupBy(array('categoria'))
            ->get()
            ->toArray();

        foreach ($sconfiniTotali as $label => $data) {
            $cleanData['Categorie'][$data->categoria] = $data->sconfiniTotali;
        }

        $cleanData['SconfiniTotali'] = $sconfiniTotali;


        // Sconfini entro i 90 giorni

        $CentraleRischiModel = DB::table('crs as t');
        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where($queryPeriodArray)
                    ->whereIn('nome_banca', $banks)
                    ->where('t.document_id', $this->_documentId)
                    ->whereIn('categoria', $categories)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->where('stato_rapporto', 'not like', "Rapporti non contestati-crediti" . '%')
                    ->where('stato_rapporto', 'not like', "Rapp non contestati - cred scad" . '%');
            });
        }

        $sconfiniEntro90Giorni = $CentraleRischiModel
            ->get()
            ->sortBy('date')
            ->toArray();
        $cleanData['SconfiniEntro90Giorni'] = $sconfiniEntro90Giorni;


        // Sconfini oltre i 90 giorni ed entro i 180 giorni
        $CentraleRischiModel = DB::table('crs as t');
        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where($queryPeriodArray)
                    ->whereIn('nome_banca', $banks)
                    ->where('t.document_id', $this->_documentId)
                    ->whereIn('categoria', $categories)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->where('stato_rapporto', 'like', "Rapp non contestati - cred scad" . '%');
            });
        }
        $sconfiniOltre90Giorni = $CentraleRischiModel
            ->get()
            ->sortBy('date')
            ->toArray();
        $cleanData['SconfiniOltre90Giorni'] = $sconfiniOltre90Giorni;
        if (count($sconfiniOltre90Giorni) > 0) {
            $this->sconfiniOltre90 = true;
        }

        // Sconfini oltre i 180 giorni
        $CentraleRischiModel = DB::table('crs as t');
        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where('t.document_id', $this->_documentId)
                    ->where($queryPeriodArray)
                    ->whereIn('nome_banca', $banks)
                    ->whereIn('categoria', $categories)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->Where('stato_rapporto', 'like', "Rapporti non contestati-crediti" . "%");
            });
        }
        $sconfiniOltre180Giorni = $CentraleRischiModel
            ->get()
            ->sortBy('date')
            ->toArray();
        $cleanData['SconfiniOltre180Giorni'] = $sconfiniOltre180Giorni;
        if (count($sconfiniOltre180Giorni) > 0) {
            $this->_sconfiniOltre180 = true;
        }


        $cleanData['PresenzaSconfini'] = false;

        if ($cleanData['SconfiniTotali']) {
            $cleanData['PresenzaSconfini'] = true;
        }

        $this->numeroSconfiniTotali = 0;

        $countSconfiniPerCategoria = array(
            'RISCHI A SCADENZA' => 0,
            'RISCHI AUTOLIQUIDANTI'  => 0,
            'RISCHI A REVOCA'  => 0,
        );


        $countBanks = $this->getCountBanks($banks);
        $allMonthsCount = $this->getCountMonths();
        $this->_tensioni["RISCHI A SCADENZA"] = false;
        $this->_tensioni["RISCHI A REVOCA"] = false;
        $this->_tensioni["RISCHI AUTOLIQUIDANTI"] = false;

        foreach ($cleanData['SconfiniTotali'] as $singleSconfino) {
            $this->numeroSconfiniTotali += $singleSconfino->sconfiniTotali;


            $countSconfiniPerCategoria[$singleSconfino->categoria] = $cleanData['Categorie'][$singleSconfino->categoria];


            if ($countBanks == 1) {
                if ($allMonthsCount <= 12) {
                    if ($singleSconfino->sconfiniTotali >= 2) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 12) {
                    if ($singleSconfino->sconfiniTotali >= 5) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 24) {
                    if ($singleSconfino->sconfiniTotali >= 8) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                }
            } else if ($countBanks > 1) {
                if ($allMonthsCount <= 12) {
                    if ($singleSconfino->sconfiniTotali >= 3) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 12) {
                    if ($singleSconfino->sconfiniTotali >= 6) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 24) {
                    if ($singleSconfino->sconfiniTotali >= 12) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                }
            }
        }

        if (isset($this->_tensioni) && is_array($this->_tensioni)) {
            $cleanData['Tensioni'] = $this->_tensioni;
        }

        $cleanData['SconfiniTotali'] = $this->numeroSconfiniTotali;
        $cleanData['CountSconfiniPerCategoria'] = $countSconfiniPerCategoria;

        //dd($cleanData);
        return $cleanData;
    }


    public function getAlertImpagati($banks)
    {
        $totaleImpagati = count($this->getImpagati($banks));
        $allMonthsCount = $this->getCountMonths();
        $countBanks = $this->getCountBanks($banks);

        if ($countBanks == 1) {
            if ($allMonthsCount <= 12) {
                if ($totaleImpagati >= 2) {
                    $this->_alertImpagati = true;
                }
            } else if ($allMonthsCount > 12 && $allMonthsCount <= 24) {
                if ($totaleImpagati >= 4) {
                    $this->_alertImpagati = true;
                }
            } else if ($allMonthsCount > 24) {
                if ($totaleImpagati >= 8) {
                    $this->_alertImpagati = true;
                }
            }
        } else if ($countBanks > 1) {
            if ($allMonthsCount <= 12) {
                if ($totaleImpagati >= 4) {
                    $this->_alertImpagati = true;
                }
            } else if ($allMonthsCount > 12 && $allMonthsCount <= 24) {
                if ($totaleImpagati >= 8) {
                    $this->_alertImpagati = true;
                }
            } else if ($allMonthsCount > 24) {
                if ($totaleImpagati >= 12) {
                    $this->_alertImpagati = true;
                }
            }
        }

        return $this->_alertImpagati;
    }

    public function getScoring($banks, $countBanks, $sconfini, $sofferenze, $creditiPerdita)
    {
        $allMonthsCount = $this->getCountMonths();
        //  $countBanks = $this->getCountBanks($banks);
        // $sconfiniOld = $this->getTotaleSconfiniOld($banks);
        //$sconfini = $this->getTotaleSconfini($banks);

        $numeroSconfiniOltre90 = count($sconfini['SconfiniOltre90Giorni']);
        $numeroSconfiniOltre180 = count($sconfini['SconfiniOltre180Giorni']);

        // Tensioni
        if (!isset($this->_tensioni['RISCHI A SCADENZA'])) {
            $this->_finalScore += 0.055;
        }
        if (!isset($this->_tensioni['RISCHI A REVOCA'])) {
            $this->_finalScore += 0.055;
        }
        if (!isset($this->_tensioni['RISCHI AUTOLIQUDIANTI'])) {
            $this->_finalScore += 0.055;
        }


        // Sconfini
        if ($countBanks == 1) {
            if ($allMonthsCount <= 12) {
                if (!($this->numeroSconfiniTotali >= 1)) {
                    $this->_finalScore += 0.1;
                }
            } else if ($allMonthsCount > 12 && $allMonthsCount <= 24) {
                if (!($this->numeroSconfiniTotali >= 2)) {
                    $this->_finalScore += 0.1;
                }
            } else if ($allMonthsCount > 24) {
                if (!($this->numeroSconfiniTotali >= 4)) {
                    $this->_finalScore += 0.1;
                }
            }
        } else if ($countBanks > 1) {
            if ($allMonthsCount <= 12) {
                if (!($this->numeroSconfiniTotali >= 2)) {
                    $this->_finalScore += 0.1;
                }
            } else if ($allMonthsCount > 12 && $allMonthsCount <= 24) {
                if (!($this->numeroSconfiniTotali >= 4)) {
                    $this->_finalScore += 0.1;
                }
            } else if ($allMonthsCount > 24) {
                if (!($this->numeroSconfiniTotali >= 8)) {
                    $this->_finalScore += 0.1;
                }
            }
        }


        // Impagati

        $alertImpagati = $this->getAlertImpagati($banks);

        if (!$alertImpagati) {
            $this->_finalScore += 0.1;
        }


        // Oltre i 90 e oltre i 180
        if ($allMonthsCount <= 12) {
            if (!$numeroSconfiniOltre90 >= 2) {
                $this->_finalScore += 0.11;
            }
            if (!$numeroSconfiniOltre180 >= 1) {
                $this->_finalScore += 0.12;
            }
        } else if ($allMonthsCount > 12) {
            if (!$numeroSconfiniOltre90 >= 4) {
                $this->_finalScore += 0.11;
            }
            if (!$numeroSconfiniOltre180 >= 2) {
                $this->_finalScore += 0.12;
            }
        } else if ($allMonthsCount > 24) {
            if (!$numeroSconfiniOltre90 >= 6) {
                $this->_finalScore += 0.11;
            }
            if (!$numeroSconfiniOltre180 >= 3) {
                $this->_finalScore += 0.12;
            }
        }

        // Presenza Sofferenze
        $sofferenze = count($sofferenze);

        if ($allMonthsCount <= 12) {
            if ($sofferenze < 1) {
                $this->_finalScore += 0.135;
            }
        } else if ($allMonthsCount > 12 && $allMonthsCount <= 24) {
            if ($sofferenze < 2) {
                $this->_finalScore += 0.135;
            }
        } else if ($allMonthsCount > 24) {
            if ($sofferenze < 3) {
                $this->_finalScore += 0.135;
            }
        }


        // Presenza Crediti passati a perdite
        $creditiPerdita = count($creditiPerdita);
        if ($allMonthsCount <= 12) {
            if ($creditiPerdita < 1) {
                $this->_finalScore += 0.135;
            }
        } else if ($allMonthsCount > 12 && $allMonthsCount <= 24) {
            if ($creditiPerdita < 2) {
                $this->_finalScore += 0.135;
            }
        } else if ($allMonthsCount > 24) {
            if ($creditiPerdita < 3) {
                $this->_finalScore += 0.135;
            }
        }

        // Presenza Garanzie attivate con esito negativo
        $garanzieEsitoNegativo = $this->getGaranzieEsitoNegativo($banks);

        if ($allMonthsCount <= 12) {
            if ($garanzieEsitoNegativo < 1) {
                $this->_finalScore += 0.135;
            }
        } else if ($allMonthsCount > 12 && $allMonthsCount <= 24) {
            if ($garanzieEsitoNegativo < 2) {
                $this->_finalScore += 0.135;
            }
        } else if ($allMonthsCount > 24) {
            if ($garanzieEsitoNegativo < 3) {
                $this->_finalScore += 0.135;
            }
        }

        return $this->_finalScore;
    }

    public function getImpagati($banks)
    {
        $periods = $this->buildPeriodArray();
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
            'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI',
        );

        $CentraleRischiModel = DB::table('crs');

        $documentId = $this->_documentId;

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks, $documentId) {
                $query->where($queryPeriodArray);
                $query->whereIn('nome_banca', $banks);
                $query->where('stato_rapporto', 'Crediti impagati');
                $query->whereIn('categoria', $categories);
                $query->where('document_id', $documentId);
            });
        }
        $impagati = $CentraleRischiModel
            ->get()
            ->toArray();

        $this->_impagati = $impagati;

        return $this->_impagati;
    }

    public function getSofferenze($banks)
    {
        $periods = $this->buildPeriodArray();

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->where('categoria', 'SOFFERENZE');
            });
        }
        $sofferenze = $CentraleRischiModel
            ->get()
            ->sortBy('date')
            ->toArray();

        $this->_sofferenze = $sofferenze;

        return $this->_sofferenze;
    }


    public function getCreditiPassatiPerdita($banks)
    {
        $periods = $this->buildPeriodArray();

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->where('categoria', 'SOFFERENZE - CREDITI PASSATI A PERDITA');
            });
        }
        $passatiPerdita = $CentraleRischiModel
            ->get()
            ->sortBy('date')
            ->toArray();

        $this->_creditiPerdita = $passatiPerdita;

        return $this->_creditiPerdita;
    }


    public function getGaranzieEsitoNegativo($banks)
    {
        $periods = $this->buildPeriodArray();

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->where('stato_rapporto', 'like', 'esito negativo');
            });
        }
        $garanzieEsitoNegativo = $CentraleRischiModel
            ->get()
            ->sortBy('date')
            ->toArray();

        $this->_garanzieEsitoNegativo = count($garanzieEsitoNegativo);

        return $this->_garanzieEsitoNegativo;
    }

    public function getMediaIndebitamento($banks)
    {
        $allMonthsCount = $this->getCountMonths();
        $mediaAnalisiIndebitamento = array();
        $periods = $this->buildPeriodArray();
        $sections = array(
            'Cassa',
            'Firma',
        );
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $sections, $categories, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->whereIn('sezione', $sections);
                $query->whereIn('categoria', $categories);
            });
        }
        $mediaIndebitamento = $CentraleRischiModel->select('categoria', 'accordato_operativo', 'utilizzato')->get()
            ->groupBy(array('categoria'))
            ->toArray();

        foreach ($mediaIndebitamento as $singleLine => $data) {
            if (!isset($mediaAnalisiIndebitamento[$singleLine]['Accordato Operativo'])) {
                $mediaAnalisiIndebitamento[$singleLine]['Accordato Operativo'] = 0;
            }
            if (!isset($mediaAnalisiIndebitamento[$singleLine]['Utilizzato'])) {
                $mediaAnalisiIndebitamento[$singleLine]['Utilizzato'] = 0;
            }
            if (!isset($mediaAnalisiIndebitamento[$singleLine]['Sconfinamenti'])) {
                $mediaAnalisiIndebitamento[$singleLine]['Sconfinamenti'] = 0;
            }
            foreach ($data as $singleRow) {
                if ($singleRow->utilizzato > $singleRow->accordato_operativo) {
                    $sconfinoImporto = $singleRow->utilizzato - $singleRow->accordato_operativo;
                    $mediaAnalisiIndebitamento[$singleLine]['Sconfinamenti'] += $sconfinoImporto;
                }
                if (!is_numeric((float)$singleRow->accordato_operativo)) {
                    dd($data);
                }

                $mediaAnalisiIndebitamento[$singleLine]['Accordato Operativo'] += (float)$singleRow->accordato_operativo;
                $mediaAnalisiIndebitamento[$singleLine]['Utilizzato'] += (float)$singleRow->utilizzato;
            }
        }

        foreach ($mediaAnalisiIndebitamento as $singleLine => $data) {
            $mediaAnalisiIndebitamento[$singleLine]['Sconfinamenti'] = $data['Sconfinamenti'] / $allMonthsCount;
            $mediaAnalisiIndebitamento[$singleLine]['Accordato Operativo'] = $data['Accordato Operativo'] / $allMonthsCount;
            $mediaAnalisiIndebitamento[$singleLine]['Utilizzato'] = $data['Utilizzato'] / $allMonthsCount;
        }

        return $mediaAnalisiIndebitamento;
    }


    public function getImportiSconfini($banks)
    {
        $periods = $this->buildPeriodArray();
        $importiSconfini = array();
        $sections = array('Cassa');
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $sections, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->whereRaw('CAST(accordato_operativo as SIGNED) < CAST(utilizzato as SIGNED)');
                $query->whereIn('categoria', $categories);
                $query->whereIn('sezione', $sections);
            });
        }

        $sconfiniTotali = $CentraleRischiModel->select('accordato_operativo', 'utilizzato', 'nome_banca', 'anno', 'mese', 'categoria', 'tipo_attivita', 'stato_rapporto')
            ->get()
            ->toArray();

        foreach ($sconfiniTotali as $label => $item) {

            $probErrataSegnalazione = '';

            // dd($item, ((float)(float)$item->utilizzato / (float)(float)$item->accordato_operativo));

            if ((float)$item->accordato_operativo == 0) {
                $probErrataSegnalazione = 'Accordato Nullo';
            } else if ($item->categoria == 'RISCHI A SCADENZA') {
                if (((float)(float)$item->utilizzato / (float)(float)$item->accordato_operativo) < 1) {
                    $probErrataSegnalazione = 'Utilizzo anomalo, probabile errata segnalazione';
                }
            } else if ($item->categoria == 'RISCHI A SCADENZA') {
                if (((float)(float)$item->utilizzato / (float)(float)$item->accordato_operativo) > 1) {
                    $probErrataSegnalazione = 'Presenza di rate insolute o probabile errata segnalazione';
                }
            } else if ($item->categoria == 'RISCHI AUTOLIQUIDANTI') {
                if (((float)(float)$item->utilizzato - (float)(float)$item->accordato_operativo) > 0) {
                    $probErrataSegnalazione = 'Sconfino da verificare, possibile errore di valuta';
                }
            } else if (str_contains($item->stato_rapporto, 'crediti scaduti o sconfinanti da più di 90 giorni e non oltre 180 giorni') || str_contains($item->stato_rapporto, 'crediti scaduti o sconfinanti da più di oltre 180')) {
                $probErrataSegnalazione = 'Mancata evidenza dello sconfino nei mesi precedenti, possibile errata segnalazione';
            }

            $importiSconfini[] = array(
                'Data' => $item->anno . ' ' . $item->mese,
                'Banca' => $item->nome_banca,
                'Categoria' => $item->categoria,
                'Tipo attività' => $item->tipo_attivita,
                'Importo Sconfinamento' => (float)$item->accordato_operativo - (float)$item->utilizzato,
                'Utilizzo Posizione Sconfinata' => (float)$item->utilizzato,
                'Probabile errata segnalazione' => $probErrataSegnalazione
            );
        }
        return $importiSconfini;
    }

    public function getCreditiScaduti($banks)
    {
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');
        $categories = array(
            'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI',
        );

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->whereIn('categoria', $categories);
            });
        }
        $creditiScaduti = $CentraleRischiModel->select('categoria', 'importo_garantito')->get()
            ->groupBy(array('categoria'))
            ->toArray();

        return $creditiScaduti;
    }

    public function getCreditiScadutiImpagati($banks)
    {
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');
        $categories = array(
            'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI',
        );


        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->where('stato_rapporto', 'Crediti impagati');
                $query->whereIn('categoria', $categories);
            });
        }
        $creditiScadutiImpagati = $CentraleRischiModel->select('categoria', 'importo_garantito')->get()
            ->groupBy(array('categoria'))
            ->toArray();

        return $creditiScadutiImpagati;
    }

    public function getScadutiSconfinantiEntro90($banks)
    {


        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');


        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->whereRaw('CAST(accordato_operativo as SIGNED) < CAST(utilizzato as SIGNED)');
                $query->where('stato_rapporto', 'not like', "Rapporti non contestati-crediti" . '%');
                $query->where('stato_rapporto', 'not like', "Rapp non contestati" . '%');
            });
        }

        $scadutiSconfinanti90 = $CentraleRischiModel->select('importo_garantito')->get()
            ->toArray();
        return $scadutiSconfinanti90;
    }


    public function getScadutiSconfinanti90($banks)
    {
        // OLTRE I 90 GIORNI

        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');


        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->Where('stato_rapporto', 'like', "Rapp non contestati" . "%");
            });
        }

        $scadutiSconfinanti90 = $CentraleRischiModel->select('importo_garantito')->get()
            ->toArray();

        return $scadutiSconfinanti90;
    }

    public function getScadutiSconfinanti180($banks)
    {

        // OLTRE I 180
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');


        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                //$query->where('stato_rapporto', 'not like', '%non oltre 180%');
                //$query->where('stato_rapporto', 'like', '%oltre 180%');
                //$query->where('stato_rapporto', '!=', "Rapporti non contestati-crediti scaduti o sconfinanti da piu di");

                $query->Where('stato_rapporto', 'like', "Rapporti non contestati-crediti" . "%");
                //$query->where('stato_rapporto', "Rapporti non contestati-crediti scaduti o sconfinanti da piu di");
            });
        }
        $scadutiSconfinanti90 = $CentraleRischiModel->select('categoria', 'importo_garantito')->get()
            ->groupBy(array('categoria'))
            ->toArray();
        return $scadutiSconfinanti90;
    }

    public function getCreditiContestati($banks)
    {
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->Where(function ($query) use ($queryPeriodArray, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->where('stato_rapporto', 'not like', '%non contestati%');
                $query->where('stato_rapporto', 'like', '%contestati%');
            });
        }

        $scadutiSconfinanti90 = $CentraleRischiModel->select('categoria', 'importo_garantito')->get()
            ->groupBy(array('categoria'))
            ->toArray();

        return $scadutiSconfinanti90;
    }

    public function getPercentualeMediaImpagati($banks)
    {
        $periods = $this->getPeriod();

        // dd($periods);

        $incidenzaImpagati = array();


        foreach ($periods as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $papaya) {
                $totaleScaduti = cr::groupBy('anno')->groupBy('mese')->selectRaw("SUM(importo_garantito) as Importo")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI')->where('anno', $singleYear)->where('mese', $singleMonth)->whereIn('nome_banca', $banks)->get()->toArray();
                $totaleScadutiImpagati = cr::groupBy('anno')->groupBy('mese')->selectRaw("SUM(importo_garantito) as Importo")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI')->where('anno', $singleYear)->where('mese', $singleMonth)->where('stato_rapporto', 'Crediti impagati')->whereIn('nome_banca', $banks)->get()->toArray();
                $totaleScadutiPagati = cr::groupBy('anno')->groupBy('mese')->selectRaw("SUM(importo_garantito) as Importo")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI')->where('anno', $singleYear)->where('mese', $singleMonth)->where('stato_rapporto', 'Crediti pagati')->whereIn('nome_banca', $banks)->get()->toArray();

                if (count($totaleScaduti) != 0 && count($totaleScadutiImpagati) != 0 && count($totaleScadutiPagati) != 0) {

                    $incidenzaImpagati[$singleYear][$singleMonth] = number_format(($totaleScadutiImpagati[0]['Importo'] / $totaleScaduti[0]['Importo']) * 100, 2, '.', ',');
                } else {
                    $incidenzaImpagati[$singleYear][$singleMonth] = 0;
                }
            }
        }

        $sum = 0;
        $countMonths = $this->getCountMonths();
        foreach ($incidenzaImpagati as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $papaya) {
                $sum += $incidenzaImpagati[$singleYear][$singleMonth];
            }
        }

        $sum = $sum / $countMonths;

        return number_format($sum, 2, '.', ',');
    }

    public function getRischiGaranzieArray($banks)
    {

        $rischiGaranzie['CreditiScaduti'] = $this->getCreditiScaduti($banks);
        $rischiGaranzie['CreditiScadutiImpagati'] = $this->getCreditiScadutiImpagati($banks);
        $rischiGaranzie['PercentualeMediaImpagati'] = $this->getPercentualeMediaImpagati($banks);
        $rischiGaranzie['Sofferenze'] = $this->getSofferenze($banks);
        $rischiGaranzie['CreditiPassatiPerdita'] = $this->getCreditiPassatiPerdita($banks);
        $rischiGaranzie['Entro90'] = $this->getScadutiSconfinantiEntro90($banks);
        $rischiGaranzie['Oltre90'] = $this->getScadutiSconfinanti90($banks);
        $rischiGaranzie['Oltre180'] = $this->getScadutiSconfinanti180($banks);
        $rischiGaranzie['CreditiContestati'] = $this->getCreditiContestati($banks);

        return $rischiGaranzie;
    }

    public function getRischiGaranzie($banks)
    {
        $allMonthsCount = $this->getCountMonths();
        $crediti = $this->getRischiGaranzieArray($banks);

        $mediaRischiGaranzie = array();
        $mediaCreditiScaduti = 0;
        $mediaCreditiScadutiImpagati = 0;
        $mediaSofferenze = 0;
        $mediaCreditiEntro90 = 0;
        $mediaCreditiOltre90 = 0;
        $mediaCreditiOltre180 = 0;
        $mediaCreditiPassatiPerdita = 0;
        $mediaCreditiContestati = 0;

        foreach ($crediti['CreditiScaduti'] as $singleCreditoScadutoLinee => $singleCreditoScadutoLinea) {
            foreach ($singleCreditoScadutoLinea as $label => $singleCreditoScaduto) {
                $mediaCreditiScaduti += (float)str_replace('.', '', $singleCreditoScaduto->importo_garantito);
            }
        }

        $mediaRischiGaranzie['CreditiScaduti'] = $mediaCreditiScaduti / $allMonthsCount;

        foreach ($crediti['CreditiScadutiImpagati'] as $singleCreditoScadutoImpagatoLinee => $singleCreditoImpagatoLinea) {
            foreach ($singleCreditoImpagatoLinea as $label => $singleCreditoScadutoImpagato) {
                $mediaCreditiScadutiImpagati += (float)str_replace('.', '', $singleCreditoScadutoImpagato->importo_garantito);
            }
        }
        $mediaRischiGaranzie['CreditiScadutiImpagati'] = $mediaCreditiScadutiImpagati / $allMonthsCount;

        foreach ($crediti['Sofferenze'] as $singleSofferenza => $singleSofferenzaValue) {
            $mediaSofferenze += (float)str_replace('.', '', $singleSofferenzaValue->importo_garantito);
        }
        $mediaRischiGaranzie['Sofferenze'] = $mediaSofferenze / $allMonthsCount;
        foreach ($crediti['CreditiPassatiPerdita'] as $singleCreditoPassatoPerdita => $singleCreditoPassatoPerditaValue) {
            $mediaCreditiPassatiPerdita += (float)str_replace('.', '', $singleCreditoPassatoPerditaValue->importo_garantito);
        }
        $mediaRischiGaranzie['CreditiPassatiPerdita'] = $mediaCreditiPassatiPerdita / $allMonthsCount;

        foreach ($crediti['Entro90'] as $singleCreditoEntro90 => $singleCreditoEntro90Value) {

            $mediaCreditiEntro90 += (float)str_replace('.', '', $singleCreditoEntro90Value->importo_garantito);
        }

        $mediaRischiGaranzie['Entro90'] = $mediaCreditiEntro90 / $allMonthsCount;

        foreach ($crediti['Oltre90'] as $singleCreditoOltre90 => $singleCreditoOltre90Value) {
            $mediaCreditiOltre90 += (float)str_replace('.', '', $singleCreditoOltre90Value->importo_garantito);
        }
        $mediaRischiGaranzie['Oltre90'] = $mediaCreditiOltre90 / $allMonthsCount;

        foreach ($crediti['Oltre180'] as $singleCreditoOltre180 => $singleCreditoOltre180Value) {
            foreach ($singleCreditoOltre180Value as $label => $singleCreditoOltre180) {
                $mediaCreditiOltre180 += (float)str_replace('.', '', $singleCreditoOltre180->importo_garantito);
            }
        }
        $mediaRischiGaranzie['Oltre180'] = $mediaCreditiOltre180 / $allMonthsCount;

        foreach ($crediti['CreditiContestati'] as $singleCreditoContestato => $singleCreditoContestatoValue) {
            foreach ($singleCreditoContestatoValue as $label => $singleCreditoContestatoValue) {
                $mediaCreditiContestati += (float)str_replace('.', '', $singleCreditoContestatoValue->importo_garantito);
            }
        }
        $mediaRischiGaranzie['CreditiContestati'] = $mediaCreditiContestati / $allMonthsCount;

        //dd($mediaRischiGaranzie);

        return $mediaRischiGaranzie;
    }

    public function getTotaleAffidamenti($categories, $latestYear, $latestMonth, $banks)
    {

        // return cr::groupBy('nome_banca')->groupBy('categoria')->selectRaw("nome_banca, categoria, SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('document_id', $this->_documentId)->where('mese', $latestMonth)->whereIn('categoria', $categories)->whereIn('nome_banca', $banks)->get()->toArray();

        $totAffidamentiConPesiPerBanca = cr::groupBy('nome_banca')->groupBy('categoria')->selectRaw("nome_banca, categoria, SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('document_id', $this->_documentId)->where('mese', $latestMonth)->whereIn('categoria', $categories)->whereIn('nome_banca', $banks)->get()->toArray();
        $totaliAccordatiUtilizzatiLastMonth = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('mese', $latestMonth)->whereIn('categoria', $categories)->whereIn('nome_banca', $banks)->where('document_id', $this->_documentId)->get()->toArray();

        foreach ($totAffidamentiConPesiPerBanca as $index => $singleBank) {

            // dd($singleBank['totAccordatoOperativo'], $totaliAccordatiUtilizzatiLastMonth[0]['totAccordatoOperativo']);
            if ($totaliAccordatiUtilizzatiLastMonth[0]['totAccordatoOperativo'] != 0) {
                $totAffidamentiConPesiPerBanca[$index]['PesoAccordatoOperativo'] = round((($singleBank['totAccordatoOperativo'] / $totaliAccordatiUtilizzatiLastMonth[0]['totAccordatoOperativo']) * 100), 2);
            } else {
                $totAffidamentiConPesiPerBanca[$index]['totAccordatoOperativo'] = 0;
            }

            if ($totaliAccordatiUtilizzatiLastMonth[0]['totUtilizzato'] != 0) {
                $totAffidamentiConPesiPerBanca[$index]['PesoUtilizzato'] = round((($singleBank['totUtilizzato'] / $totaliAccordatiUtilizzatiLastMonth[0]['totUtilizzato']) * 100), 2);
            } else {
                $totAffidamentiConPesiPerBanca[$index]['PesoUtilizzato'] = 0;
            }

        }

        return $totAffidamentiConPesiPerBanca;
    }

    public function getTotaleAffidamentiGeneral($categories, $latestYear, $latestMonth, $banks)
    {
        //return cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('mese', $latestMonth)->whereIn('categoria', $categories)->whereIn('nome_banca', $banks)->where('document_id', $this->_documentId)->get()->toArray();
        return cr::groupBy('nome_banca')->groupBy('categoria')->selectRaw("nome_banca, categoria, SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('mese', $latestMonth)->whereIn('categoria', $categories)->whereIn('nome_banca', $banks)->where('document_id', $this->_documentId)->get()->toArray();
    }

    public function getTotaleAffidamentiPerMese($lastYearPeriod, $categories, $banks)
    {


        $indebitamentoPerMese = array();
        $indebitamentoPerMeseCat = array();
        $singleYear = array_key_first($lastYearPeriod);
        $singleMonth = array_key_first($lastYearPeriod[$singleYear]);

        $startPeriod = new DateTime(cr::select('date')->where('anno', $singleYear)->where('document_id', $this->_documentId)->where('mese', $singleMonth)->distinct()->first()->date);
        $endPeriod = new DateTime(cr::select('date')->where('document_id', $this->_documentId)->orderBy('date', 'desc')->distinct()->first()->date);


        $distinctRisks = cr::selectRaw("date,categoria, SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where("date", '>=', $startPeriod->format('Y-m-d'))->where("date", '<=', $endPeriod->format('Y-m-d'))->orderBy('date')->groupBy('date', 'categoria')->whereIn('categoria', $categories)->whereIn('nome_banca', $banks)->where('document_id', $this->_documentId)->get();


        foreach ($distinctRisks as $label => $singleCrData) {
            $dateTmp = new DateTime($singleCrData->date);
            $tmp = $dateTmp->format('y') . '-' . $dateTmp->format('m');
            $affidamentoPerMese[$singleCrData->categoria][] = array($tmp, $singleCrData->totAccordatoOperativo, $singleCrData->totUtilizzato);
        }


        foreach($affidamentoPerMese as $category => $arrData) {
            $accordatoDataByCat = array();
            $utilizzatoDataByCat = array();
            $dateArrayByCat = array();

            foreach($arrData as $key => $values) {
                $date = $values[0];
                $accordatoOperativo = $values[1];
                $utilizzato = $values[2];

                array_push($dateArrayByCat, $date);
                array_push($accordatoDataByCat, $accordatoOperativo);
                array_push($utilizzatoDataByCat, $utilizzato);
            }

            $indebitamentoPerMeseCat[$category]['dates'] = $dateArrayByCat;
            $indebitamentoPerMeseCat[$category]['series'] = array(
                [
                    "name" => "Accordato",
                    "data" => $accordatoDataByCat,
                ],
                [
                    "name" => "Utilizzato",
                    "data" => $utilizzatoDataByCat,
                ]
            );

        }



        $indebitamento = cr::selectRaw("date, SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where("date", '>=', $startPeriod->format('Y-m-d'))->where("date", '<=', $endPeriod->format('Y-m-d'))->orderBy('date')->groupBy('date')->whereIn('categoria', $categories)->whereIn('nome_banca', $banks)->where('document_id', $this->_documentId)->get();
        $accordatoData = array();
        $utilizzatoData = array();
        $dateArray = array();
        foreach ($indebitamento as $label => $singleIndebitamentoData) {
            $dateTmp = new DateTime($singleIndebitamentoData->date);
            $tmp = $dateTmp->format('Y-m-d');

            array_push($dateArray, $tmp);
            array_push($accordatoData, $singleIndebitamentoData->totAccordatoOperativo);
            array_push($utilizzatoData, $singleIndebitamentoData->totUtilizzato);
        }

        $indebitamentoPerMese['dates'] = $dateArray;
        $indebitamentoPerMese['series'] = array(
            [
                "name" => "Accordato",
                "data" => $accordatoData,
            ],
            [
                "name" => "Utilizzato",
                "data" => $utilizzatoData,
            ]
        );

        return array("IndebitamentoTotale" => $indebitamentoPerMese, "IndebitamentoPerCategoria" => $indebitamentoPerMeseCat);
    }

    public function getPesiAffidamentiPerBanca($categories, $latestYear, $latestMonth, $banks)
    {
        $totaliAccordatiUtilizzatiLastMonth = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('mese', $latestMonth)->whereIn('categoria', $categories)->whereIn('nome_banca', $banks)->where('document_id', $this->_documentId)->get()->toArray();
        $totAffidamentiConPesiPerBanca = cr::groupBy('nome_banca')->selectRaw("nome_banca, SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('mese', $latestMonth)->where('document_id', $this->_documentId)->whereIn('categoria', $categories)->whereIn('nome_banca', $banks)->get()->toArray();

        foreach ($totAffidamentiConPesiPerBanca as $index => $singleBank) {
            if ($totaliAccordatiUtilizzatiLastMonth[0]['totAccordatoOperativo'] != 0) {
                $totAffidamentiConPesiPerBanca[$index]['PesoAccordatoOperativo'] = (($singleBank['totAccordatoOperativo'] / $totaliAccordatiUtilizzatiLastMonth[0]['totAccordatoOperativo']) * 100);
            } else {
                $totAffidamentiConPesiPerBanca[$index]['totAccordatoOperativo'] = 0;
            }

            if ($totaliAccordatiUtilizzatiLastMonth[0]['totUtilizzato'] != 0) {
                $totAffidamentiConPesiPerBanca[$index]['PesoUtilizzato'] = (($singleBank['totUtilizzato'] / $totaliAccordatiUtilizzatiLastMonth[0]['totUtilizzato']) * 100);
            } else {
                $totAffidamentiConPesiPerBanca[$index]['PesoUtilizzato'] = 0;
            }
        }

        return $totAffidamentiConPesiPerBanca;
    }


    public function getInformazioniGaranti($banks)
    {
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->where('sezione', 'Garanti');
                $query->whereIn('nome_banca', $banks);
            });
        }


        $informazioniGaranti = $CentraleRischiModel->select('anno', 'mese', 'date', 'nome_banca', 'importo_garantito', 'garanzia')->orderBy('date')->get()
            ->groupBy('date')
            ->toArray();

        // dd($periods);

        //dd($informazioniGaranti);

        $datiGaranti = array();

        // dd($informazioniGaranti);
        $totValoreGaranzia = 0;
        $totImportoGarantito = 0;

        foreach ($periods as $label => $queryPeriod) {
            $data = cr::selectRaw('SUM(importo_garantito) as garantito, SUM(garanzia) as garanzia, anno, mese, categoria, sezione')
                ->where('sezione', 'Garanti')
                ->where('anno', $queryPeriod['anno'])
                ->where('mese', $queryPeriod['mese'])
                ->where('document_id', $this->_documentId)
                ->whereIn('nome_banca', $banks)
                ->groupBy(array('anno', 'mese', 'categoria', 'sezione'))
                ->get()->first();
            if ($data !== null) {
                $data = $data->toArray();
                $totValoreGaranzia += $data['garantito'];
                $totImportoGarantito += $data['garanzia'];
            }
        }


        $totImportoGarantito = $totImportoGarantito / count($periods);
        $totValoreGaranzia = $totValoreGaranzia / count($periods);

        foreach ($periods as $queryPeriodArray) {
            $infoGaranti =
                cr::where('anno', $queryPeriodArray['anno'])
                ->where('document_id', $this->_documentId)
                ->where('mese', $queryPeriodArray['mese'])
                ->where('sezione', 'Garanti')
                ->whereIn('nome_banca', $banks)
                ->orderBy('date', 'asc')
                ->get();

            foreach ($infoGaranti as $label => $singleInfo) {
                $anomalie = cr::where('anno', $singleInfo->anno)
                    ->where('document_id', $this->_documentId)
                    ->where('mese', $singleInfo->mese)
                    ->where('sezione', 'Cassa')
                    ->where('nome_banca', $singleInfo->nome_banca)
                    ->orderBy('date', 'asc')
                    ->get();

                if (is_numeric($singleInfo->importo_garantito) && is_numeric($singleInfo->garanzia)) {
                    if ($singleInfo->garanzia == 0) {
                        if ($singleInfo->importo_garantito > 0) {
                            $datiGaranti[$singleInfo->nome_banca][$singleInfo->date]['Info sui garanti'][] = 'Garanzie Sovradimensionate';
                        }
                    } else {
                        if (($singleInfo->importo_garantito / $singleInfo->garanzia) > 2) {
                            $datiGaranti[$singleInfo->nome_banca][$singleInfo->date]['Info sui garanti'][] = 'Garanzie Sovradimensionate';
                        }
                    }

                    foreach ($anomalie as $index => $singleAnomalia) {
                        if ($singleAnomalia->categoria == 'RISCHI AUTOLIQUIDANTI' && (float)$singleAnomalia->utilizzato == 0) {
                            $datiGaranti[$singleAnomalia->nome_banca][$singleAnomalia->date][$singleAnomalia->categoria][] = 'Fido Inutilizzato';
                        } else if ($singleAnomalia->categoria == 'RISCHI AUTOLIQUIDANTI' && (float)$singleAnomalia->utilizzato == (float)$singleAnomalia->accordato_operativo) {
                            $datiGaranti[$singleAnomalia->nome_banca][$singleAnomalia->date][$singleAnomalia->categoria][] = 'Utilizzato pari ad accordato';
                        } else if ($singleAnomalia->categoria != 'RISCHI AUTOLIQUIDANTI' && (float)$singleAnomalia->utilizzato == 0) {
                            $datiGaranti[$singleAnomalia->nome_banca][$singleAnomalia->date][$singleAnomalia->categoria][] = 'Garanzie Inutilizzate';
                        }
                    }
                }
            }
        }


        // dd($datiGaranti);

        // !!!   $cr->importo_garantito = $valoreGaranzia;
        // !!!   $cr->garanzia = $importo_garantito;

        return array('Anomalie' => $datiGaranti, 'Tot. Valore Garanzia' => $totValoreGaranzia, 'Tot. importo garantito' => $totImportoGarantito);
    }


    //deprecato, vedi sopra
    public function getAnomalie($banks)
    {
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs')->where('document_id', $this->_documentId);
        $anomalieCategorizzate = array();

        foreach ($periods as $queryPeriodArray) {
            $infoGaranti =
                cr::where('anno', $queryPeriodArray['anno'])
                ->where('document_id', $this->_documentId)
                ->where('mese', $queryPeriodArray['mese'])
                ->where('sezione', 'Garanti')
                ->orderBy('date')
                ->get();

            foreach ($infoGaranti as $label => $singleInfo) {
                $anomalie = cr::where('anno', $singleInfo->anno)
                    ->where('mese', $singleInfo->mese)
                    ->where('document_id', $this->_documentId)
                    ->where('sezione', 'Cassa')
                    ->where('nome_banca', $singleInfo->nome_banca)
                    ->orderBy('date')
                    ->get();

                foreach ($anomalie as $index => $singleAnomalia) {
                    if ($singleAnomalia->categoria == 'RISCHI AUTOLIQUIDANTI' && (float)$singleAnomalia->utilizzato == 0) {
                        $anomalieCategorizzate[] = [
                            'nome_banca' => $singleAnomalia->nome_banca,
                            'date' => $singleAnomalia->date,
                            'categoria' => $singleAnomalia->categoria,
                            'anomalia' => 'Fido Inutilizzato',
                        ];
                        //$anomalieCategorizzate[$singleAnomalia->nome_banca][$singleAnomalia->date][$singleAnomalia->categoria][] = 'Fido Inutilizzato';
                    } else if ($singleAnomalia->categoria == 'RISCHI AUTOLIQUIDANTI' && (float)$singleAnomalia->utilizzato == (float)$singleAnomalia->accordato_operativo) {
                        $anomalieCategorizzate[] = [
                            'nome_banca' => $singleAnomalia->nome_banca,
                            'date' => $singleAnomalia->date,
                            'categoria' => $singleAnomalia->categoria,
                            'anomalia' => 'Utilizzato pari ad accordato',
                        ];
                        //$anomalieCategorizzate[$singleAnomalia->nome_banca][$singleAnomalia->date][$singleAnomalia->categoria][] = 'Utilizzato pari ad accordato';
                    } else if ($singleAnomalia->categoria != 'RISCHI AUTOLIQUIDANTI' && (float)$singleAnomalia->utilizzato == 0) {
                        $anomalieCategorizzate[] = [
                            'nome_banca' => $singleAnomalia->nome_banca,
                            'date' => $singleAnomalia->date,
                            'categoria' => $singleAnomalia->categoria,
                            'anomalia' => 'Garanzie Inutilizzate',
                        ];
                       // $anomalieCategorizzate[$singleAnomalia->nome_banca][$singleAnomalia->date][$singleAnomalia->categoria][] = 'Garanzie Inutilizzate';
                    }
                }
            }
        }

        return $anomalieCategorizzate;
    }

    public function getGaranzieRicevute($banks)
    {
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $totImportoGarantito = 0;
        $totValoreGaranzia = 0;

        foreach ($periods as $queryPeriodArray) {
            $garanzieRicevute =
                cr::select('importo_garantito', 'garanzia', 'date')
                ->where('anno', $queryPeriodArray['anno'])
                ->where('document_id', $this->_documentId)
                ->where('mese', $queryPeriodArray['mese'])
                ->where('categoria', 'GARANZIE RICEVUTE')
                ->orderBy('date')
                ->get();

            // dd($garanzieRicevute);

            foreach ($garanzieRicevute as $label => $singleGaranzia) {
                $totImportoGarantito += (float)str_replace('.', '', $singleGaranzia->importo_garantito);
                $totValoreGaranzia += (float)str_replace('.', '', $singleGaranzia->garanzia);
            }
        }

        $totImportoGarantito = $totImportoGarantito / $allMonthsCount;
        $totValoreGaranzia = $totValoreGaranzia / $allMonthsCount;

        return array('Garantito' => $totImportoGarantito, 'Garanzia' => $totValoreGaranzia);
    }

    public function divideAnomalie($numeroSconfiniTotali, $banks)
    {

        $importiSconfini = array(
            'SconfiniEntro90Giorni' => [],
            'SconfiniOltre90Giorni' => [],
            'SconfiniOltre180Giorni' => [],
        );

        // $this->errateSegnalazioni($banks, $numeroSconfiniTotali);

        foreach ($numeroSconfiniTotali['SconfiniEntro90Giorni'] as $label => $item) {

            $probErrataSegnalazione = '';

            $dataSconfino = new DateTime($item->date);

            if ((float)$item->accordato_operativo == 0) {
                $probErrataSegnalazione = 'Accordato Nullo';
                $importiSconfini['SconfiniEntro90Giorni'][] = array(
                    'data' => $item->anno . ' ' . $item->mese,
                    'banca' => $item->nome_banca,
                    'categoria' => $item->categoria,
                    'tipo_attivita' => $item->tipo_attivita,
                    'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                    'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                    'probabile_errata_segnalazione' => $probErrataSegnalazione
                );
            } else if ($item->categoria == 'RISCHI A SCADENZA') {
                if (((float)$item->utilizzato / (float)$item->accordato_operativo) < 1) {
                    $probErrataSegnalazione = 'Utilizzo anomalo, probabile errata segnalazione';
                    $importiSconfini['SconfiniEntro90Giorni'][] = array(
                        'data' => $item->anno . ' ' . $item->mese,
                        'banca' => $item->nome_banca,
                        'categoria' => $item->categoria,
                        'tipo_attivita' => $item->tipo_attivita,
                        'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                        'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                        'probabile_errata_segnalazione' => $probErrataSegnalazione
                    );
                } else if (((float)$item->utilizzato / (float)$item->accordato_operativo) > 1) {
                    $probErrataSegnalazione = 'Presenza di rate insolute o probabile errata segnalazione';
                    $importiSconfini['SconfiniEntro90Giorni'][] = array(
                        'data' => $item->anno . ' ' . $item->mese,
                        'banca' => $item->nome_banca,
                        'categoria' => $item->categoria,
                        'tipo_attivita' => $item->tipo_attivita,
                        'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                        'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                        'probabile_errata_segnalazione' => $probErrataSegnalazione
                    );
                }
            } else if ($item->categoria == 'RISCHI AUTOLIQUIDANTI') {
                if (((float)$item->utilizzato - (float)$item->accordato_operativo) > 0) {
                    $probErrataSegnalazione = 'Sconfino da verificare, possibile errore di valuta';
                    $importiSconfini['SconfiniEntro90Giorni'][] = array(
                        'data' => $item->anno . ' ' . $item->mese,
                        'banca' => $item->nome_banca,
                        'categoria' => $item->categoria,
                        'tipo_attivita' => $item->tipo_attivita,
                        'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                        'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                        'probabile_errata_segnalazione' => $probErrataSegnalazione
                    );
                }
            } else if (str_contains($item->stato_rapporto, 'cred scad o sconf da')) {
                $probErrataSegnalazione = 'Mancata evidenza dello sconfino nei mesi precedenti, possibile errata segnalazione';
                $importiSconfini['SconfiniEntro90Giorni'][] = array(
                    'data' => $item->anno . ' ' . $item->mese,
                    'banca' => $item->nome_banca,
                    'categoria' => $item->categoria,
                    'tipo_attivita' => $item->tipo_attivita,
                    'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                    'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                    'probabile_errata_segnalazione' => $probErrataSegnalazione
                );
            } else {
                $importiSconfini['SconfiniEntro90Giorni'][] = array(
                    'data' => $item->anno . ' ' . $item->mese,
                    'banca' => $item->nome_banca,
                    'categoria' => $item->categoria,
                    'tipo_attivita' => $item->tipo_attivita,
                    'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                    'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                    'probabile_errata_segnalazione' => 'Sconfino da verificare'
                );
            }
        }


        foreach ($numeroSconfiniTotali['SconfiniOltre90Giorni'] as $label => $item) {

            $probErrataSegnalazione = '';

            if ((float)$item->accordato_operativo == 0) {
                $probErrataSegnalazione = 'Accordato Nullo';
                $importiSconfini['SconfiniOltre90Giorni'][] = array(
                    'data' => $item->anno . ' ' . $item->mese,
                    'banca' => $item->nome_banca,
                    'categoria' => $item->categoria,
                    'tipo_attivita' => $item->tipo_attivita,
                    'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                    'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                    'probabile_errata_segnalazione' => $probErrataSegnalazione
                );
            } else if ($item->categoria == 'RISCHI A SCADENZA') {
                if (((float)$item->utilizzato / (float)$item->accordato_operativo) < 1) {
                    $probErrataSegnalazione = 'Utilizzo anomalo, probabile errata segnalazione';
                    $importiSconfini['SconfiniOltre90Giorni'][] = array(
                        'data' => $item->anno . ' ' . $item->mese,
                        'banca' => $item->nome_banca,
                        'categoria' => $item->categoria,
                        'tipo_attivita' => $item->tipo_attivita,
                        'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                        'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                        'probabile_errata_segnalazione' => $probErrataSegnalazione
                    );
                } else if (((float)$item->utilizzato / (float)$item->accordato_operativo) > 1) {
                    $probErrataSegnalazione = 'Presenza di rate insolute o probabile errata segnalazione';
                    $importiSconfini['SconfiniOltre90Giorni'][] = array(
                        'data' => $item->anno . ' ' . $item->mese,
                        'banca' => $item->nome_banca,
                        'categoria' => $item->categoria,
                        'tipo_attivita' => $item->tipo_attivita,
                        'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                        'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                        'probabile_errata_segnalazione' => $probErrataSegnalazione
                    );
                }
            } else if ($item->categoria == 'RISCHI AUTOLIQUIDANTI') {
                if (((float)$item->utilizzato - (float)$item->accordato_operativo) > 0) {
                    $probErrataSegnalazione = 'Sconfino da verificare, possibile errore di valuta';
                    $importiSconfini['SconfiniOltre90Giorni'][] = array(
                        'data' => $item->anno . ' ' . $item->mese,
                        'banca' => $item->nome_banca,
                        'categoria' => $item->categoria,
                        'tipo_attivita' => $item->tipo_attivita,
                        'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                        'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                        'probabile_errata_segnalazione' => $probErrataSegnalazione
                    );
                }
            } else if (str_contains($item->stato_rapporto, 'cred scad o sconf da')) {
                $probErrataSegnalazione = 'Mancata evidenza dello sconfino nei mesi precedenti, possibile errata segnalazione';
                $importiSconfini['SconfiniOltre90Giorni'][] = array(
                    'data' => $item->anno . ' ' . $item->mese,
                    'banca' => $item->nome_banca,
                    'categoria' => $item->categoria,
                    'tipo_attivita' => $item->tipo_attivita,
                    'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                    'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                    'probabile_errata_segnalazione' => $probErrataSegnalazione
                );
            } else {
                $importiSconfini['SconfiniOltre90Giorni'][] = array(
                    'data' => $item->anno . ' ' . $item->mese,
                    'banca' => $item->nome_banca,
                    'categoria' => $item->categoria,
                    'tipo_attivita' => $item->tipo_attivita,
                    'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                    'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                    'probabile_errata_segnalazione' => 'Sconfino da verificare'
                );
            }
        }


        foreach ($numeroSconfiniTotali['SconfiniOltre180Giorni'] as $label => $item) {

            $probErrataSegnalazione = '';

            if ((float)$item->accordato_operativo == 0) {
                $probErrataSegnalazione = 'Accordato Nullo';
                $importiSconfini['SconfiniOltre180Giorni'][] = array(
                    'data' => $item->anno . ' ' . $item->mese,
                    'banca' => $item->nome_banca,
                    'categoria' => $item->categoria,
                    'tipo_attivita' => $item->tipo_attivita,
                    'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                    'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                    'probabile_errata_segnalazione' => $probErrataSegnalazione
                );
            } else if ($item->categoria == 'RISCHI A SCADENZA') {
                if (((float)$item->utilizzato / (float)$item->accordato_operativo) < 1) {
                    $probErrataSegnalazione = 'Utilizzo anomalo, probabile errata segnalazione';
                    $importiSconfini['SconfiniOltre180Giorni'][] = array(
                        'data' => $item->anno . ' ' . $item->mese,
                        'banca' => $item->nome_banca,
                        'categoria' => $item->categoria,
                        'tipo_attivita' => $item->tipo_attivita,
                        'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                        'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                        'probabile_errata_segnalazione' => $probErrataSegnalazione
                    );
                } else if (((float)$item->utilizzato / (float)$item->accordato_operativo) > 1) {
                    $probErrataSegnalazione = 'Presenza di rate insolute o probabile errata segnalazione';
                    $importiSconfini['SconfiniOltre180Giorni'][] = array(
                        'data' => $item->anno . ' ' . $item->mese,
                        'banca' => $item->nome_banca,
                        'categoria' => $item->categoria,
                        'tipo_attivita' => $item->tipo_attivita,
                        'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                        'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                        'probabile_errata_segnalazione' => $probErrataSegnalazione
                    );
                }
            } else if ($item->categoria == 'RISCHI AUTOLIQUIDANTI') {
                if (((float)$item->utilizzato - (float)$item->accordato_operativo) > 0) {
                    $probErrataSegnalazione = 'Sconfino da verificare, possibile errore di valuta';
                    $importiSconfini['SconfiniOltre180Giorni'][] = array(
                        'data' => $item->anno . ' ' . $item->mese,
                        'banca' => $item->nome_banca,
                        'categoria' => $item->categoria,
                        'tipo_attivita' => $item->tipo_attivita,
                        'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                        'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                        'probabile_errata_segnalazione' => $probErrataSegnalazione
                    );
                }
            } else if (str_contains($item->stato_rapporto, 'cred scad o sconf da')) {
                $probErrataSegnalazione = 'Mancata evidenza dello sconfino nei mesi precedenti, possibile errata segnalazione';
                $importiSconfini['SconfiniOltre180Giorni'][] = array(
                    'data' => $item->anno . ' ' . $item->mese,
                    'banca' => $item->nome_banca,
                    'categoria' => $item->categoria,
                    'tipo_attivita' => $item->tipo_attivita,
                    'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                    'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                    'probabile_errata_segnalazione' => $probErrataSegnalazione
                );
            } else {
                $importiSconfini['SconfiniOltre180Giorni'][] = array(
                    'data' => $item->anno . ' ' . $item->mese,
                    'banca' => $item->nome_banca,
                    'categoria' => $item->categoria,
                    'tipo_attivita' => $item->tipo_attivita,
                    'importo_sconfinamento' => number_format((float)$item->accordato_operativo - (float)$item->utilizzato, 2, ',', '.'),
                    'utilizzo_posizione_sconfinata' => number_format((float)$item->utilizzato, 2, ',', '.'),
                    'probabile_errata_segnalazione' => 'Sconfino da verificare'
                );
            }
        }
        return $importiSconfini;
    }

    public function errateSegnalazioni($banks, $numeroSconfiniTotali)
    {
        $period = $this->buildPeriodArray();

        foreach ($period as $per => $periodQuery) {
            $segnalazioniModel =
                cr::where('stato_rapporto', 'like', '%cred scad o sconf da%')
                ->where('anno', $periodQuery['anno'])
                ->where('document_id', $this->_documentId)
                ->where('mese', $periodQuery['mese'])
                ->whereIn('nome_banca', $banks);

            if ($segnalazioniModel->count() != 0) {
                $errateSegnalazioni[] = $segnalazioniModel->get();
            }
        }

        foreach ($errateSegnalazioni as $label => $items) {
            foreach ($items as $itemLabel => $singleItem) {
                $dataSegnalazione = new DateTime($singleItem->date);

                $mancataSegnalazioneEntro90 = new DateTime($singleItem->date);
                $mancataSegnalazioneOltre90 = new DateTime($singleItem->date);
                $mancataSegnalazioneOltre180 = new DateTime($singleItem->date);

                $mancataSegnalazioneEntro90 = $mancataSegnalazioneEntro90->modify('-2 months');
                $mancataSegnalazioneOltre90 = $mancataSegnalazioneOltre90->modify('-5 months');
                $mancataSegnalazioneOltre180 = $mancataSegnalazioneOltre180->modify('-11 months');

                $mesiEntro90 =
                    cr::select('date', 'anno', 'mese')
                    ->where('document_id', $this->_documentId)
                    ->where('date', '>=', $mancataSegnalazioneEntro90->format('Y-m-d'))
                    ->where('date', '<=', $dataSegnalazione->format('Y-m-d'))
                    ->distinct()->orderBy('date')->get()->toArray();

                foreach ($mesiEntro90 as $index => $singleMeseEntro90) {
                    // foreach($)
                }

                $mesiOltre90 =
                    cr::select('date', 'anno', 'mese')
                    ->where('document_id', $this->_documentId)
                    ->where('date', '>=', $mancataSegnalazioneOltre90->format('Y-m-d'))
                    ->where('date', '<=', $dataSegnalazione->format('Y-m-d'))
                    ->distinct()->orderBy('date')->get()->toArray();

                foreach ($mesiEntro90 as $index => $singleMeseEntro90) {
                }

                $mesiOltre180 =
                    cr::select('date', 'anno', 'mese')
                    ->where('document_id', $this->_documentId)
                    ->where('date', '>=', $mancataSegnalazioneOltre180->format('Y-m-d'))
                    ->where('date', '<=', $dataSegnalazione->format('Y-m-d'))
                    ->distinct()->orderBy('date')->get()->toArray();

                foreach ($mesiEntro90 as $index => $singleMeseEntro90) {
                }

                // dd($mesiEntro90, $mesiOltre90, $mesiOltre180);
            }
        }
    }

    public function mancateSegnalazioniStatoRapporto($banks)
    {
        $CentraleRischiModel = DB::table('crs');
        $periods = $this->buildPeriodArray();

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $banks) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereIn('nome_banca', $banks);
                $query->where('stato_rapporto', '!=', '');
                $query->where('stato_rapporto', 'like', '%scad o sconf%');
            });
        }
        $errateSegnalazioni = $CentraleRischiModel->get();
        $sconfini = $this->getTotaleSconfini($banks);

        $anomalie = array();

        foreach ($errateSegnalazioni as $index => $singleErrataSegnalazione) {
            foreach ($sconfini['SconfiniOltre90Giorni'] as $entro90index => $singleSconfiniEntro180) {
                if (
                    $singleSconfiniEntro180->nome_banca == $singleErrataSegnalazione->nome_banca &&
                    $singleSconfiniEntro180->categoria == $singleErrataSegnalazione->categoria &&
                    $singleSconfiniEntro180->sezione == $singleErrataSegnalazione->sezione &&
                    $singleSconfiniEntro180->divisa == $singleErrataSegnalazione->divisa &&
                    $singleSconfiniEntro180->localizzazione == $singleErrataSegnalazione->localizzazione
                ) {
                    $anomalie['Sconfini entro 180 giorni'][] = array(
                        'Data' => $singleErrataSegnalazione->anno . ' ' . $singleErrataSegnalazione->mese,
                        'Banca' => $singleErrataSegnalazione->nome_banca,
                        'Categoria' => $singleErrataSegnalazione->categoria,
                        'Tipo attività' => $singleErrataSegnalazione->tipo_attivita,
                        'Importo Sconfinamento' => (float)$singleSconfiniEntro180->accordato_operativo - (float)$singleSconfiniEntro180->utilizzato,
                        'Utilizzo Posizione Sconfinata' => $singleSconfiniEntro180->utilizzato,
                        'Probabile errata segnalazione' => 'Mancata evidenza dello sconfino nei mesi precedenti. Possibile errata segnalazione'
                    );
                }
            }
            foreach ($sconfini['SconfiniOltre180Giorni'] as $entro90index => $singleSconfiniOltre180) {
                if (
                    $singleSconfiniOltre180->nome_banca == $singleErrataSegnalazione->nome_banca &&
                    $singleSconfiniOltre180->categoria == $singleErrataSegnalazione->categoria &&
                    $singleSconfiniOltre180->sezione == $singleErrataSegnalazione->sezione &&
                    $singleSconfiniOltre180->localizzazione == $singleErrataSegnalazione->divisa &&
                    $singleSconfiniOltre180->localizzazione == $singleErrataSegnalazione->localizzazione

                ) {
                    $anomalie['Sconfini oltre 180 giorni'][] = array(
                        'Data' => $singleErrataSegnalazione->anno . ' ' . $singleErrataSegnalazione->mese,
                        'Banca' => $singleErrataSegnalazione->nome_banca,
                        'Categoria' => $singleErrataSegnalazione->categoria,
                        'Tipo attività' => $singleErrataSegnalazione->tipo_attivita,
                        'Importo Sconfinamento' => (float)$singleSconfiniOltre180->accordato_operativo - (float)$singleSconfiniOltre180->utilizzato,
                        'Utilizzo Posizione Sconfinata' => $singleSconfiniOltre180->utilizzato,
                        'Probabile errata segnalazione' => 'Mancata evidenza dello sconfino nei mesi precedenti. Possibile errata segnalazione'
                    );
                }
            }
        }
        return $anomalie;
    }

    public function getAffidamentiGeneralTrimestrale($trimestre)
    {

        $totAffidamenti = 0;
        $affidamentiPerCategoria = array();
        $affidamentiPerBanca = array();
        $utilizzoLineeCredito = array();
        $utilizzatoPerCategoria = array();
        $accordatoPerMese = array();
        $lastMonth = $trimestre[2]['mese'];

        $categories = array('RISCHI AUTOLIQUIDANTI', 'RISCHI A REVOCA', 'RISCHI A SCADENZA');
        $categoriesColumnChart = array('RISCHI AUTOLIQUIDANTI', 'RISCHI A REVOCA', 'RISCHI A SCADENZA', array("role" => 'style'));


        $queryPeriodArray = array();
        $CentraleRischiModel = DB::table('crs');
        $lastMonth = $trimestre[2]['mese'];

        foreach ($trimestre as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
            });
            $generalModel = $CentraleRischiModel->selectRaw('SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese')->whereIn('categoria', $categories)->groupBy('categoria', 'anno', 'mese')->get()->toArray();
            $detailedModel = $CentraleRischiModel->selectRaw('SUM(accordato_operativo) as totAccordatoOperativo, categoria, anno, mese, nome_banca')->whereIn('categoria', $categories)->groupBy('nome_banca', 'categoria', 'anno', 'mese')->get()->toArray();

            foreach ($generalModel as $singleModel) {
                $utilizzatoPerCategoria[$singleModel->mese][$singleModel->categoria] = isset($utilizzatoPerCategoria[$singleModel->mese][$singleModel->categoria]) ? $utilizzatoPerCategoria[$singleModel->mese][$singleModel->categoria] + $singleModel->totUtilizzato : $singleModel->totUtilizzato;
                $accordatoPerMese[$singleModel->mese][$singleModel->categoria] = isset($accordatoPerMese[$singleModel->mese][$singleModel->categoria]) ? $accordatoPerMese[$singleModel->mese][$singleModel->categoria] + $singleModel->totAccordatoOperativo : $singleModel->totAccordatoOperativo;
                $affidamentiPerCategoria[$singleModel->mese][$singleModel->categoria] = isset($affidamentiPerCategoria[$singleModel->mese][$singleModel->categoria]) ? $affidamentiPerCategoria[$singleModel->mese][$singleModel->categoria] + $singleModel->totAccordatoOperativo : $singleModel->totAccordatoOperativo;
            }
            foreach ($detailedModel as $singleModel) {
                $affidamentiPerBanca[$singleModel->mese][$singleModel->nome_banca][$singleModel->categoria] = isset($affidamentiPerCategoria[$singleModel->nome_banca][$singleModel->categoria]) ? $affidamentiPerCategoria[$singleModel->nome_banca][$singleModel->categoria] + $singleModel->totAccordatoOperativo : $singleModel->totAccordatoOperativo;
            }
        }
        foreach ($utilizzatoPerCategoria as $singleMonth => $creditLines) {
            foreach ($creditLines as $creditType => $data) {
                if ($accordatoPerMese[$singleMonth][$creditType] == 0) {
                    $utilizzoLineeCredito[$singleMonth][$creditType] = 0;
                } else {
                    $utilizzoLineeCredito[$singleMonth][$creditType] = 100 * ($data / $accordatoPerMese[$singleMonth][$creditType]);
                }
            }
        }
        $affidamentiFormatted = array(array('Categoria', 'Percentuale'));

        foreach ($affidamentiPerCategoria[$lastMonth] as $singleCategory => $data) {
            array_push($affidamentiFormatted, array($singleCategory, $data));
        }

        $affidamentiPerBancaFormatted = array(array_merge(array('Banche'), $categories));

        foreach ($affidamentiPerBanca[$lastMonth] as $singleBank => $multipleCategories) {
            $singleBankArray = array($singleBank);
            foreach ($categories as $singolaCategoria) {
                if (isset($multipleCategories[$singolaCategoria])) {
                    array_push($singleBankArray, $multipleCategories[$singolaCategoria]);
                } else {
                    array_push($singleBankArray, 0);
                }
            }


            array_push($affidamentiPerBancaFormatted, $singleBankArray);
        }

        return array('General' => $affidamentiFormatted, 'Banche' => $affidamentiPerBancaFormatted, 'Utilizzo' => $utilizzoLineeCredito);
    }

    public function getPresenzaSconfini($singleTrimestre)
    {
        $cleanData = array();
        $periods = $singleTrimestre;
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );
        $sconfiniPerMese = array();

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereRaw('CAST(accordato_operativo as SIGNED) < CAST(utilizzato as SIGNED)');
                $query->whereIn('categoria', $categories);
            });
        }
        $sconfiniTotali = $CentraleRischiModel
            ->orderBy('date')
            ->get()
            ->toArray();


        foreach ($sconfiniTotali as $label => $item) {
            $sconfiniPerMese[$item->mese][$item->categoria]['Importo'] = isset($sconfiniPerMese[$item->mese][$item->categoria]['Importo']) ? $sconfiniPerMese[$item->mese][$item->categoria]['Importo'] + ((float)(float)$item->utilizzato - (float)(float)$item->accordato_operativo) : (float)(float)$item->utilizzato - (float)(float)$item->accordato_operativo;
        }

        if (!empty($sconfiniTotali)) {
            foreach ($singleTrimestre as $singleMonth) {
                $disponibilita = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")
                    ->where('anno', $singleMonth['anno'])
                    ->where('mese', $singleMonth['mese'])
                    ->where('document_id', $this->_documentId)
                    ->whereIn('categoria', $categories)
                    ->groupBy('categoria', 'anno', 'mese', 'date')
                    ->get();
                foreach ($disponibilita as $item) {
                    $sconfiniPerMese[$item->mese][$item->categoria]['Disp'] = (float)$item->totAccordatoOperativo - (float)$item->totUtilizzato;
                }
            }
        }

        return $sconfiniPerMese;
    }

    public function verificaImpagati($trimestre)
    {
        $periods = $trimestre;
        $categoriaScaduti = array(
            'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI',
        );

        $verificaImpagatiData = array();

        foreach ($trimestre as $singleMonth) {
            $impagati =
                cr::selectRaw('SUM(importo_garantito) as totImportoGarantito')
                ->where('stato_rapporto', 'Crediti impagati')
                ->where('categoria', $categoriaScaduti)
                ->where('anno', $singleMonth['anno'])
                ->where('mese', $singleMonth['mese'])
                ->get()->first();

            $affidamento =
                cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")
                ->where('anno', $singleMonth['anno'])
                ->where('mese', $singleMonth['mese'])
                ->where('document_id', $this->_documentId)
                ->where('categoria', 'RISCHI A REVOCA')
                ->groupBy('categoria', 'anno', 'mese', 'date')
                ->get()->first();

            if ($impagati !== null) {
                if ($affidamento !== null) {
                    $verificaImpagatiData[$singleMonth['mese']] = array('Impagati' => $impagati->totImportoGarantito, 'Affidamento' => ((float)$affidamento->totAccordatoOperativo - (float)$affidamento->totUtilizzato));
                }
            } else {
                if ($affidamento !== null) {
                    $verificaImpagatiData[$singleMonth['mese']] = array('Impagati' => 0, 'Affidamento' => ((float)$affidamento->totAccordatoOperativo - (float)$affidamento->totUtilizzato));
                }
            }
        }

        return $verificaImpagatiData;
    }

    public function verificaDisponibilita($singleTrimestre)
    {
        $disponbilitaPerMese = array();
        foreach ($singleTrimestre as $singleMonth) {
            $disponibilita = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI A REVOCA')->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese')->get()->first();
            if ($disponibilita !== null) {
                $disponbilitaPerMese[$disponibilita->mese] = $disponibilita->totAccordatoOperativo - $disponibilita->totUtilizzato;
            } else {
                $disponbilitaPerMese[$singleMonth['mese']] = 0;
            }
        }
        return $disponbilitaPerMese;
    }

    public function pesoDebitiBreveTermine($singleTrimestre)
    {
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );
        $incidenzaBreveTermine = array();
        foreach ($singleTrimestre as $singleMonth) {
            $affidamentiBreveTermineRevoca = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI A REVOCA')->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese')->get()->first();
            $affidamentiBreveTermineAutoliq = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI AUTOLIQUIDANTI')->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese')->get()->first();
            $affidamentiBreveTermineScadenza = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI A SCADENZA')->where('durata_residua', 'like', '%Fino a 1%')->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese')->get()->first();

            $totaleMensile = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")->whereIn('categoria', $categories)->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese')->get();
            $affidamentoMensile = 0;

            foreach ($totaleMensile as $singleTotale) {
                $affidamentoMensile += $singleTotale->totAccordatoOperativo;
            }
            $totaleMensile = $affidamentoMensile;

            $totaleMensileBreveTermine = 0;
            if ($affidamentiBreveTermineRevoca != null) {
                $totaleMensileBreveTermine += $affidamentiBreveTermineRevoca->totAccordatoOperativo;
            }
            if ($affidamentiBreveTermineAutoliq != null) {
                $totaleMensileBreveTermine += $affidamentiBreveTermineAutoliq->totAccordatoOperativo;
            }
            if ($affidamentiBreveTermineScadenza != null) {
                $totaleMensileBreveTermine += $affidamentiBreveTermineScadenza->totAccordatoOperativo;
            }
            // dump($totaleMensileBreveTermine, $totaleMensile);
            $incidenzaBreveTermine[$singleMonth['mese']] = ($totaleMensileBreveTermine / $totaleMensile) * 100;
        }
        // dd('');
        return $incidenzaBreveTermine;
    }

    public function verificaSegnalazioniGravi($singleTrimestre)
    {
        $sconfini = $this->getTotaleSconfiniTrimestrale($singleTrimestre);

        $creditiScadutiSconfinanti = array();

        foreach ($singleTrimestre as $singlePeriod) {
            $creditiScadutiSconfinanti[$singlePeriod['mese']] = cr::where('stato_rapporto', 'like', '%scaduti o sconfinanti%')->where('document_id', $this->_documentId)->where('anno', $singlePeriod['anno'])->where('mese', $singlePeriod['mese'])->get()->toArray();
        }

        unset($sconfini['Categorie'], $sconfini['SconfiniTotali'], $sconfini['SconfiniEntro90Giorni'], $sconfini['PresenzaSconfini'], $sconfini['Tensioni']);

        $importiSegnalazioniGravi = array();
        foreach ($sconfini as $tipoSconfino) {
            foreach ($tipoSconfino as $index => $singleSconfino) {
                foreach ($creditiScadutiSconfinanti as $singleMonth => $multipleCreditiScaduti) {
                    foreach ($multipleCreditiScaduti as $singleCreditoScaduto) {
                        if (
                            $singleSconfino->nome_banca == $singleCreditoScaduto->nome_banca &&
                            $singleSconfino->categoria == $singleCreditoScaduto->categoria &&
                            $singleSconfino->sezione == $singleCreditoScaduto->sezione &&
                            $singleSconfino->divisa == $singleCreditoScaduto->divisa &&
                            $singleSconfino->localizzazione == $singleCreditoScaduto->localizzazione
                        ) {
                            $importiSegnalazioniGravi[$singleCreditoScaduto->mese] = isset($importiSegnalazioniGravi[$singleCreditoScaduto->mese]) ? $importiSegnalazioniGravi[$singleCreditoScaduto->mese] + $singleCreditoScaduto->utilizzato : $singleCreditoScaduto->utilizzato;
                        }
                    }
                }
            }
        }

        $periods = $singleTrimestre;

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->where('categoria', 'Sofferenze');
            });
        }
        $sofferenzePerMese = array();
        $sofferenze = $CentraleRischiModel->get()->toArray();
        foreach ($sofferenze as $singolaSofferenza) {
            $sofferenzePerMese[$singolaSofferenza->mese] = isset($sofferenzePerMese[$singolaSofferenza->mese]) ? $sofferenzePerMese[$singolaSofferenza->mese] + $singolaSofferenza->utilizzato : $singolaSofferenza->utilizzato;
        }
        return array('Sofferenze' => $sofferenzePerMese, 'ScadSconf' => $importiSegnalazioniGravi);
    }


    public function getTotaleSconfiniTrimestrale($trimestre)
    {
        $cleanData = array();
        $periods = $trimestre;
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories) {
                $query->where($queryPeriodArray);
                $query->where('document_id', $this->_documentId);
                $query->whereRaw('CAST(accordato_operativo as SIGNED) < CAST(utilizzato as SIGNED)');
                $query->whereIn('categoria', $categories);
            });
        }
        $sconfiniTotali = $CentraleRischiModel->select(DB::raw('categoria, count(*) as sconfiniTotali'))
            ->groupBy(array('categoria'))
            ->get()
            ->toArray();

        foreach ($sconfiniTotali as $label => $data) {
            $cleanData['Categorie'][$data->categoria] = $data->sconfiniTotali;
        }

        $cleanData['SconfiniTotali'] = $sconfiniTotali;


        // Sconfini entro i 90 giorni

        $CentraleRischiModel = DB::table('crs as t');
        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories) {
                $query->where($queryPeriodArray)
                    ->whereIn('categoria', $categories)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date - interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    })->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 3 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa')
                            ->whereExists(function ($query) {
                                $query->select('*')
                                    ->from('crs as t3')
                                    ->whereRaw('t3.date = t2.date - interval 1 month AND CAST(t3.accordato_operativo as SIGNED) < CAST(t3.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
        AND t2.categoria = t3.categoria
        AND t2.mese != t3.mese
        AND t2.nome_banca = t3.nome_banca
        AND t2.localizzazione = t3.localizzazione
        AND t2.divisa = t3.divisa');
                            })->whereExists(function ($query) {
                                $query->select('*')
                                    ->from('crs as t3')
                                    ->whereRaw('t3.date = t2.date - interval 2 month AND CAST(t3.accordato_operativo as SIGNED) < CAST(t3.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
        AND t2.categoria = t3.categoria
        AND t2.mese != t3.mese
        AND t2.nome_banca = t3.nome_banca
        AND t2.localizzazione = t3.localizzazione
        AND t2.divisa = t3.divisa');
                            });
                    });
            });
        }
        $sconfiniEntro90Giorni = $CentraleRischiModel
            ->get()
            ->toArray();
        $cleanData['SconfiniEntro90Giorni'] = $sconfiniEntro90Giorni;


        // Sconfini oltre i 90 giorni ed entro i 180 giorni
        $CentraleRischiModel = DB::table('crs as t');
        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories) {
                $query->where($queryPeriodArray)
                    ->whereIn('categoria', $categories)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date - interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 2 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 3 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    })->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 5 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    });
            });
        }
        $sconfiniOltre90Giorni = $CentraleRischiModel
            ->get()
            ->toArray();
        $cleanData['SconfiniOltre90Giorni'] = $sconfiniOltre90Giorni;
        if (count($sconfiniOltre90Giorni) > 0) {
            $this->sconfiniOltre90 = true;
        }

        // Sconfini oltre i 180 giorni
        $CentraleRischiModel = DB::table('crs as t');
        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories) {
                $query->where($queryPeriodArray)
                    ->whereIn('categoria', $categories)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date - interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 2 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 3 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 4 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 5 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
    AND t.categoria = t2.categoria
    AND t.mese != t2.mese
    AND t.nome_banca = t2.nome_banca
    AND t.localizzazione = t2.localizzazione
    AND t.divisa = t2.divisa');
                    });
            });
        }
        $sconfiniOltre180Giorni = $CentraleRischiModel
            ->get()
            ->toArray();
        $cleanData['SconfiniOltre180Giorni'] = $sconfiniOltre180Giorni;
        if (count($sconfiniOltre180Giorni) > 0) {
            $this->_sconfiniOltre180 = true;
        }


        $cleanData['PresenzaSconfini'] = false;

        if ($cleanData['SconfiniTotali']) {
            $cleanData['PresenzaSconfini'] = true;
        }
        $banks = array();
        foreach ($trimestre as $singleMonth) {
            $singleMonthBanks = cr::select('nome_banca')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->distinct()->get()->toArray();
            foreach ($singleMonthBanks as $singolaBanca) {
                if (!in_array($singolaBanca['nome_banca'], $banks)) {
                    array_push($banks, $singolaBanca['nome_banca']);
                }
            }
        }

        $this->numeroSconfiniTotali = 0;
        $countBanks = count($banks);
        $allMonthsCount = count($trimestre);

        foreach ($cleanData['SconfiniTotali'] as $singleSconfino) {
            $this->numeroSconfiniTotali += $singleSconfino->sconfiniTotali;
            if ($countBanks == 1) {
                if ($allMonthsCount <= 12) {
                    if ($singleSconfino->sconfiniTotali >= 2) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 12) {
                    if ($singleSconfino->sconfiniTotali >= 5) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 24) {
                    if ($singleSconfino->sconfiniTotali >= 8) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                }
            } else if ($countBanks > 1) {
                if ($allMonthsCount <= 12) {
                    if ($singleSconfino->sconfiniTotali >= 3) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 12) {
                    if ($singleSconfino->sconfiniTotali >= 6) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                } else if ($allMonthsCount > 24) {
                    if ($singleSconfino->sconfiniTotali >= 12) {
                        $this->_tensioni[$singleSconfino->categoria] = true;
                    }
                }
            }
        }

        if (isset($this->_tensioni) && is_array($this->_tensioni)) {
            $cleanData['Tensioni'] = $this->_tensioni;
        }

        $cleanData['SconfiniTotali'] = $this->numeroSconfiniTotali;

        return $cleanData;
    }

    public function verificaGaranzie($singleTrimestre)
    {
        $infoGaranti = array();
        $garanzieRicevute = array();


        foreach ($singleTrimestre as $label => $singleMonth) {
            $garanti = cr::where('Sezione', 'Garanti')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->get();
            $infoGarantiMensile = array('Importo Garantito' => 0, 'Garanzia' => 0);
            foreach ($garanti as $singleGaranzia) {
                $infoGarantiMensile['Importo Garantito'] += (float)$singleGaranzia->garanzia;
                $infoGarantiMensile['Garanzia'] += (float)$singleGaranzia->importo_garantito;
            }
            $infoGaranti[$singleMonth['mese']] = $infoGarantiMensile;

            $garanzie = cr::where('Sezione', 'Garanzie')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->get();
            $garanzieMensili = array('Importo Garantito' => 0, 'Garanzia' => 0);
            foreach ($garanzie as $singleGaranziaRicevuta) {
                $garanzieMensili['Importo Garantito'] += (float)$singleGaranziaRicevuta->importo_garantito;
                $garanzieMensili['Garanzia'] += (float)$singleGaranziaRicevuta->garanzia;
            }
            $garanzieRicevute[$singleMonth['mese']] = $garanzieMensili;
        }
        return array('InfoGaranti' => $infoGaranti, 'GaranzieRicevute' => $garanzieRicevute);
    }

    public function usoAffidamenti($singleTrimestre)
    {
        $arrayAffidamenti = array();
        $categories = array('RISCHI AUTOLIQUIDANTI', 'RISCHI A REVOCA', 'RISCHI A SCADENZA');
        $breveTerminePerBanca = array();
        $MLTPerBanca = array();

        foreach ($singleTrimestre as $singleMonth) {
            $arrayAffidamenti[$singleMonth['mese']] = array();
            $banks = cr::select('nome_banca')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->distinct()->get();
            foreach ($categories as $singolaCategoria) {
                $totSaldoMedio[$singleMonth['mese']][$singolaCategoria] = 0;
                $totaliMensili[$singleMonth['mese']][$singolaCategoria] = array('Utilizzato' => 0, 'Accordato' => 0);

                $totaleBreve[$singleMonth['mese']][$singolaCategoria] = array('Accordato' => 0, 'Utilizzato' => 0);
                $totaleMLT[$singleMonth['mese']][$singolaCategoria] = array('Accordato' => 0, 'Utilizzato' => 0);
                foreach ($banks as $singolaBanca) {

                    $saldoMedioMensile = 0;

                    $tipo = '';
                    $flagBreveTermine = false;
                    $flagMedioLungoTermine = false;
                    $affidamenti = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato, SUM(saldo_medio) as saldomedio,categoria, anno, mese")->where('document_id', $this->_documentId)->where('nome_banca', $singolaBanca->nome_banca)->where('categoria', $singolaCategoria)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese', 'nome_banca')->get()->first();
                    if ($singolaCategoria == 'RISCHI A REVOCA') {
                        $saldoMedio = cr::select('saldo_medio', 'nome_banca', 'categoria')->where('document_id', $this->_documentId)->where('nome_banca', $singolaBanca->nome_banca)->where('categoria', $singolaCategoria)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->where('saldo_medio', '<>', 'N/A')->where('saldo_medio', '<>', '0')->get()->toArray();
                        foreach ($saldoMedio as $singleSaldo) {
                            if ($singleSaldo['saldo_medio'] !== "") {
                                $singleSaldo = (float)str_replace('.', '', $singleSaldo['saldo_medio']);
                                $saldoMedioMensile += $singleSaldo;
                                $totSaldoMedio[$singleMonth['mese']][$singolaCategoria] += $singleSaldo;
                            }
                        }
                    }

                    if ($affidamenti !== null) {
                        $totaliMensili[$singleMonth['mese']][$singolaCategoria]['Accordato'] += $affidamenti->totAccordatoOperativo;
                        $totaliMensili[$singleMonth['mese']][$singolaCategoria]['Utilizzato'] += $affidamenti->totUtilizzato;
                        if ((float)$affidamenti->totAccordatoOperativo != 0) {
                            if ($singolaCategoria == 'RISCHI A SCADENZA') {
                                $breveTerminePerBanca[$singleMonth['mese']][$singolaBanca->nome_banca][$singolaCategoria] = array('Utilizzato' => 0, 'Accordato' => 0, 'Percentuale' => 0);
                                $MLTPerBanca[$singleMonth['mese']][$singolaBanca->nome_banca][$singolaCategoria] = array('Utilizzato' => 0, 'Accordato' => 0, 'Percentuale' => 0);

                                $durate = cr::where('nome_banca', $singolaBanca->nome_banca)->where('document_id', $this->_documentId)->where('categoria', $singolaCategoria)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->distinct()->get();
                                foreach ($durate as $singolaDurata) {
                                    if (str_contains($singolaDurata->durata_residua, 'Fino a 1') || str_contains($singolaDurata->durata_residua, 'Fino a 1 anno')) {
                                        $totaleBreve[$singleMonth['mese']][$singolaCategoria]['Accordato'] += $singolaDurata->accordato_operativo;
                                        $totaleBreve[$singleMonth['mese']][$singolaCategoria]['Utilizzato'] += $singolaDurata->utilizzato;

                                        $breveTerminePerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Accordato'] += $singolaDurata->accordato_operativo;
                                        $breveTerminePerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Utilizzato'] += $singolaDurata->utilizzato;


                                        if (!$flagBreveTermine) {
                                            $tipo .= 'Breve ';
                                            $flagBreveTermine = true;
                                        }
                                    } else {
                                        $totaleMLT[$singleMonth['mese']][$singolaCategoria]['Accordato'] += $singolaDurata->accordato_operativo;
                                        $totaleMLT[$singleMonth['mese']][$singolaCategoria]['Utilizzato'] += $singolaDurata->utilizzato;

                                        $MLTPerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Accordato'] += $singolaDurata->accordato_operativo;
                                        $MLTPerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Utilizzato'] += $singolaDurata->utilizzato;

                                        if (!$flagMedioLungoTermine) {
                                            $tipo .= 'MLT';
                                            $flagMedioLungoTermine = true;
                                        }
                                    }
                                }
                                if ($breveTerminePerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Accordato'] != 0) {
                                    $breveTerminePerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Percentuale'] = ($breveTerminePerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Utilizzato'] / $breveTerminePerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Accordato']) * 100;
                                } else {
                                    $breveTerminePerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Percentuale'] = 100;
                                }
                                if ($MLTPerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Accordato'] != 0) {
                                    $MLTPerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Percentuale'] = ($MLTPerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Utilizzato'] / $MLTPerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Accordato']) * 100;
                                } else {
                                    $MLTPerBanca[$singleMonth['mese']][$singolaDurata->nome_banca][$singolaCategoria]['Percentuale'] = 100;
                                }

                                $arrayAffidamenti[$singleMonth['mese']][$singolaBanca->nome_banca][$singolaCategoria] = array('Utilizzato' => (float)$affidamenti->totUtilizzato, 'Accordato' => (float)$affidamenti->totAccordatoOperativo, 'Percentuale' => ((float)$affidamenti->totUtilizzato / (float)$affidamenti->totAccordatoOperativo) * 100, 'SaldoMedio' => $saldoMedioMensile, 'Tipo' => $tipo);
                            } else {
                                $arrayAffidamenti[$singleMonth['mese']][$singolaBanca->nome_banca][$singolaCategoria] = array('Utilizzato' => (float)$affidamenti->totUtilizzato, 'Accordato' => (float)$affidamenti->totAccordatoOperativo, 'Percentuale' => ((float)$affidamenti->totUtilizzato / (float)$affidamenti->totAccordatoOperativo) * 100, 'SaldoMedio' => $saldoMedioMensile);
                            }
                        } else {
                            if ((float)$affidamenti->totAccordatoOperativo == 0) {
                                $arrayAffidamenti[$singleMonth['mese']][$singolaBanca->nome_banca][$singolaCategoria] = array('Utilizzato' => (float)$affidamenti->totUtilizzato, 'Accordato' => (float)$affidamenti->totAccordatoOperativo, 'Percentuale' => 100, 'SaldoMedio' => $saldoMedioMensile, 'skippable' => true);
                            } else {
                                $arrayAffidamenti[$singleMonth['mese']][$singolaBanca->nome_banca][$singolaCategoria] = array('Utilizzato' => (float)$affidamenti->totUtilizzato, 'Accordato' => (float)$affidamenti->totAccordatoOperativo, 'Percentuale' => ((float)$affidamenti->totUtilizzato / (float)$affidamenti->totAccordatoOperativo) * 100, 'SaldoMedio' => $saldoMedioMensile, 'skippable' => true);
                            }
                        }
                    } else {
                        $arrayAffidamenti[$singleMonth['mese']][$singolaBanca->nome_banca][$singolaCategoria] = array('Utilizzato' => 0, 'Accordato' => 0, 'Percentuale' => 0, 'SaldoMedio' => $saldoMedioMensile, 'skippable' => true);
                    }
                }
            }
        }
        return array('DettaglioPerTipo' => array('Breve' => $breveTerminePerBanca, 'MLT' => $MLTPerBanca), 'Dettaglio' => $arrayAffidamenti, 'Mensile' => array('Breve' => $totaleBreve, 'MLT' => $totaleMLT, 'SaldoMedio' => $totSaldoMedio, 'Totali' => $totaliMensili));
    }

    public function analisiSconfini($singleTrimestre)
    {
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
        );
        $analisiSconfiniData = array();
        $datiMensiliTotali = array();

        foreach ($singleTrimestre as $singleMonth) {
            $banks = cr::select('nome_banca')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->distinct()->get();
            foreach ($banks as $singolaBanca) {
                foreach ($categories as $singolaCategoria) {
                    $affidamenti = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese, nome_banca")->where('categoria', $singolaCategoria)->where('document_id', $this->_documentId)->where('nome_banca', $singolaBanca->nome_banca)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese', 'nome_banca')->get()->first();
                    if ($affidamenti !== null && ((float)$affidamenti->totAccordatoOperativo !== 0 && (float)$affidamenti->totUtilizzato !== 0)) {
                        if ((float)$affidamenti->totAccordatoOperativo > (float)$affidamenti->totUtilizzato) {
                            $analisiSconfiniData[$singleMonth['mese']][$singolaBanca->nome_banca][$singolaCategoria] = array(
                                'Utilizzato' => (float)$affidamenti->totUtilizzato,
                                'Accordato' => (float)$affidamenti->totAccordatoOperativo,
                                'Accordato - Utilizzato' => ((float)$affidamenti->totAccordatoOperativo - (float)$affidamenti->totUtilizzato),
                                'Sconfino' => 0
                            );
                        } else {
                            $analisiSconfiniData[$singleMonth['mese']][$singolaBanca->nome_banca][$singolaCategoria] = array(
                                'Utilizzato' => (float)$affidamenti->totUtilizzato,
                                'Accordato' => (float)$affidamenti->totAccordatoOperativo,
                                'Accordato - Utilizzato' => ((float)$affidamenti->totAccordatoOperativo - (float)$affidamenti->totUtilizzato),
                                'Sconfino' => (float)$affidamenti->totUtilizzato - (float)$affidamenti->totAccordatoOperativo
                            );
                        }
                    }
                    if (!isset($datiMensiliTotali[$singleMonth['mese']][$singolaCategoria])) {
                        $totMensile = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")->where('document_id', $this->_documentId)->where('categoria', $singolaCategoria)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese')->get()->first();
                        if ($totMensile !== null) {
                            $datiMensiliTotali[$singleMonth['mese']][$singolaCategoria] = (float)$totMensile->totAccordatoOperativo - (float)$totMensile->totUtilizzato;
                        } else {
                            $datiMensiliTotali[$singleMonth['mese']][$singolaCategoria] = 0;
                        }
                    }
                }
            }
        }

        $conteggioSconfini = array();

        foreach ($singleTrimestre as $periods) {

            $conteggioSconfini[$periods['mese']] = array('RISCHI A REVOCA' => 0, 'RISCHI AUTOLIQUIDANTI' => 0, 'RISCHI A SCADENZA' => 0);
            $categories = array(
                'RISCHI A SCADENZA',
                'RISCHI AUTOLIQUIDANTI',
                'RISCHI A REVOCA',
            );

            $CentraleRischiModel = DB::table('crs');

            $CentraleRischiModel->orWhere(function ($query) use ($periods, $categories) {
                $query->where($periods);
                $query->where('document_id', $this->_documentId);
                $query->whereRaw('CAST(accordato_operativo as SIGNED) < CAST(utilizzato as SIGNED)');
                $query->whereIn('categoria', $categories);
            });

            $sconfiniTotali = $CentraleRischiModel
                ->orderBy('date')
                ->get()
                ->toArray();

            foreach ($sconfiniTotali as $singleSconfino) {
                $conteggioSconfini[$singleSconfino->mese][$singleSconfino->categoria] += $singleSconfino->accordato_operativo - $singleSconfino->utilizzato;
            }
        }
        return array('DatiSconfini' => $analisiSconfiniData, 'DatiMensiliTotali' => $datiMensiliTotali, 'ConteggioSconfini' => $conteggioSconfini);
    }

    public function creditiRischi($singleTrimestre)
    {
        $arrayCreditiScaduti = array();

        foreach ($singleTrimestre as $singleMonth) {
            $arrayCreditiScaduti['Mensile'][$singleMonth['mese']] = array('Scaduti' => 0, 'Impagati' => 0, 'Differenza' => 0, 'Percentuale' => 0);
            $banks = cr::select('nome_banca')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->distinct()->get();
            foreach ($banks as $singolaBanca) {
                $arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca] = array('Scaduti' => 0, 'Impagati' => 0, 'Differenza' => 0, 'Percentuale' => 0);
                $scaduti = cr::where('categoria', 'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI')->where('document_id', $this->_documentId)->where('nome_banca', $singolaBanca->nome_banca)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->get();
                foreach ($scaduti as $singleScaduto) {
                    $arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Scaduti'] += (float)$singleScaduto->importo_garantito;
                    $arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Scaduti'] += (float)$singleScaduto->importo_garantito;
                    if ($singleScaduto->stato_rapporto == 'Crediti impagati') {
                        $arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Impagati'] += (float)$singleScaduto->importo_garantito;
                        $arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Impagati'] += (float)$singleScaduto->importo_garantito;
                    }
                }

                if ($arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Scaduti'] != 0) {
                    if ($arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Impagati'] != 0) {
                        $arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Percentuale'] = 100 * ($arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Impagati'] / $arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Scaduti']);
                    } else {
                        $arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Percentuale'] = 0;
                    }
                } else {
                    $arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Percentuale'] = 0;
                }

                if ($arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Scaduti'] != 0) {
                    if ($arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Impagati'] != 0) {
                        $arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Percentuale'] = 100 * ($arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Impagati'] / $arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Scaduti']);
                    } else {
                        $arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Percentuale'] = 0;
                    }
                } else {
                    $arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Percentuale'] = 0;
                }

                $affidamentoRevoca = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato, anno, mese, nome_banca")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI A REVOCA')->where('nome_banca', $singolaBanca->nome_banca)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('anno', 'mese', 'nome_banca')->get()->first();
                if ($affidamentoRevoca !== null) {
                    $arrayCreditiScaduti['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Differenza'] = (float)$affidamentoRevoca->totAccordatoOperativo - (float)$affidamentoRevoca->totUtilizzato;
                }
            }
            $affidamentoRevoca = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato, anno, mese")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI A REVOCA')->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('anno', 'mese')->get()->first();
            if ($affidamentoRevoca !== null) {
                $arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Differenza'] = (float)$affidamentoRevoca->totAccordatoOperativo - (float)$affidamentoRevoca->totUtilizzato;
            }
            if (count($scaduti) !== 0) {
                $arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Percentuale'] = ($arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Impagati'] / $arrayCreditiScaduti['Mensile'][$singleMonth['mese']]['Scaduti']) * 100;
            }
        }

        $arraySituazioniRischio = array();

        $sconfini = $this->getTotaleSconfiniTrimestrale($singleTrimestre);

        $creditiScadutiSconfinanti = array();
        foreach ($singleTrimestre as $singleMonth) {
            $arraySituazioniRischio['Mensile'][$singleMonth['mese']] = array('ScadSconfEntro180' => 0, 'ScadSconfOltre180' => 0, 'Sofferenze' => 0, 'Contestati' => 0, 'CreditiPerdita' => 0);
            $banks = cr::select('nome_banca')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->distinct()->get();
            foreach ($banks as $singolaBanca) {
                $arraySituazioniRischio['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['ScadSconfEntro180'] = 0;
                $arraySituazioniRischio['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['ScadSconfOltre180'] = 0;
                $scadSconf = cr::where('stato_rapporto', 'like', '%scad o sconf%')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->where('nome_banca', $singolaBanca->nome_banca)->get();
                foreach ($scadSconf as $singleScadSconf) {
                    if (strpos($singleScadSconf->stato_rapporto, '90gg') !== false) {
                        $arraySituazioniRischio['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['ScadSconfEntro180'] += (float)str_replace('.', '', $singleScadSconf->importo_garantito);
                        $arraySituazioniRischio['Mensile'][$singleMonth['mese']]['ScadSconfEntro180'] += (float)str_replace('.', '', $singleScadSconf->importo_garantito);
                    } else {
                        $arraySituazioniRischio['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['ScadSconfOltre180'] += (float)str_replace('.', '', $singleScadSconf->importo_garantito);
                        $arraySituazioniRischio['Mensile'][$singleMonth['mese']]['ScadSconfOltre180'] += (float)str_replace('.', '', $singleScadSconf->importo_garantito);
                    }
                }
                $arraySituazioniRischio['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Sofferenze'] = 0;

                $sofferenze = cr::where('categoria', 'Sofferenze')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->where('nome_banca', $singolaBanca->nome_banca)->get();
                foreach ($sofferenze as $singolaSofferenza) {
                    $arraySituazioniRischio['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Sofferenze'] += (float)str_replace('.', '', $singolaSofferenza->importo_garantito);
                    $arraySituazioniRischio['Mensile'][$singleMonth['mese']]['Sofferenze'] += (float)str_replace('.', '', $singolaSofferenza->importo_garantito);
                }

                $arraySituazioniRischio['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['CreditiPerdita'] = 0;

                $creditiPerdita = cr::where('categoria', 'SOFFERENZE - CREDITI PASSATI A PERDITA')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->where('nome_banca', $singolaBanca->nome_banca)->get();
                foreach ($creditiPerdita as $singleCreditoPerdita) {
                    $arraySituazioniRischio['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['CreditiPerdita'] += (float)str_replace('.', '', $singleCreditoPerdita->importo_garantito);
                    $arraySituazioniRischio['Mensile'][$singleMonth['mese']]['CreditiPerdita'] += (float)str_replace('.', '', $singleCreditoPerdita->importo_garantito);
                }

                $arraySituazioniRischio['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Contestati'] = 0;
                $creditiContestati = cr::where('stato_rapporto', 'like', '%contestati%')->where('document_id', $this->_documentId)->where('stato_rapporto', 'not like', '%non contestati%')->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->where('nome_banca', $singolaBanca->nome_banca)->get();
                foreach ($creditiContestati as $singleCreditoContestato) {
                    $arraySituazioniRischio['Dettaglio'][$singleMonth['mese']][$singolaBanca->nome_banca]['Contestati'] += (float)str_replace('.', '', $singleCreditoContestato->importo_garantito);
                    $arraySituazioniRischio['Mensile'][$singleMonth['mese']]['Contestati'] += (float)str_replace('.', '', $singleCreditoContestato->importo_garantito);
                }
            }
        }
        return array('CreditiScaduti' => $arrayCreditiScaduti, 'SituazioniRischio' => $arraySituazioniRischio);
    }

    public function getCreditiScadutiBreveTermine($singleTrimestre)
    {
        $creditiScaduti = array();
        $categories = array('RISCHI AUTOLIQUIDANTI', 'RISCHI A REVOCA', 'RISCHI A SCADENZA');
        $mensile = array();

        foreach ($singleTrimestre as $singleMonth) {
            $mensile[$singleMonth['mese']] = array('RISCHI AUTOLIQUIDANTI' => 0, 'RISCHI A REVOCA' => 0, 'RISCHI A SCADENZA' => 0, 'AccordatoTotale' => 0, 'PesoDebitiBreveTermine' => 0);
            $totaleUtilizzatoMensile = 0;
            $banks = cr::select('nome_banca')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->distinct()->get();

            foreach ($banks as $singolaBanca) {
                $creditiScaduti[$singleMonth['mese']][$singolaBanca->nome_banca] = array('RISCHI A REVOCA' => 0, 'RISCHI AUTOLIQUIDANTI' => 0, 'RISCHI A SCADENZA' => 0, 'AccordatoTotale' => 0, 'PesoDebitiBreveTermine' => 0);
                $affidamentiBreveTermineRevoca = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI A REVOCA')->where('anno', $singleMonth['anno'])->where('nome_banca', $singolaBanca->nome_banca)->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese')->get()->first();
                $affidamentiBreveTermineAutoliq = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI AUTOLIQUIDANTI')->where('anno', $singleMonth['anno'])->where('nome_banca', $singolaBanca->nome_banca)->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese')->get()->first();
                $affidamentiBreveTermineScadenza = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")->where('document_id', $this->_documentId)->where('categoria', 'RISCHI A SCADENZA')/*->where('durata_residua', 'like', '%Fino a 1%')*/->where('nome_banca', $singolaBanca->nome_banca)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese')->get()->first();

                $affidamenti = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato,categoria, anno, mese")->whereIn('categoria', $categories)->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('nome_banca', $singolaBanca->nome_banca)->where('mese', $singleMonth['mese'])->groupBy('categoria', 'anno', 'mese')->get();

                $totaleUtilizzato = 0;

                foreach ($affidamenti as $singleAffidamento) {

                    $creditiScaduti[$singleMonth['mese']][$singolaBanca->nome_banca]['AccordatoTotale'] += $singleAffidamento->totAccordatoOperativo;
                    $mensile[$singleMonth['mese']]['AccordatoTotale'] += $singleAffidamento->totAccordatoOperativo;
                }

                if ($affidamentiBreveTermineAutoliq !== null) {
                    $creditiScaduti[$singleMonth['mese']][$singolaBanca->nome_banca]['RISCHI AUTOLIQUIDANTI'] = $affidamentiBreveTermineAutoliq->totUtilizzato;
                    $totaleUtilizzato += $affidamentiBreveTermineAutoliq->totUtilizzato;

                    $mensile[$singleMonth['mese']]['RISCHI AUTOLIQUIDANTI'] += $affidamentiBreveTermineAutoliq->totUtilizzato;
                    $totaleUtilizzatoMensile += $affidamentiBreveTermineAutoliq->totUtilizzato;
                }

                if ($affidamentiBreveTermineRevoca !== null) {
                    $creditiScaduti[$singleMonth['mese']][$singolaBanca->nome_banca]['RISCHI A REVOCA'] = $affidamentiBreveTermineRevoca->totUtilizzato;
                    $totaleUtilizzato += $affidamentiBreveTermineRevoca->totUtilizzato;

                    $mensile[$singleMonth['mese']]['RISCHI A REVOCA'] += $affidamentiBreveTermineRevoca->totUtilizzato;
                    $totaleUtilizzatoMensile += $affidamentiBreveTermineRevoca->totUtilizzato;
                }

                if ($affidamentiBreveTermineScadenza !== null) {
                    $creditiScaduti[$singleMonth['mese']][$singolaBanca->nome_banca]['RISCHI A SCADENZA'] = $affidamentiBreveTermineScadenza->totUtilizzato;
                    $totaleUtilizzato += $affidamentiBreveTermineScadenza->totUtilizzato;

                    $mensile[$singleMonth['mese']]['RISCHI A SCADENZA'] += $affidamentiBreveTermineScadenza->totUtilizzato;
                    $totaleUtilizzatoMensile += $affidamentiBreveTermineScadenza->totUtilizzato;
                }
                if ($creditiScaduti[$singleMonth['mese']][$singolaBanca->nome_banca]['AccordatoTotale'] != 0) {
                    $creditiScaduti[$singleMonth['mese']][$singolaBanca->nome_banca]['PesoDebitiBreveTermine'] = ($totaleUtilizzato / $creditiScaduti[$singleMonth['mese']][$singolaBanca->nome_banca]['AccordatoTotale']) * 100;
                }
            }
            if ($mensile[$singleMonth['mese']]['AccordatoTotale'] !== 0) {
                $mensile[$singleMonth['mese']]['PesoDebitiBreveTermine'] = ($totaleUtilizzatoMensile / $mensile[$singleMonth['mese']]['AccordatoTotale']) * 100;
            }
        }

        return array('Dettaglio' => $creditiScaduti, 'Mensile' => $mensile);
    }

    public function getInformazioniGarantiTrimestrale($singleTrimestre)
    {
        $arrayInfoGaranti = array();
        $datiGarantiMensili = array();

        foreach ($singleTrimestre as $singleMonth) {
            $datiGarantiMensili[$singleMonth['mese']] = array('Importo' => 0, 'Garanzia' => 0);

            $banks = cr::select('nome_banca')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('mese', $singleMonth['mese'])->distinct()->get();
            foreach ($banks as $singolaBanca) {
                $arrayInfoGaranti[$singleMonth['mese']][$singolaBanca->nome_banca] = array('Importo' => 0, 'Garanzia' => 0);
                $garanti = cr::where('sezione', 'Garanti')->where('document_id', $this->_documentId)->where('anno', $singleMonth['anno'])->where('nome_banca', $singolaBanca->nome_banca)->where('mese', $singleMonth['mese'])->get();
                foreach ($garanti as $singleInfoGaranti) {
                    if ($singleInfoGaranti !== null) {
                        $arrayInfoGaranti[$singleMonth['mese']][$singolaBanca->nome_banca]['Importo'] += (float)$singleInfoGaranti->garanzia;
                        $arrayInfoGaranti[$singleMonth['mese']][$singolaBanca->nome_banca]['Garanzia'] += (float)$singleInfoGaranti->importo_garantito;

                        $datiGarantiMensili[$singleMonth['mese']]['Importo'] += (float)$singleInfoGaranti->garanzia;
                        $datiGarantiMensili[$singleMonth['mese']]['Garanzia'] += (float)$singleInfoGaranti->importo_garantito;
                    }
                }
            }
        }
        return array('Dettaglio' => $arrayInfoGaranti, 'Mensile' => $datiGarantiMensili);
    }


    public function informazioniSuiGaranti($informazioniGaranti)
    {
        $informazioniGarantiAnomalie = [];
        foreach ($informazioniGaranti['Anomalie'] as $nomeBanca => $multipleDates) {
            foreach ($multipleDates as $singleDate => $multipleTypes) {
                foreach ($multipleTypes as $singleType => $multipleAnomalie) {
                    $multipleAnomalie = array_unique($multipleAnomalie);
                    foreach ($multipleAnomalie as $singleAnomalia => $tmp) {
                        $informazioniGarantiAnomalie[] = [
                            'data' => $singleDate,
                            'nome_banca' => $nomeBanca,
                            'type' => $singleType,
                            'tmp' => $tmp
                        ];
                    }
                }
            }
        }

        return $informazioniGarantiAnomalie;
    }

    public function singleBankData($banks, $periods)
    {
        $banksScoring = [];
        foreach ($banks as $label => $nameData) {
            $newCrExtractor = new newCrExtractor;
            $newCrExtractor->setPeriod($periods);
            $singleBankData[$nameData]['totaleSconfini'] = $newCrExtractor->getTotaleSconfini(array(0 => $nameData));
            $singleBankData[$nameData]['Impagati'] = $newCrExtractor->getImpagati(array(0 => $nameData));
            $newCrExtractor->getAlertImpagati(array(0 => $nameData));
            $singleBankData[$nameData]['Sofferenze'] = $newCrExtractor->getSofferenze(array(0 => $nameData));
            $singleBankData[$nameData]['Crediti a perdita'] = $newCrExtractor->getCreditiPassatiPerdita(array(0 => $nameData));
            $banksScoring[$nameData] = $newCrExtractor->getScoring(array(0 => $nameData));
            if (!empty($singleBankData['totaleSconfini']['Tensioni'])) {
                foreach ($singleBankData[$nameData]['totaleSconfini']['Tensioni'] as $creditLine => $presence) {
                    $presenzaTensione[$creditLine] = true;
                }
            }
            unset($newCrExtractor);
        }

        return $banksScoring;
    }

    public function percentualiUtilizzato($totAffidamentiConPesiPerBanca)
    {
        $percentualiUtilizzato = [];
        foreach ($totAffidamentiConPesiPerBanca as $label => $data) {
            if (isset($data['PesoUtilizzato'])) {
                $percentualiUtilizzato[] = array("label" => $data['nome_banca'], "y" => $data['PesoUtilizzato']);
            } else {
                $percentualiUtilizzato[] = array("label" => $data['nome_banca'], "y" => 0);
            }
        }

        return $percentualiUtilizzato;
    }

    public function percentualiAccordato($totAffidamentiConPesiPerBanca)
    {
        $percentualiAccordato = [];
        foreach ($totAffidamentiConPesiPerBanca as $label => $data) {
            if (isset($data['PesoAccordatoOperativo'])) {
                $percentualiAccordato[] = array("label" => $data['nome_banca'], "y" => $data['PesoAccordatoOperativo']);
            } else {
                $percentualiAccordato[] = array("label" => $data['nome_banca'], "y" => 0);
            }
        }

        return $percentualiAccordato;
    }

    public function missingMonths($unrefinedPeriods, $crAndamentaleData)
    {
        $missingMonths = [];
        $periods = [];
        $counter = 0;
        $mesiCheckList = [0 => "fuoriMese", 1 => "gennaio", 2 => 'febbraio', 3 => 'marzo', 4 => 'aprile', 5 => 'maggio', 6 => 'giugno', 7 => 'luglio', 8 => "agosto", 9 => 'settembre', 10 => 'ottobre', 11 => 'novembre', 12 => 'dicembre'];

        foreach ($unrefinedPeriods as $label => $data) {
            $periods[$data['anno']][$data['mese']] = 1;
            $counter++;

            if ($counter < count($unrefinedPeriods)) {
                $tempDate = new DateTime($data['date']);
                if (cr::where([['date', '>=', $tempDate->modify('+1 month')->format('Y-m-01')], ['date', '<=', $tempDate->format('Y-m-0t')]])->where('document_id', $crAndamentaleData['period'])->groupBy('date')->count() == 0) {
                    $missingMonths[] = $mesiCheckList[(float)$tempDate->format('m')] . ' ' . $tempDate->format('Y');
                }
            }
        }

        return $missingMonths;
    }

    public function getCleanPeriods($unrefinedPeriods)
    {

        $periods = [];

        foreach ($unrefinedPeriods as $label => $data) {
            $periods[$data['anno']][$data['mese']] = 1;
        }

        return $periods;
    }

    public function totAffidamentiConPesiPerBanca($totAffidamentiConPesiPerBanca)
    {
        $totaleAccordatoGeneral = 0;
        $totaleUtilizzatoGeneral = 0;
        foreach ($totAffidamentiConPesiPerBanca as $singleBank) {
            $totaleAccordatoGeneral = $totAffidamentiConPesiPerBanca[0]['totAccordatoOperativo'] + $singleBank['totAccordatoOperativo'];
            $totaleUtilizzatoGeneral = $totAffidamentiConPesiPerBanca[0]['totUtilizzato'] + $singleBank['totUtilizzato'];
        }

        return array("totaleAccordatoGeneral" => $totaleAccordatoGeneral, "totaleUtilizzatoGeneral" => $totaleUtilizzatoGeneral);
    }

    public function getGeneratedbanks($inputBanks, $crAndamentaleData, $periodsCorrect, $categories)
    {
        $banksQuery = DB::table('crs');
        $banks = [];
        foreach ($periodsCorrect as $queryPeriodArray) {
            $banksQuery->orWhere(function ($query) use ($queryPeriodArray, $categories, $crAndamentaleData) {
                $query->where('document_id', $crAndamentaleData['period']);
                $query->where($queryPeriodArray);
            });
        }


        if ($inputBanks !== null) {
            $banks = $inputBanks;
        } else {

            $banksData = $banksQuery
                ->get()
                ->groupBy('nome_banca')
                ->toArray();



            foreach ($banksData as $singleBankName => $arrayData) {
                $banks[] = $singleBankName;
            }
        }
        return $banks;
    }
}
