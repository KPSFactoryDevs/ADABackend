<?php


namespace App\Helpers\CentraleRischi;

use App\Http\Requests;
use App;
use App\Models\Bilanci;
use App\Models\Account;
use App\Models\cr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class newCrExtractor
{
    public $_period = false;
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

    public function getCountBanks()
    {
        if ($this->_countBanks) {
            return $this->_countBanks;
        }

        $data = $this->getAllData()->get()->groupBy(array('nome_banca'))->toArray();
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

    public function getAllData()
    {
        $queryPeriodArray = array();
        $CentraleRischiModel = DB::table('crs');
        $periods = $this->buildPeriodArray();

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray) {
                $query->where($queryPeriodArray);
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

    public function getAllDataToArray()
    {
        $data = $this->getAllData()->get()->groupBy(array('anno', 'mese', 'nome_banca', 'sezione', 'categoria'))->toArray();
        $data = json_decode(json_encode($data), true);


        return $data;
    }

    public function getTotaleSconfini($bank)
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
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $bank) {
                $query->where($queryPeriodArray);
                $query->where('nome_banca', '=', $bank);
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
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $bank) {
                $query->where($queryPeriodArray)
                    ->where('nome_banca', '=', $bank)
                    ->whereIn('categoria', $categories)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date - interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 3 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa')
                            ->whereExists(function ($query) {
                                $query->select('*')
                                    ->from('crs as t3')
                                    ->whereRaw('t3.date = t2.date - interval 1 month AND CAST(t3.accordato_operativo as SIGNED) < CAST(t3.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
	AND t2.categoria = t3.categoria
	AND t2.mese != t3.mese
	AND t2.localizzazione = t3.localizzazione
	AND t2.divisa = t3.divisa');
                            })->whereExists(function ($query) {
                                $query->select('*')
                                    ->from('crs as t3')
                                    ->whereRaw('t3.date = t2.date - interval 2 month AND CAST(t3.accordato_operativo as SIGNED) < CAST(t3.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
	AND t2.categoria = t3.categoria
	AND t2.mese != t3.mese
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
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $bank) {
                $query->where($queryPeriodArray)
                    ->where('nome_banca', $bank)
                    ->whereIn('categoria', $categories)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date - interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 2 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 3 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 5 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
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
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $bank) {
                $query->where($queryPeriodArray)
                    ->where('nome_banca', $bank)
                    ->whereIn('categoria', $categories)
                    ->whereRaw('CAST(t.accordato_operativo as SIGNED) < CAST(t.utilizzato as SIGNED)')
                    ->whereNotExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date - interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 1 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 2 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 3 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 4 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
AND t.localizzazione = t2.localizzazione
AND t.divisa = t2.divisa');
                    })->whereExists(function ($query) {
                        $query->select('*')
                            ->from('crs as t2')
                            ->whereRaw('t2.date = t.date + interval 5 month AND CAST(t2.accordato_operativo as SIGNED) < CAST(t2.utilizzato as SIGNED) AND t.accordato_operativo = t2.accordato_operativo
AND t.categoria = t2.categoria
AND t.mese != t2.mese
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

        $this->numeroSconfiniTotali = 0;
        $countBanks = $this->getCountBanks();
        $allMonthsCount = $this->getCountMonths();
        foreach ($cleanData['SconfiniTotali'] as $singleSconfino) {
            $this->numeroSconfiniTotali += $singleSconfino->sconfiniTotali;
            if ($countBanks = 1) {
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
    public function getAlertImpagati($bank)
    {
        $totaleImpagati = count($this->getImpagati($bank));
        $allMonthsCount = $this->getCountMonths();
        $countBanks = 1;

        if ($countBanks = 1) {
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
    public function getScoring($bank)
    {
        $allMonthsCount = $this->getCountMonths();
        $countBanks = 1;
        $sconfini = $this->getTotaleSconfini($bank);

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

        $alertImpagati = $this->getAlertImpagati($bank);

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
        $sofferenze = count($this->getSofferenze($bank));
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
        $creditiPerdita = count($this->getCreditiPassatiPerdita($bank));
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
        $garanzieEsitoNegativo = $this->getGaranzieEsitoNegativo($bank);

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

    public function getImpagati($bank)
    {
        $periods = $this->buildPeriodArray();
        $categories = array(
            'RISCHI A SCADENZA',
            'RISCHI AUTOLIQUIDANTI',
            'RISCHI A REVOCA',
            'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI',
        );

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($bank, $queryPeriodArray, $categories) {
                $query->where('nome_banca', $bank);
                $query->where($queryPeriodArray);
                $query->where('stato_rapporto', 'Crediti impagati');
                $query->whereIn('categoria', $categories);
            });
        }
        $impagati = $CentraleRischiModel
            ->get()
            ->toArray();

        $this->_impagati = $impagati;

        return $this->_impagati;
    }

    public function getSofferenze($bank)
    {
        $periods = $this->buildPeriodArray();

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($bank, $queryPeriodArray) {
                $query->where('nome_banca', $bank);
                $query->where($queryPeriodArray);
                $query->where('categoria', 'SOFFERENZE');
            });
        }
        $sofferenze = $CentraleRischiModel
            ->get()
            ->toArray();

        $this->_sofferenze = $sofferenze;

        return $this->_sofferenze;
    }


    public function getCreditiPassatiPerdita($bank)
    {
        $periods = $this->buildPeriodArray();

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $bank) {
                $query->where('nome_banca', $bank);
                $query->where($queryPeriodArray);
                $query->where('categoria', 'SOFFERENZE - CREDITI PASSATI A PERDITA');
            });
        }
        $passatiPerdita = $CentraleRischiModel
            ->get()
            ->toArray();

        $this->_creditiPerdita = $passatiPerdita;

        return $this->_creditiPerdita;
    }


    public function getGaranzieEsitoNegativo($bank)
    {
        $periods = $this->buildPeriodArray();

        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $bank) {
                $query->where('nome_banca', $bank);
                $query->where($queryPeriodArray);
                $query->where('stato_rapporto', 'like', 'esito negativo');
            });
        }
        $garanzieEsitoNegativo = $CentraleRischiModel
            ->get()
            ->toArray();

        $this->_garanzieEsitoNegativo = count($garanzieEsitoNegativo);

        return $this->_garanzieEsitoNegativo;
    }
    public function getMediaIndebitamento()
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
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $sections, $categories) {
                $query->where($queryPeriodArray);
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
                if ($singleRow->utilizzato >  $singleRow->accordato_operativo) {
                    $sconfinoImporto =  $singleRow->utilizzato - $singleRow->accordato_operativo;
                    $mediaAnalisiIndebitamento[$singleLine]['Sconfinamenti'] += $sconfinoImporto;
                }
                if (!is_numeric($singleRow->accordato_operativo)) {
                    dd($data);
                }

                $mediaAnalisiIndebitamento[$singleLine]['Accordato Operativo'] += $singleRow->accordato_operativo;
                $mediaAnalisiIndebitamento[$singleLine]['Utilizzato'] += $singleRow->utilizzato;
            }
        }

        foreach ($mediaAnalisiIndebitamento as $singleLine => $data) {
            $mediaAnalisiIndebitamento[$singleLine]['Sconfinamenti'] = $data['Sconfinamenti'] / $allMonthsCount;
            $mediaAnalisiIndebitamento[$singleLine]['Accordato Operativo'] = $data['Accordato Operativo'] / $allMonthsCount;
            $mediaAnalisiIndebitamento[$singleLine]['Utilizzato'] = $data['Utilizzato'] / $allMonthsCount;
        }

        return $mediaAnalisiIndebitamento;
    }

    public function getImportiSconfini()
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
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories, $sections) {
                $query->where($queryPeriodArray);
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

            if ($item->accordato_operativo == 0) {
                $probErrataSegnalazione = 'Accordato Nullo';
            } else if ($item->categoria == 'RISCHI A SCADENZA') {
                if (($item->utilizzato / $item->accordato_operativo) < 1) {
                    $probErrataSegnalazione = 'Utilizzo anomalo, probabile errata segnalazione';
                }
            } else if ($item->categoria == 'RISCHI A SCADENZA') {
                if (($item->utilizzato / $item->accordato_operativo) > 1) {
                    $probErrataSegnalazione = 'Presenza di rate insolute o probabile errata segnalazione';
                }
            } else if ($item->categoria == 'RISCHI AUTOLIQUIDANTI') {
                if (($item->utilizzato - $item->accordato_operativo) > 0) {
                    $probErrataSegnalazione = 'Sconfino da verificare, possibile errore di valuta';
                }
            } else if (str_contains($item->stato_rapporto, 'crediti scaduti o sconfinanti da più di 90 giorni e non oltre 180 giorni') || str_contains($item->stato_rapporto, 'crediti scaduti o sconfinanti da più di oltre 180')) {
                $probErrataSegnalazione = 'Mancata evidenza dello sconfino nei mesi precedenti, possibile errata segnalazione';
            }

            $importiSconfini[] =  array(
                'Data' => $item->anno . ' ' . $item->mese,
                'Banca' => $item->nome_banca,
                'Categoria' => $item->categoria,
                'Tipo attività' => $item->tipo_attivita,
                'Importo Sconfinamento' => $item->accordato_operativo - $item->utilizzato,
                'Utilizzo Posizione Sconfinata' => $item->utilizzato,
                'Probabile errata segnalazione' => $probErrataSegnalazione
            );
        }
        return $importiSconfini;
    }

    public function getCreditiScaduti()
    {
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');
        $categories = array(
            'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI',
        );

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories) {
                $query->where($queryPeriodArray);
                $query->whereIn('categoria', $categories);
            });
        }
        $creditiScaduti = $CentraleRischiModel->select('categoria', 'importo_garantito')->get()
            ->groupBy(array('categoria'))
            ->toArray();

        return $creditiScaduti;
    }

    public function getCreditiScadutiImpagati()
    {
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');
        $categories = array(
            'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI',
        );


        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray, $categories) {
                $query->where($queryPeriodArray);
                $query->where('stato_rapporto', 'Crediti impagati');
                $query->whereIn('categoria', $categories);
            });
        }
        $creditiScadutiImpagati = $CentraleRischiModel->select('categoria', 'importo_garantito')->get()
            ->groupBy(array('categoria'))
            ->toArray();

        return $creditiScadutiImpagati;
    }

    public function getScadutiSconfinanti90()
    {
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');



        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray) {
                $query->where($queryPeriodArray);
                $query->where('stato_rapporto', 'like', '%non oltre 180%');
            });
        }

        $scadutiSconfinanti90 = $CentraleRischiModel->select('importo_garantito')->get()
            ->toArray();

        return $scadutiSconfinanti90;
    }

    public function getScadutiSconfinanti180()
    {
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');



        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray) {
                $query->where($queryPeriodArray);
                $query->where('stato_rapporto', 'not like', '%non oltre 180%');
                $query->where('stato_rapporto', 'like', '%oltre 180%');
            });
        }
        $scadutiSconfinanti90 = $CentraleRischiModel->select('categoria', 'importo_garantito')->get()
            ->groupBy(array('categoria'))
            ->toArray();

        return $scadutiSconfinanti90;
    }

    public function getCreditiContestati()
    {
        $allMonthsCount = $this->getCountMonths();
        $periods = $this->buildPeriodArray();
        $CentraleRischiModel = DB::table('crs');

        foreach ($periods as $queryPeriodArray) {
            $CentraleRischiModel->orWhere(function ($query) use ($queryPeriodArray) {
                $query->where($queryPeriodArray);
                $query->where('stato_rapporto', 'not like', '%non contestati%');
                $query->where('stato_rapporto', 'like', '%contestati%');
            });
        }

        $scadutiSconfinanti90 = $CentraleRischiModel->select('categoria', 'importo_garantito')->get()
            ->groupBy(array('categoria'))
            ->toArray();

        return $scadutiSconfinanti90;
    }

    public function getPercentualeMediaImpagati()
    {
        $periods = $this->getPeriod();

        // dd($periods);

        $incidenzaImpagati = array();

        foreach ($periods as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $papaya) {
                $totaleScaduti = cr::groupBy('anno')->groupBy('mese')->selectRaw("SUM(importo_garantito) as Importo")->where('categoria', 'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI')->where('anno', $singleYear)->where('mese', $singleMonth)->get()->toArray();
                $totaleScadutiImpagati = cr::groupBy('anno')->groupBy('mese')->selectRaw("SUM(importo_garantito) as Importo")->where('categoria', 'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI')->where('anno', $singleYear)->where('mese', $singleMonth)->where('stato_rapporto', 'Crediti impagati')->get()->toArray();
                $totaleScadutiPagati = cr::groupBy('anno')->groupBy('mese')->selectRaw("SUM(importo_garantito) as Importo")->where('categoria', 'RISCHI AUTOLIQUIDANTI - CREDITI SCADUTI')->where('anno', $singleYear)->where('mese', $singleMonth)->where('stato_rapporto', 'Crediti pagati')->get()->toArray();
                $incidenzaImpagati[$singleYear][$singleMonth] = number_format(($totaleScadutiImpagati[0]['Importo'] / $totaleScaduti[0]['Importo']) * 100, 2, '.', ',');
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

    public function getRischiGaranzieArray()
    {
        $rischiGaranzie['CreditiScaduti'] = $this->getCreditiScaduti();
        $rischiGaranzie['CreditiScadutiImpagati'] = $this->getCreditiScadutiImpagati();
        $rischiGaranzie['PercentualeMediaImpagati'] = $this->getPercentualeMediaImpagati();
        $rischiGaranzie['Sofferenze'] = $this->getSofferenze();
        $rischiGaranzie['CreditiPassatiPerdita'] = $this->getCreditiPassatiPerdita();
        $rischiGaranzie['Oltre90'] = $this->getScadutiSconfinanti90();
        $rischiGaranzie['Oltre180'] = $this->getScadutiSconfinanti180();
        $rischiGaranzie['CreditiContestati'] = $this->getCreditiContestati();

        return $rischiGaranzie;
    }
    public function getRischiGaranzie()
    {
        $allMonthsCount = $this->getCountMonths();
        $crediti = $this->getRischiGaranzieArray();
        $mediaRischiGaranzie = array();
        $mediaCreditiScaduti = 0;
        $mediaCreditiScadutiImpagati = 0;
        $mediaSofferenze = 0;
        $mediaCreditiOltre90 = 0;
        $mediaCreditiOltre180 = 0;
        $mediaCreditiPassatiPerdita = 0;
        $mediaCreditiContestati = 0;

        foreach ($crediti['CreditiScaduti'] as $singleCreditoScadutoLinee => $singleCreditoScadutoLinea) {
            foreach ($singleCreditoScadutoLinea as $label => $singleCreditoScaduto) {
                $mediaCreditiScaduti += $singleCreditoScaduto->importo_garantito;
            }
        }
        $mediaRischiGaranzie['CreditiScaduti'] = $mediaCreditiScaduti / $allMonthsCount;

        foreach ($crediti['CreditiScadutiImpagati'] as $singleCreditoScadutoImpagatoLinee => $singleCreditoImpagatoLinea) {
            foreach ($singleCreditoImpagatoLinea as $label => $singleCreditoScadutoImpagato) {
                $mediaCreditiScadutiImpagati += $singleCreditoScadutoImpagato->importo_garantito;
            }
        }
        $mediaRischiGaranzie['CreditiScadutiImpagati'] = $mediaCreditiScadutiImpagati / $allMonthsCount;

        foreach ($crediti['Sofferenze'] as $singleSofferenza => $singleSofferenzaValue) {
            $mediaSofferenze += $singleSofferenzaValue->importo_garantito;
        }
        $mediaRischiGaranzie['Sofferenze'] = $mediaSofferenze / $allMonthsCount;

        foreach ($crediti['CreditiPassatiPerdita'] as $singleCreditoPassatoPerdita => $singleCreditoPassatoPerditaValue) {
            foreach ($singleCreditoPassatoPerditaValue as $label => $singleCreditoPassatoPerdita) {
                $mediaCreditiPassatiPerdita += $singleCreditoPassatoPerdita->importo_garantito;
            }
        }
        $mediaRischiGaranzie['CreditiPassatiPerdita'] = $mediaCreditiPassatiPerdita / $allMonthsCount;

        foreach ($crediti['Oltre90'] as $singleCreditoOltre90 => $singleCreditoOltre90Value) {
            $mediaCreditiOltre90 += $singleCreditoOltre90Value->importo_garantito;
        }
        $mediaRischiGaranzie['Oltre90'] = $mediaCreditiOltre90 / $allMonthsCount;

        foreach ($crediti['Oltre180'] as $singleCreditoOltre180 => $singleCreditoOltre180Value) {
            foreach ($singleCreditoOltre180Value as $label => $singleCreditoOltre180) {
                $mediaCreditiOltre180 += $singleCreditoOltre180->importo_garantito;
            }
        }
        $mediaRischiGaranzie['Oltre180'] = $mediaCreditiOltre180 / $allMonthsCount;

        foreach ($crediti['CreditiContestati'] as $singleCreditoContestato => $singleCreditoContestatoValue) {
            foreach ($singleCreditoContestatoValue as $label => $singleCreditoContestatoValue) {
                $mediaCreditiContestati += $singleCreditoContestatoValue->importo_garantito;
            }
        }
        $mediaRischiGaranzie['CreditiContestati'] = $mediaCreditiContestati / $allMonthsCount;

        return $mediaRischiGaranzie;
    }


    public function getAnomalie()
    {
        // TODO
    }

    public function getTotaleAffidamenti($categories, $latestYear, $latestMonth)
    {
        return cr::groupBy('nome_banca')->groupBy('categoria')->selectRaw("nome_banca, categoria, SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('mese', $latestMonth)->whereIn('categoria', $categories)->get()->toArray();
    }

    public function getTotaleAffidamentiGeneral($categories, $latestYear, $latestMonth)
    {
        return cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('mese', $latestMonth)->whereIn('categoria', $categories)->get()->toArray();
    }

    public function getTotaleAffidamentiPerMese($lastYearPeriod, $categories)
    {
        $affidamentiPerMese = array();
        foreach ($lastYearPeriod as $singleYear => $multipleMonths) {
            foreach ($multipleMonths as $singleMonth => $raw) {
                $affidamentiPerMese[$singleMonth] =  cr::groupBy('mese')->groupBy('nome_banca')->groupBy('categoria')->selectRaw("nome_banca, categoria, SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $singleYear)->where('mese', $singleMonth)->whereIn('categoria', $categories)->get()->toArray();
            }
        }
        return $affidamentiPerMese;
    }

    public function getPesiAffidamentiPerBanca($categories, $latestYear, $latestMonth)
    {
        $totaliAccordatiUtilizzatiLastMonth = cr::selectRaw("SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('mese', $latestMonth)->whereIn('categoria', $categories)->get()->toArray();
        $totAffidamentiConPesiPerBanca = cr::groupBy('nome_banca')->selectRaw("nome_banca, SUM(accordato_operativo) as totAccordatoOperativo, SUM(utilizzato) as totUtilizzato")->where('anno', $latestYear)->where('mese', $latestMonth)->whereIn('categoria', $categories)->get()->toArray();


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
}
