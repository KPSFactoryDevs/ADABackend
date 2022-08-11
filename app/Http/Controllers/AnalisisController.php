<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Analisi;
use App\Models\Bilanci;
use App\Models\Account;
use App\Models\indici;
use App\Models\range;
use App\Models\AnalisisType;
use Illuminate\Http\Request;
use Exception;
use App\Helpers\Bilanci\BilanciHelper;
use Illuminate\Support\Facades\DB;
use DateTime;

class AnalisisController extends Controller
{

    /**
     * Display a listing of the analisis.
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $analisis = Analisi::with(['bilanci', 'account', 'analisistype'])->paginate(25);

        return view('analisis.index', compact('analisis'));
    }

    /**
     * Show the form for creating a new analisi.
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        $bilancis = Bilanci::pluck('name', 'id')->all();
        $accounts = Account::pluck('name', 'id')->all();
        $analisisType = AnalisisType::pluck('name', 'id')->all();

        return view('analisis.create', compact('bilancis', 'accounts', 'analisisType'));
    }

    /**
     * Store a new analisi in the storage.
     *
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        try {

            $data = $this->getData($request);


            Analisi::create($data);

			return response()->json([
				'error' => false,
				'data' => "Analisi was successfully added"
			]);

            return redirect()->route('admin.analisis.analisi.index')
                ->with('success_message', 'Analisi was successfully added.');
        } catch (Exception $exception) {
            var_dump($exception->getMessage());
            exit;
            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Display the specified analisi.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function show($id)
    {

        $bilancio = Bilanci::findOrFail($id);
        $tipoAzienda = $bilancio->tipo_azienda;

        $attributes = $bilancio->getAttributes();
        $jsonData['current'] = json_decode($attributes['json_data'], true);
        // dd($jsonData['current']);

        $jsonData['prev'] = json_decode($attributes['json_data_prev'], true);
        $jsonData['anagrafic'] = json_decode($attributes['json_data_anag'], true);
        $jsonData['currentYear'] = $attributes['current_year'];
        $jsonData['prevYear'] = $attributes['prev_year'];
        $tipoAzienda = $attributes['tipo_azienda'];
        $idBilancio = $id;

        $voci = DB::table('vocis')->get();
        $gradi = array();

        foreach ($voci as $voce) {
            if ($voce->voce_padre == null || $voce->voce_padre == "") {
                $h1[$voce->name] = $voce->extended_name;
                $gradi[] = array();
            } else {
                foreach ($voci as $voci1) {
                    if ($voce->voce_padre == $voci1->name) {
                        if ($voci1->voce_padre == null || $voci1->voce_padre == "") {
                            $gradi[$voci1->name][$voce->name] = array();
                        } else {
                            foreach ($voci as $voci2) {
                                if ($voci1->voce_padre == $voci2->name) {
                                    if ($voci2->voce_padre == null || $voci2->voce_padre == "") {
                                        $gradi[$voci2->name][$voci1->name][$voce->name] = array();
                                    } else {
                                        foreach ($voci as $voci3) {
                                            if ($voci2->voce_padre == $voci3->name) {
                                                if ($voci3->voce_padre == null || $voci3->voce_padre == "") {
                                                    $gradi[$voci3->name][$voci2->name][$voci1->name][$voce->name] = array();
                                                } else {
                                                    foreach ($voci as $voci4) {
                                                        if ($voci3->voce_padre == $voci4->name) {
                                                            if ($voci4->voce_padre == null || $voci4->voce_padre == "") {
                                                                $gradi[$voci4->name][$voci3->name][$voci2->name][$voci1->name][$voce->name] = array();
                                                            } else {
                                                                foreach ($voci as $voci5) {
                                                                    if ($voci4->voce_padre == $voci5->name) {
                                                                        if ($voci5->voce_padre == null || $voci5->voce_padre == "") {
                                                                            $gradi[$voci5->name][$voci4->name][$voci3->name][$voci2->name][$voci1->name][$voce->name] = array();
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        unset($gradi[0], $gradi[1], $gradi[2]);

        //        dd($gradi);

        $vociExt = array();

        $dataAnalisiBasic = array();

        foreach ($voci as $voce) {
            $vociExt[$voce->name] = $voce->extended_name;
        }

        //       $analisi = Analisi::with('bilanci')->with('account')->with('analisisType')->findOrFail($id);



        $bilancioJSON = json_decode($bilancio['json_data']);
        $bilancioJSONprev = json_decode($bilancio['json_data_prev']);
        $dataAnalisis = array();
        $righeUtilizzate = array();

        if ($bilancio->provvisorio == 1) {
            $vociContoEconomico = array(
                "ValoreProduzioneRicaviVenditePrestazioni",
                "ValoreProduzioneVariazioniRimanenzeProdottiCorsoLavorazioneSemilavoratiFiniti",
                "ValoreProduzioneVariazioniLavoriCorsoOrdinazione",
                "ValoreProduzioneIncrementiImmobilizzazioniLavoriInterni",
                "ValoreProduzioneAltriRicaviProventiTotaleAltriRicaviProventi",
                "CostiProduzioneMateriePrimeSussidiarieConsumoMerci",
                "CostiProduzioneServizi",
                "CostiProduzioneGodimentoBeniTerzi",
                "CostiProduzionePersonaleTotaleCostiPersonale",
                "CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni",
                "CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci",
                "CostiProduzioneAccantonamentiRischi",
                "CostiProduzioneAltriAccantonamenti",
                "CostiProduzioneOneriDiversiGestione",
                "ProventiOneriFinanziariProventiPartecipazioniTotaleProventiPartecipazioni",
                "ProventiOneriFinanziariAltriProventiFinanziariTotaleAltriProventiFinanziari",
                "ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari",
                "ProventiOneriStraordinariProventiTotaleProventi",
                "ProventiOneriStraordinariOneriTotaleOneri",
            );

            $tmpPeriod = explode(' ', $bilancio->year);

            $periodStart = new DateTime($tmpPeriod[0]);

            $periodEnd = new DateTime($tmpPeriod[1]);

            $days = $periodEnd->diff($periodStart)->format("%a");

            $daysToYear = $days / 365;

            $bilancioJSON = (array)$bilancioJSON;

            $bilancioJSON['ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate'] = (float)$bilancioJSON['RisultatoPrimaImposte'] * 0.28;

            $bilancioJSON['UtilePerditaEsercizio'] = (float)$bilancioJSON['RisultatoPrimaImposte'] - (float)$bilancioJSON['ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate'];

            foreach ($vociContoEconomico as $tmp => $singolaVoce) {
                if (isset($bilancioJSON[$singolaVoce])) {
                    $bilancioJSON[$singolaVoce] = ($bilancioJSON[$singolaVoce]) * (float)$daysToYear;
                }
            }

            $bilancioJSON = (object)$bilancioJSON;

            // dd($periodStart, $periodEnd, $daysToYear, $bilancioJSON);
        }


        $imposteRedditoEsercizioImposteAnticipate = isset($bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate) ? (float)$bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate : 0;

        $bilancioJSONanag = json_decode($bilancio['json_data_anag']); // DatiAnagraficiDenominazione DatiAnagraficiFormaGiuridica

        $formaGiuridica = $bilancio->forma_giuridica;

        $nomeAzienda = $bilancioJSONanag->DatiAnagraficiDenominazione;

        $salariStipendi = $bilancioJSON->CostiProduzionePersonaleSalariStipendi;

        try {
            $TotaleAttivo = (isset($bilancioJSON->TotaleAttivo) ? $bilancioJSON->TotaleAttivo : 0);
            $CostiProduzioneAltriAccantonamenti = (isset($bilancioJSON->CostiProduzioneAltriAccantonamenti) ? $bilancioJSON->CostiProduzioneAltriAccantonamenti : 0);
            $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni = (isset($bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni) ? $bilancioJSON->CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni : 0);
            $TotaleCreditiVersoSociVersamentiAncoraDovuti = (isset($bilancioJSON->TotaleCreditiVersoSociVersamentiAncoraDovuti) ? $bilancioJSON->TotaleCreditiVersoSociVersamentiAncoraDovuti : 0);
            $TotalePatrimonioNetto = (isset($bilancioJSON->TotalePatrimonioNetto) ? $bilancioJSON->TotalePatrimonioNetto : 0);
            $PN_NEGATIVO = (float)$TotalePatrimonioNetto - (float)$TotaleCreditiVersoSociVersamentiAncoraDovuti;
            $dataAnalisisBasic['Patrimonio_Netto'] = $PN_NEGATIVO;
            $arrayConVoci['Patrimonio_Netto'] = array('TotalePatrimonioNetto', 'TotaleCreditiVersoSociVersamentiAncoraDovuti');
            $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : 0));
            $UtilePerditaEsercizio = (isset($bilancioJSON->UtilePerditaEsercizio) ? $bilancioJSON->UtilePerditaEsercizio : 0);
            $PatrimonioNettoUtilePerditaEsercizio = isset($bilancioJSON->PatrimonioNettoUtilePerditaEsercizio) ? $bilancioJSON->PatrimonioNettoUtilePerditaEsercizio : 0;
            // Valori bilancio
            // ### OF_RICAVI ###
            $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = (isset($bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari) ? $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari : 0);
            $ValoreProduzioneRicaviVenditePrestazioni = (isset($bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni : 0);
            if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
                $ValoreProduzioneRicaviVenditePrestazioni = 0.00001;
            }
            $OF_RICAVI = number_format((float)($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / (float)$ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
            $dataAnalisisBasic['OF_Fatturato'] = $OF_RICAVI . '%';
            $arrayConVoci['OF_Fatturato'] = array('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 'ValoreProduzioneRicaviVenditePrestazioni');

            // ### ADEGUATEZZA_PATRIMONIALE ###
            $TotaleDebiti = (isset($bilancioJSON->TotaleDebiti) ? $bilancioJSON->TotaleDebiti : 0);
            $PassivoRateiRisconti = (isset($bilancioJSON->PassivoRateiRisconti) ? $bilancioJSON->PassivoRateiRisconti : 0);

            $ADEGUATEZZA_PATRIMONIALE = number_format((float)($PN_NEGATIVO / ($TotaleDebiti + (float)$PassivoRateiRisconti)) * 100, 2, ',', '.');
            $dataAnalisisBasic['Adeguatezza_Patrimoniale'] = $ADEGUATEZZA_PATRIMONIALE . '%';
            $arrayConVoci['Adeguatezza_Patrimoniale'] = array('TotaleDebiti', 'PassivoRateiRisconti');

            $arrayConVoci['Adeguatezza_Patrimoniale'] = array_merge($arrayConVoci['Adeguatezza_Patrimoniale'], $arrayConVoci['Patrimonio_Netto']);


            // ### RITORNO_LIQUIDO_ATTIVO ###
            $DebitiEsigibiliEntroEsercizioSuccessivo = isset($bilancioJSON->DebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiEsigibiliEntroEsercizioSuccessivo : 0;
            $TotaleDisponibilitaLiquide = (isset($bilancioJSON->TotaleDisponibilitaLiquide) ? $bilancioJSON->TotaleDisponibilitaLiquide : $val = (isset($bilancioJSONprev->TotaleDisponibilitaLiquide) ? $bilancioJSONprev->TotaleDisponibilitaLiquide : 0));
            $AttivoRateiRisconti = (isset($bilancioJSON->AttivoRateiRisconti) ? $bilancioJSON->AttivoRateiRisconti : 0);
            $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni = (isset($bilancioJSON->TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni) ? $bilancioJSON->TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni : 0);
            $TotaleRimanenze = (isset($bilancioJSON->TotaleRimanenze) ? $bilancioJSON->TotaleRimanenze : 0);
            $TotaleCrediti = (isset($bilancioJSON->TotaleCrediti) ? $bilancioJSON->TotaleCrediti : 0);
            $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo : 0);
            $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
            $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
            $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
            $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : 0);
            $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : 0);
            $CreditiImposteAnticipateTotaleImposteAnticipate = (isset($bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate) ? $bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate : 0);

            // dd($bilancioJSON->CreditiImposteAnticipateTotaleImposteAnticipate);

            $TotaleCreditiEntroDodiciMesi = (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiImposteAnticipateTotaleImposteAnticipate + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;

            $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo : 0);
            $PassivoRateiRisconti = (isset($bilancioJSON->PassivoRateiRisconti) ? $bilancioJSON->PassivoRateiRisconti : 0);


            $TotaleDebitiEntroDodiciMesi = (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo;

            //        dd($TotaleDebitiEntroDodiciMesi);

            //        dd($PassivoRateiRisconti,$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo,$DebitiAccontiEsigibiliEntroEsercizioSuccessivo,$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo,$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo,$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo);
            //	dd($bilancioJSON, $imposteRedditoEsercizioImposteAnticipate);

            // ### LIQUIDITA ###
            $CostiProduzioneAccantonamentiRischi = (isset($bilancioJSON->CostiProduzioneAccantonamentiRischi) ? $bilancioJSON->CostiProduzioneAccantonamentiRischi : 0);
            $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
            $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
            if ($TotaleAttivo == 0) {
                $LIQUIDITA = 0;
                $dataAnalisisBasic['Liquidità'] = $LIQUIDITA . '%';
            } else {
                $LIQUIDITA = number_format((float)(($UtilePerditaEsercizio + (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti - (float)$imposteRedditoEsercizioImposteAnticipate) / (float)$TotaleAttivo) * 100, 2, ',', '.');
                $dataAnalisisBasic['Liquidità'] = $LIQUIDITA . '%';
            }
            //dd('Utile Perdita Esercizio', $UtilePerditaEsercizio, 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', $CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni, 'CostiProduzioneAccantonamentiRischi', $CostiProduzioneAccantonamentiRischi, 'CostiProduzioneAltriAccantonamenti', $CostiProduzioneAltriAccantonamenti, 'Totale attivo', $TotaleAttivo, 'Imposte Anticipate', $imposteRedditoEsercizioImposteAnticipate);
            $arrayConVoci['Liquidità'] = array('UtilePerditaEsercizio', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'TotaleAttivo', 'ImposteRedditoEsercizioImposteAnticipate');

            // ### INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO ###
            $DebitiDebitiTributariTotaleDebitiTributariCorrente = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
            $DebitiDebitiTributariTotaleDebitiTributari = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
            $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale : 0);
            $ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari = $bilancioJSON->ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari;
            $ValoreProduzioneRicaviVenditePrestazioni = $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni;
            if ($TotaleAttivo == 0) {
                $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / 1) * 100, 2, ',', '.');
                $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%';
            } else {
                $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO = number_format((($DebitiDebitiTributariTotaleDebitiTributari + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale) / (float)$TotaleAttivo) * 100, 2, ',', '.');
                $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'] = $INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO . '%';
            }

            $arrayConVoci['Indebitamento_Previdenziale_Tributario'] = array('DebitiDebitiTributariTotaleDebitiTributari', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeTotaleDebitiVersoIstitutiPrevidenzaSicurezzaSociale', 'TotaleAttivo');

            //        dd($INDEBITAMENTO_PREVIDENZIALE_TRIBUTARIO);

            // INDICI ADVANCED

            //### Andamento del fatturato
            $ValoreProduzioneRicaviVenditePrestazioniCurr = (isset($bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSON->ValoreProduzioneRicaviVenditePrestazioni : 0);
            $ValoreProduzioneRicaviVenditePrestazioniPrev = (isset($bilancioJSONprev->ValoreProduzioneRicaviVenditePrestazioni) ? $bilancioJSONprev->ValoreProduzioneRicaviVenditePrestazioni : 0);
            if ($ValoreProduzioneRicaviVenditePrestazioniPrev == 0) {
                $AndamentoDelFatturato = number_format((float)(- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / (1)))) * 100, 2, ',', '.');
                $dataAnalisis['Andamento_del_fatturato'] = $AndamentoDelFatturato . '%';
            } else {
                $AndamentoDelFatturato = number_format((float)(- (1 - (($ValoreProduzioneRicaviVenditePrestazioniCurr) / ($ValoreProduzioneRicaviVenditePrestazioniPrev)))) * 100, 2, ',', '.');
                $dataAnalisis['Andamento_del_fatturato'] = $AndamentoDelFatturato . '%';
            }
            $arrayConVoci['Andamento_del_fatturato'] = array('ValoreProduzioneRicaviVenditePrestazioniCurr', 'ValoreProduzioneRicaviVenditePrestazioniPrev');

            // ANDAMENTO DEL MOL
            $TotaleValoreProduzione = (isset($bilancioJSON->TotaleValoreProduzione) ? $bilancioJSON->TotaleValoreProduzione : 0);
            $CostiProduzioneMateriePrimeSussidiarieConsumoMerci = (isset($bilancioJSON->CostiProduzioneMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSON->CostiProduzioneMateriePrimeSussidiarieConsumoMerci : 0);
            $CostiProduzioneGodimentoBeniTerzi = (isset($bilancioJSON->CostiProduzioneGodimentoBeniTerzi) ? $bilancioJSON->CostiProduzioneGodimentoBeniTerzi : 0);
            $CostiProduzioneServizi = (isset($bilancioJSON->CostiProduzioneServizi) ? $bilancioJSON->CostiProduzioneServizi : 0);
            $CostiProduzionePersonaleTotaleCostiPersonale = (isset($bilancioJSON->CostiProduzionePersonaleTotaleCostiPersonale) ? $bilancioJSON->CostiProduzionePersonaleTotaleCostiPersonale : 0);
            $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci = (isset($bilancioJSON->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSON->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci : 0);
            $CostiProduzioneOneriDiversiGestione = (isset($bilancioJSON->CostiProduzioneOneriDiversiGestione) ? $bilancioJSON->CostiProduzioneOneriDiversiGestione : 0);

            $TotaleValoreProduzionePrecedente = (isset($bilancioJSONprev->TotaleValoreProduzione) ? $bilancioJSONprev->TotaleValoreProduzione : 0);
            $CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente = (isset($bilancioJSONprev->CostiProduzioneMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSONprev->CostiProduzioneMateriePrimeSussidiarieConsumoMerci : 0);
            $CostiProduzioneGodimentoBeniTerziPrecedente = (isset($bilancioJSONprev->CostiProduzioneGodimentoBeniTerzi) ? $bilancioJSONprev->CostiProduzioneGodimentoBeniTerzi : 0);
            $CostiProduzioneServiziPrecedente = (isset($bilancioJSONprev->CostiProduzioneServizi) ? $bilancioJSONprev->CostiProduzioneServizi : 0);
            $CostiProduzionePersonaleTotaleCostiPersonalePrecedente = (isset($bilancioJSONprev->CostiProduzionePersonaleTotaleCostiPersonale) ? $bilancioJSONprev->CostiProduzionePersonaleTotaleCostiPersonale : 0);
            $CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente = (isset($bilancioJSONprev->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci) ? $bilancioJSONprev->CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci : 0);
            $CostiProduzioneOneriDiversiGestionePrecedente = (isset($bilancioJSONprev->CostiProduzioneOneriDiversiGestione) ? $bilancioJSONprev->CostiProduzioneOneriDiversiGestione : 0);


            $MOLcurr = $TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzioneServizi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione;
            $MOLprev = $TotaleValoreProduzionePrecedente - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerciPrecedente - (float)$CostiProduzioneGodimentoBeniTerziPrecedente - (float)$CostiProduzioneServiziPrecedente - (float)$CostiProduzionePersonaleTotaleCostiPersonalePrecedente - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerciPrecedente - (float)$CostiProduzioneOneriDiversiGestionePrecedente;

            if ($MOLprev == 0) {
                $AndamentoMOL = number_format(- (1 - ($MOLcurr / 1)) * 100, 2, ',', '.');
                $dataAnalisis['Andamento_del_MOL'] = $AndamentoMOL . '%';
            } else {
                $AndamentoMOL = number_format(- (1 - ($MOLcurr / (float)$MOLprev)) * 100, 2, ',', '.');
                $dataAnalisis['Andamento_del_MOL'] = $AndamentoMOL . '%';
            }

            $arrayConVoci['Andamento_del_MOL'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzioneServizi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione');

            // ### ROI

            $DifferenzaValoreCostiProduzione = (isset($bilancioJSON->DifferenzaValoreCostiProduzione) ? $bilancioJSON->DifferenzaValoreCostiProduzione : 0);
            if ($TotaleAttivo == 0) {
                $ROI = number_format((float)($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '.');
                $dataAnalisis['ROI'] = $ROI . '%';
            } else {
                $ROI = number_format((float)($DifferenzaValoreCostiProduzione / (float)$TotaleAttivo) * 100, 2, ',', '.');
                $dataAnalisis['ROI'] = $ROI . '%';
            }

            $arrayConVoci['ROI'] = array('DifferenzaValoreCostiProduzione', 'TotaleAttivo');

            // ### ROS

            if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
                $ROS = number_format((float)($DifferenzaValoreCostiProduzione / 1) * 100, 2, ',', '.');
                $dataAnalisis['ROS'] = $ROS . '%';
            } else {
                $ROS = number_format((float)($DifferenzaValoreCostiProduzione / (float)$ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
                $dataAnalisis['ROS'] = $ROS . '%';
            }

            $arrayConVoci['ROS'] = array('DifferenzaValoreCostiProduzione', 'ValoreProduzioneRicaviVenditePrestazioni');

            //### ROE

            if ($TotalePatrimonioNetto == 0) {
                $ROE = number_format((float)($UtilePerditaEsercizio / 1) * 100, 2, ',', '.');
                $dataAnalisis['ROE'] = $ROE . '%';
            } else {
                $ROE = number_format((float)($UtilePerditaEsercizio / (float)$TotalePatrimonioNetto) * 100, 2, ',', '.');
                $dataAnalisis['ROE'] = $ROE . '%';
            }

            $arrayConVoci['ROE'] = array('UtilePerditaEsercizio', 'TotalePatrimonioNetto');

            //### EBITDA/Fatturato

            if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
                $EBITDA_FATTURATO = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione) / 1) * 100, 2, ',', '.');
                $dataAnalisis['EBITDA_Fatturato'] = $EBITDA_FATTURATO . '%';
            } else {
                $EBITDA_FATTURATO = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione) / (float)$ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
                $dataAnalisis['EBITDA_Fatturato'] = $EBITDA_FATTURATO . '%';
            }

            $arrayConVoci['EBITDA_Fatturato'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'ValoreProduzioneRicaviVenditePrestazioni');

            //### Andamento dei mezzi propri

            $TotalePatrimonioNettoCurr = (isset($bilancioJSON->TotalePatrimonioNetto) ? $bilancioJSON->TotalePatrimonioNetto : 0);
            $TotalePatrimonioNettoPrev = (isset($bilancioJSONprev->TotalePatrimonioNetto) ? $bilancioJSONprev->TotalePatrimonioNetto : 0);
            if ($TotalePatrimonioNettoPrev == 0) {
                $AndamentoDeiMezziPropri = number_format((float)(($TotalePatrimonioNettoCurr / 1) - 1) * 100, 2, ',', '.');
                $dataAnalisis['Andamento_dei_mezzi_propri'] = $AndamentoDeiMezziPropri . '%';
            } else {
                $AndamentoDeiMezziPropri = number_format((float)(($TotalePatrimonioNettoCurr / (float)$TotalePatrimonioNettoPrev) - 1) * 100, 2, ',', '.');
                $dataAnalisis['Andamento_dei_mezzi_propri'] = $AndamentoDeiMezziPropri . '%';
            }

            $arrayConVoci['Andamento_dei_mezzi_propri'] = array('TotalePatrimonioNetto');

            //### Margine Struttura Primario
            $TotaleImmobilizzazioni = (isset($bilancioJSON->TotaleImmobilizzazioni) ? $bilancioJSON->TotaleImmobilizzazioni : 0);
            if ($TotaleImmobilizzazioni == 0) {
                $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / 1) * 100, 2, ',', '.');
                $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario . '%';
            } else {
                $Margine_Struttura_Primario = number_format((float)($TotalePatrimonioNetto / (float)$TotaleImmobilizzazioni) * 100, 2, ',', '.');
                $dataAnalisis['Margine_Struttura_Primario'] = $Margine_Struttura_Primario . '%';
            }

            $arrayConVoci['Margine_Struttura_Primario'] = array('TotalePatrimonioNetto', 'TotaleImmobilizzazioni');

            //### Margine Struttura Secondario
            $TrattamentoFineRapportoLavoroSubordinato = (isset($bilancioJSON->TrattamentoFineRapportoLavoroSubordinato) ? $bilancioJSON->TrattamentoFineRapportoLavoroSubordinato : 0);
            $DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo : 0));
            $DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiAccontiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiAccontiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiAccontiEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo : 0);
            $DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSONprev->DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo : 0));
            $DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo : 0));
            $DebitiEsigibiliOltreEsercizioSuccessivo = isset($bilancioJSON->DebitiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiEsigibiliOltreEsercizioSuccessivo : 0;


            $QuarantaTre = (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo; //(isset($bilancioJSON->DebitiOltreEsercizioSuccessivo) ? $bilancioJSON->DebitiOltreEsercizioSuccessivo : 0);

            if ($TotaleImmobilizzazioni == 0) {
                $Margine_Struttura_Secondario_Semplificato = number_format((float)(($TotalePatrimonioNetto + (float)$TrattamentoFineRapportoLavoroSubordinato + (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / 1) * 100, 2, ',', '.');
                $Margine_Struttura_Secondario_Ordinario = number_format((float)(($TotalePatrimonioNetto + (float)$TrattamentoFineRapportoLavoroSubordinato + (float)$QuarantaTre) / 1) * 100, 2, ',', '.');
                $dataAnalisis['Margine_Struttura_Secondario'] = $Margine_Struttura_Secondario_Semplificato . '%';
            } else {
                $Margine_Struttura_Secondario_Semplificato = number_format((float)(($TotalePatrimonioNetto + (float)$TrattamentoFineRapportoLavoroSubordinato + (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo) / (float)$TotaleImmobilizzazioni) * 100, 2, ',', '.');
                $Margine_Struttura_Secondario_Ordinario = number_format((float)(($TotalePatrimonioNetto + (float)$TrattamentoFineRapportoLavoroSubordinato + (float)$QuarantaTre) / (float)$TotaleImmobilizzazioni) * 100, 2, ',', '.');
                $dataAnalisis['Margine_Struttura_Secondario'] = $Margine_Struttura_Secondario_Semplificato . '%';
            }

            $arrayConVoci['Margine_Struttura_Secondario'] = array('TotalePatrimonioNetto', 'TrattamentoFineRapportoLavoroSubordinat', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'DebitiAccontiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoFornitoriEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoImpreseControllateEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoImpreseCollegateEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoControllantiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiTributariEsigibiliOltreEsercizioSuccessivo', 'DebitiAltriDebitiEsigibiliOltreEsercizioSuccessivo', 'TotaleImmobilizzazioni');

            // CURRENT RATIO (VEDI INDICE RITORNO LIQUIDO ATT)

            $denominatoreRitornoLiquidoAttivo = 0;
            if (($TotaleDebitiEntroDodiciMesi + (float)$PassivoRateiRisconti) == 0) {
                $denominatoreRitornoLiquidoAttivo = 1;
            }

            $formula = ($TotaleDisponibilitaLiquide + (float)$AttivoRateiRisconti + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$TotaleRimanenze + (float)$TotaleCreditiEntroDodiciMesi) / ($TotaleDebitiEntroDodiciMesi + (float)$PassivoRateiRisconti + (float)$denominatoreRitornoLiquidoAttivo);

            $RITORNO_LIQUIDO_ATTIVO = number_format((float)$formula * 100, 2, ',', '.');
            $dataAnalisis['Current_Ratio'] = $RITORNO_LIQUIDO_ATTIVO . '%';
            $arrayConVoci['Current_Ratio'] = array('TotaleDisponibilitaLiquide', 'AttivoRateiRisconti', 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 'TotaleRimanenze', 'TotaleCreditiEntroDodiciMesi', 'TotaleDebitiEntroDodiciMesi', 'PassivoRateiRisconti');
            // dd('TotaleDisponibilitaLiquide', $TotaleDisponibilitaLiquide, 'AttivoRateiRisconti', $AttivoRateiRisconti, 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', $TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni, 'TotaleRimanenze', $TotaleRimanenze, 'TotaleCreditiEntroDodiciMesi', $TotaleCreditiEntroDodiciMesi, 'TotaleDebitiEntroDodiciMesi', $TotaleDebitiEntroDodiciMesi, 'PassivoRateiRisconti', $PassivoRateiRisconti);

            //### Attivita a breve / Passività a Breve

            // TRENTACINQUE
            $CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
            $CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
            $CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
            $CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoControllantiEsigibiliOltreEsercizioSuccessivo : 0);
            $CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo : 0));
            $CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo : 0));
            $TrentaCinque = (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo;
            $CreditiCreditiTributariTotaleCreditiTributari = (isset($bilancioJSON->CreditiCreditiTributariTotaleCreditiTributari) ? $bilancioJSON->CreditiCreditiTributariTotaleCreditiTributari : $val = (isset($bilancioJSONprev->CreditiCreditiTributariTotaleCreditiTributari) ? $bilancioJSONprev->CreditiCreditiTributariTotaleCreditiTributari : 0));

            // QUARANTANOVE
            $DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo : 0));
            $DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiAccontiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiAccontiEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo : 0));
            $DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo : 0);
            $DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo : 0));
            $DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo : $val = (isset($bilancioJSONprev->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSONprev->DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo : 0));
            $DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo : 0);
            $QuarantaNove = (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo;
            $CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo = (isset($bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo) ? $bilancioJSON->CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo : 0);

            if ($QuarantaNove > 0 || $PassivoRateiRisconti > 0) {
                $Attivita_a_breve_Passivita_a_Breve_Semplificato = number_format((float)((($TotaleDisponibilitaLiquide + (float)$TrentaCinque + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti) / ($QuarantaNove + (float)$PassivoRateiRisconti))), 2, ',', '.');
                //$dataAnalisis['Attivita_a_breve_Passività_a_Breve_Semplificato'] = $Attivita_a_breve_Passivita_a_Breve_Semplificato.'%';
            }
            $Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore = (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti;

            if ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore > 0) {
                $Attivita_a_breve_Passivita_a_Breve_Ordinario = number_format((float)((($TotaleDisponibilitaLiquide + (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti) / ($Attivita_a_breve_Passivita_a_Breve_Ordinario_divisore))) * 100, 2, ',', '.');
                $dataAnalisis['Attivita_a_breve_Passività_a_Breve_Ordinario'] = $Attivita_a_breve_Passivita_a_Breve_Ordinario . '%';
            }

            $arrayConVoci['Attivita_a_breve_Passività_a_Breve_Ordinario'] = array();

            // ACID TEST
            if ($DebitiEsigibiliEntroEsercizioSuccessivo > 0 || $PassivoRateiRisconti > 0) {
                $AcidTest = number_format((float)(($TotaleCrediti + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$TotaleDisponibilitaLiquide + (float)$AttivoRateiRisconti) / ($DebitiEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti)), 2, ',', '.');
                //            $dataAnalisis['AcidTest'] = $AcidTest.'%';
            }
            if ($QuarantaNove > 0 || $PassivoRateiRisconti > 0) {
                $ACID_TEST_Semplificato = number_format((float)((($TotaleDisponibilitaLiquide + (float)$TrentaCinque + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti - (float)$TotaleRimanenze) / ($QuarantaNove + (float)$PassivoRateiRisconti))), 2, ',', '.');
                // (float)$dataAnalisis['ACID_TEST_Semplificato'] = $ACID_TEST_Semplificato.'%';
            }
            $ACID_TEST_Ordinario_divisore = (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAccontiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo + (float)$DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$PassivoRateiRisconti;

            if ($ACID_TEST_Ordinario_divisore > 0) {
                $ACID_TEST_Ordinario = number_format((float)((($TotaleDisponibilitaLiquide + (float)$CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo + (float)$CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo + (float)$CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo + (float)$TotaleRimanenze + (float)$TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni + (float)$AttivoRateiRisconti - (float)$TotaleRimanenze) / ($ACID_TEST_Ordinario_divisore))) * 100, 2, ',', '.');
                $dataAnalisis['Acid_Test'] = $ACID_TEST_Ordinario . '%';
            }

            $arrayConVoci['Acid_Test'] = array('TotaleDisponibilitaLiquide', 'CreditiVersoClientiEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 'CreditiCreditiTributariEsigibiliEntroEsercizioSuccessivo', 'CreditiVersoAltriEsigibiliEntroEsercizioSuccessivo', 'TotaleRimanenze', 'TotaleAttivitaFinanziarieNonCostituisconoImmobilizzazioni', 'AttivoRateiRisconti', 'TotaleRimanenze', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiAccontiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoFornitoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiRappresentatiTitoliCreditoEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoImpreseControllateEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoImpreseCollegateEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoControllantiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiTributariEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoIstitutiPrevidenzaSicurezzaSocialeEsigibiliEntroEsercizioSuccessivo', 'DebitiAltriDebitiEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'PassivoRateiRisconti');

            // AUTONOMIA FINANZIARIA
            if (($TotalePatrimonioNetto + (float)$TotaleDebiti) == 0) {
                $AUTONOMIA_FINANZIARIA = number_format((float)(($TotalePatrimonioNetto / 1)) * 100, 2, ',', '.');
                $dataAnalisis['Autonomia_Finanziaria'] = $AUTONOMIA_FINANZIARIA . '%';
            } else {
                $AUTONOMIA_FINANZIARIA = number_format((float)(($TotalePatrimonioNetto / ($TotalePatrimonioNetto + (float)$TotaleDebiti))) * 100, 2, ',', '.');
                $dataAnalisis['Autonomia_Finanziaria'] = $AUTONOMIA_FINANZIARIA . '%';
            }

            $arrayConVoci['Autonomia_Finanziaria'] = array('TotalePatrimonioNetto', 'TotalePatrimonioNetto', 'TotaleDebiti');

            // LIVELLO INVESTIMENTI AZIENDALI
            if ($TotaleAttivo == 0) {
                $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto / 0.1) * 100, 2, ',', '.');
                $dataAnalisis['Livello_investimenti_aziendali'] = $LIVELLO_INVESTIMENTI_AZIENDALI . '%';
            } else {
                $LIVELLO_INVESTIMENTI_AZIENDALI = number_format((float)($TotalePatrimonioNetto / (float)$TotaleAttivo) * 100, 2, ',', '.');
                $dataAnalisis['Livello_investimenti_aziendali'] = $LIVELLO_INVESTIMENTI_AZIENDALI . '%';
            }

            $arrayConVoci['Livello_investimenti_aziendali'] = array('TotalePatrimonioNetto', 'TotaleAttivo');


            // PFN / EBITDA
            $ImmobilizzazioniFinanziarieCreditiTotaleCrediti = (isset($bilancioJSON->ImmobilizzazioniFinanziarieCreditiTotaleCrediti) ? $bilancioJSON->ImmobilizzazioniFinanziarieCreditiTotaleCrediti : 0);
            $debitiFinanziariCurr = (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
            if ($MOLcurr == 0) {
                $PFN_EBITDA = number_format((float)(($debitiFinanziariCurr - (float)$TotaleDisponibilitaLiquide - (float)$ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 1), 2, ',', '.');
                $dataAnalisis['PFN_EBITDA'] = $PFN_EBITDA;
            } else {
                $PFN_EBITDA = number_format((float)(($debitiFinanziariCurr - (float)$TotaleDisponibilitaLiquide - (float)$ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / (float)$MOLcurr), 2, ',', '.');
                $dataAnalisis['PFN_EBITDA'] = $PFN_EBITDA;
            }

            $arrayConVoci['PFN_EBITDA'] = array('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'TotaleDisponibilitaLiquide', 'ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 'TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzioneServizi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione');

            // Peso Oneri Finanziari (OF/Fatturato)
            $denominatoreOFfatturato = $ValoreProduzioneRicaviVenditePrestazioni;
            if ($denominatoreOFfatturato == 0) {
                $denominatoreOFfatturato = 1;
            }
            $Peso_Oneri_Finanziari = number_format((float)($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari / (float)$denominatoreOFfatturato) * 100,  2, ',', '.');
            $dataAnalisis['Peso_Oneri_Finanziari'] = $Peso_Oneri_Finanziari . '%';
            $arrayConVoci['Peso_Oneri_Finanziari'] = array('ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari', 'ValoreProduzioneRicaviVenditePrestazioni');

            // Copertura Lorda degli Oneri Finanziari
            if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
                $Copertura_Lorda_degli_Oneri_Finanziari = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione) / 1), 2, ',', '.');
                $dataAnalisis['Copertura_Lorda_OF'] = $Copertura_Lorda_degli_Oneri_Finanziari;
            } else {
                $Copertura_Lorda_degli_Oneri_Finanziari = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione) / (float)$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, ',', '.');
                $dataAnalisis['Copertura_Lorda_OF'] = $Copertura_Lorda_degli_Oneri_Finanziari;
            }


            $arrayConVoci['Copertura_Lorda_OF'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

            // EBIT / OF
            if ($ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari == 0) {
                $EBIT_OF = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione - (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - (float)$CostiProduzioneAccantonamentiRischi - (float)$CostiProduzioneAltriAccantonamenti) / 1), 2, ',', '.');
                $dataAnalisis['EBIT_OF'] = $EBIT_OF;
            } else {
                $EBIT_OF = number_format((float)(($TotaleValoreProduzione - (float)$CostiProduzioneMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneServizi - (float)$CostiProduzioneGodimentoBeniTerzi - (float)$CostiProduzionePersonaleTotaleCostiPersonale - (float)$CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci - (float)$CostiProduzioneOneriDiversiGestione - (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - (float)$CostiProduzioneAccantonamentiRischi - (float)$CostiProduzioneAltriAccantonamenti) / (float)$ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari), 2, ',', '.');
                $dataAnalisis['EBIT_OF'] = $EBIT_OF;
            }

            $arrayConVoci['EBIT_OF'] = array('TotaleValoreProduzione', 'CostiProduzioneMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneServizi', 'CostiProduzioneGodimentoBeniTerzi', 'CostiProduzionePersonaleTotaleCostiPersonale', 'CostiProduzioneVariazioniRimanenzeMateriePrimeSussidiarieConsumoMerci', 'CostiProduzioneOneriDiversiGestione', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'ProventiOneriFinanziariInteressiAltriOneriFinanziariTotaleInteressiAltriOneriFinanziari');

            //Costo del personale
            if ($ValoreProduzioneRicaviVenditePrestazioni == 0) {
                $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale / 1) * 100, 2, ',', '.');
                $dataAnalisis['Costo_del_personale'] = $Costo_del_personale . '%';
            } else {
                $Costo_del_personale = number_format((float)($CostiProduzionePersonaleTotaleCostiPersonale / (float)$ValoreProduzioneRicaviVenditePrestazioni) * 100, 2, ',', '.');
                $dataAnalisis['Costo_del_personale'] = $Costo_del_personale . '%';
            }

            $arrayConVoci['Costo_del_personale'] = array('CostiProduzionePersonaleTotaleCostiPersonale', 'ValoreProduzioneRicaviVenditePrestazioni');
            $CreditiImposteAnticipateTotaleImposteAnticipate = (isset($bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate) ? $bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateImposteDifferiteAnticipate : 0);

            // CF / Attivo
            if ($TotaleAttivo == 0) {
                $CF_ATTIVO = number_format((float)(($PatrimonioNettoUtilePerditaEsercizio + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti + (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - (float)$CreditiImposteAnticipateTotaleImposteAnticipate) / 0.1) * 100, 2, ',', '.');
                $dataAnalisis['CF_Attivo'] = $CF_ATTIVO . '%';
            } else {
                $CF_ATTIVO = number_format((float)(($PatrimonioNettoUtilePerditaEsercizio + (float)$CostiProduzioneAccantonamentiRischi + (float)$CostiProduzioneAltriAccantonamenti + (float)$CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni - (float)$CreditiImposteAnticipateTotaleImposteAnticipate) / (float)$TotaleAttivo) * 100, 2, ',', '.');
                $dataAnalisis['CF_Attivo'] = $CF_ATTIVO . '%';
            }

            $arrayConVoci['CF_Attivo'] = array('PatrimonioNettoUtilePerditaEsercizio', 'CostiProduzioneAccantonamentiRischi', 'CostiProduzioneAltriAccantonamenti', 'CostiProduzioneAmmortamentiSvalutazioniTotaleAmmortamentiSvalutazioni', 'CreditiImposteAnticipateTotaleImposteAnticipate', 'TotaleAttivo');


            //Indice di Indebitamento (PFN/PN)
            if ($TotalePatrimonioNetto == 0) {
                $MOLannoCorrente = (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
                $Indice_di_Indebitamento = number_format((float)(($MOLannoCorrente - (float)$TotaleDisponibilitaLiquide - (float)$ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / 0.1) * 100, 2, ',', '.');
                $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento . '%';
            } else {
                $MOLannoCorrente = (float)$DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo + (float)$DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo + (float)$DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo;
                $Indice_di_Indebitamento = number_format((float)(($MOLannoCorrente - (float)$TotaleDisponibilitaLiquide - (float)$ImmobilizzazioniFinanziarieCreditiTotaleCrediti) / (float)$TotalePatrimonioNetto) * 100, 2, ',', '.');
                $dataAnalisis['Indice_di_Indebitamento'] = $Indice_di_Indebitamento . '%';
            }

            $arrayConVoci['Indice_di_Indebitamento'] = array('DebitiObbligazioniEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniEsigibiliOltreEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliEntroEsercizioSuccessivo', 'DebitiObbligazioniConvertibiliEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoSociFinanziamentiEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoBancheEsigibiliOltreEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliEntroEsercizioSuccessivo', 'DebitiDebitiVersoAltriFinanziatoriEsigibiliOltreEsercizioSuccessivo', 'TotaleDisponibilitaLiquide', 'ImmobilizzazioniFinanziarieCreditiTotaleCrediti', 'TotalePatrimonioNetto');

            $indiciBilancio = array();

            //SALDO DEBITI VS FISCO

            $DebitiDebitiTributariTotaleDebitiTributariCorrente = (isset($bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari) ? $bilancioJSON->DebitiDebitiTributariTotaleDebitiTributari : 0);
            $FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente = (isset($bilancioJSON->FondiRischiOneriTrattamentoQuiescenzaObblighiSimili) ? $bilancioJSON->FondiRischiOneriTrattamentoQuiescenzaObblighiSimili : 0);
            $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente = isset($bilancioJSONprev->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate) ? $bilancioJSONprev->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate : 0;
            $ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate = isset($bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate) ? $bilancioJSON->ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate : 0;

            $DifferenzaImposteReddito = ($ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipate + (float)$ImposteRedditoEsercizioCorrentiDifferiteAnticipateTotaleImposteRedditoEsercizioCorrentiDifferiteAnticipatePrecedente) / 2;
            if ($DifferenzaImposteReddito == 0) {
                $SaldoDebitiVSFisco = number_format(($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + (float)$DebitiDebitiTributariTotaleDebitiTributariCorrente) / 1, 3, ',', '.');
                $dataAnalisis['Saldo_dei_Debiti_verso_il_Fisco'] = $SaldoDebitiVSFisco;
            } else {
                $SaldoDebitiVSFisco = number_format(($FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente + (float)$DebitiDebitiTributariTotaleDebitiTributariCorrente) / (float)$DifferenzaImposteReddito, 3, ',', '.');
                $dataAnalisis['Saldo_dei_Debiti_verso_il_Fisco'] = $SaldoDebitiVSFisco;
            }

            $arrayConVoci['Saldo_dei_Debiti_verso_il_Fisco'] = array('FondiRischiOneriTrattamentoQuiescenzaObblighiSimiliCorrente', 'DebitiDebitiTributariTotaleDebitiTributariCorrente', 'DifferenzaImposteReddito');
        } catch (Exception $e) {
            dd($e);
            if ($e->getMessage() == 'Division by zero') {
                $msg = 'È stato effettuata una divisione per zero, ricontrollare i dati inseriti';
                return view('errors.divisionbyzero', compact(['msg']));
            }
        }
        $bilanciHelper = new BilanciHelper;

        $explodedDate = new DateTime((explode(' ', $bilancio->year))[0]);

        $valutazioneBilancio = $bilanciHelper->valutazioneIndici($dataAnalisis, $tipoAzienda, $explodedDate->format('Y'));

        $indiciImportanti = array();

        foreach ($arrayConVoci as $indice => $formulaIndici) {
            $indiciImportanti = array_merge($indiciImportanti, $formulaIndici);
        }

        $indiciImportanti = array_unique($indiciImportanti);

        $soglieBasicAlert = array();

        $dataAnalisisBasic['OF_Fatturato'] = str_replace(',', '.', $dataAnalisisBasic['OF_Fatturato']);
		if(isset($dataAnalisisBasic['Adeguatezza_Patrimoniale'])) {
        $dataAnalisisBasic['Adeguatezza_Patrimoniale'] = str_replace(',', '.', $dataAnalisisBasic['Adeguatezza_Patrimoniale']);
		} else {$dataAnalisisBasic['Adeguatezza_Patrimoniale'] = 0;}

		if(isset($dataAnalisisBasic['Liquidità'])) {
        $dataAnalisisBasic['Liquidità'] = str_replace(',', '.', $dataAnalisisBasic['Liquidità']);
		}	else {$dataAnalisisBasic['Liquidità'] = 0;}

		if(isset($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'])) {
        $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'] = str_replace(',', '.', $dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']);
		}	else {$dataAnalisisBasic['Indebitamento_Previdenziale_Tributario'] = 0;}

		if(isset($dataAnalisisBasic['Current_Ratio'])) {
        $dataAnalisis['Current_Ratio'] = str_replace(',', '.', $dataAnalisis['Current_Ratio']);
}	else {$dataAnalisis['Current_Ratio'] = 0;}
        $basicData = array();

        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Sostenibilità Oneri Finanziari')->where('soglia', '>', floatval($dataAnalisisBasic['OF_Fatturato']))->count()) {
            $soglieBasic['Sostenibilità Oneri Finanziari'] = true;
            $basicData['Sostenibilità Oneri Finanziari'] = floatval($dataAnalisisBasic['OF_Fatturato']);
        } else {
            $soglieBasic['Sostenibilità Oneri Finanziari'] = false;
            $basicData['Sostenibilità Oneri Finanziari'] = floatval($dataAnalisisBasic['OF_Fatturato']);
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Adeguatezza Patrimoniale')->where('soglia', '<', floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']))->count()) {
            $soglieBasic['Adeguatezza Patrimoniale'] = true;
            $basicData['Adeguatezza Patrimoniale'] = floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']);
        } else {
            $soglieBasic['Adeguatezza Patrimoniale'] = false;
            $basicData['Adeguatezza Patrimoniale'] = floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']);
        }

        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Liquidità')->where('soglia', '<', floatval($dataAnalisisBasic['Liquidità']))->count()) {
            $soglieBasic['Liquidità'] = true;
            $basicData['Liquidità'] = floatval($dataAnalisisBasic['Liquidità']);
        } else {
            $soglieBasic['Liquidità'] = false;
            $basicData['Liquidità'] = floatval($dataAnalisisBasic['Liquidità']);
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Indebitamento Previdenziale Tributario')->where('soglia', '>', floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']))->count()) {
            $soglieBasic['Indebitamento Previdenziale Tributario'] = true;
            $basicData['Indebitamento Previdenziale Tributario'] = floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']);
        } else {
            $soglieBasic['Indebitamento Previdenziale Tributario'] = false;
            $basicData['Indebitamento Previdenziale Tributario'] = floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']);
        }
        if (DB::table('rangesBasic')->where('tipo_azienda', '=', $tipoAzienda)->where('indice', '=', 'Ritorno Liquido Attivo')->where('soglia', '<', floatval($dataAnalisis['Current_Ratio']))->count()) {
            $soglieBasic['Ritorno Liquido Attivo'] = true;
            $basicData['Ritorno Liquido Attivo'] = floatval($dataAnalisis['Current_Ratio']);
        } else {
            $soglieBasic['Ritorno Liquido Attivo'] = false;
            $basicData['Ritorno Liquido Attivo'] = floatval($dataAnalisis['Current_Ratio']);
        }


        // dd(floatval($dataAnalisis['Current_Ratio']), floatval($dataAnalisisBasic['OF_Fatturato']), floatval($dataAnalisisBasic['Adeguatezza_Patrimoniale']), floatval($dataAnalisisBasic['Liquidità']), floatval($dataAnalisisBasic['Indebitamento_Previdenziale_Tributario']));

        $valutazioneAllertaBasic = true;

        foreach ($soglieBasic as $label => $alert) {
            if ($alert) {
                $valutazioneAllertaBasic = false;
            }
        }

        $sistemaBasic = DB::table('basic')->where('bilancio_id', '=', $idBilancio)->get()->toArray();

        return response()->json([
            'error' => 'false',
            'basicData' =>  $basicData,
            'dataAnalisisBasic' =>  $dataAnalisisBasic,
            'sistemaBasic' =>  $sistemaBasic,
            'idBilancio' =>  $idBilancio,
            'salariStipendi' =>  $salariStipendi,
            'formaGiuridica' =>  $formaGiuridica,
            'ValoreProduzioneRicaviVenditePrestazioni' =>  $ValoreProduzioneRicaviVenditePrestazioni,
            'valutazioneAllertaBasic' =>  $valutazioneAllertaBasic,
            'soglieBasic' =>  $soglieBasic,
            'nomeAzienda' =>  $nomeAzienda,
            'dataAnalisisBasic' =>  $dataAnalisisBasic,
            'dataAnalisis' =>  $dataAnalisis,
            'valutazioneBilancio' =>  $valutazioneBilancio,
            'jsonData' =>  $jsonData,
            'gradi' =>  $gradi,
            'vociExt' =>  $vociExt,
            'tipoAzienda' =>  $tipoAzienda,
            'arrayConVoci' =>  $arrayConVoci,
        ]);
        return view('analisis.show', compact('basicData', 'dataAnalisisBasic', 'sistemaBasic', 'idBilancio', 'salariStipendi', 'formaGiuridica', 'ValoreProduzioneRicaviVenditePrestazioni', 'valutazioneAllertaBasic', 'soglieBasic', 'nomeAzienda', 'dataAnalisisBasic', 'dataAnalisis', 'valutazioneBilancio', 'jsonData', 'gradi', 'vociExt', 'tipoAzienda', 'arrayConVoci'));
    }

    public function analisiBasic(Request $request)
    {
        $idBilancio = $request->input('idBilancio');
        $allData = $request->all();

        // dd($allData);

        if (DB::table('basic')->where('bilancio_id', '=', $idBilancio)->count() == 0) {
            DB::table('basic')->insert([
                'bilancio_id' => $allData["idBilancio"],
                'DSCR' => $allData["DSCR"],
                'alertDSCR' => $allData['alertDSCR'],
                'DSCRDate' => $allData["DSCRDate"],
                'DSCRdispLiquida' => $allData["DSCRdispLiquida"],
                'entrataDSCRCFmese1' => $allData["entrataDSCRCFmese1"],
                'entrataDSCRCFmese2' => $allData["entrataDSCRCFmese2"],
                'entrataDSCRCFmese3' => $allData["entrataDSCRCFmese3"],
                'entrataDSCRCFmese4' => $allData["entrataDSCRCFmese4"],
                'entrataDSCRCFmese5' => $allData["entrataDSCRCFmese5"],
                'entrataDSCRCFmese6' => $allData["entrataDSCRCFmese6"],
                'uscitaDSCRCFmese1' => $allData["uscitaDSCRCFmese1"],
                'uscitaDSCRCFmese2' => $allData["uscitaDSCRCFmese2"],
                'uscitaDSCRCFmese3' => $allData["uscitaDSCRCFmese3"],
                'uscitaDSCRCFmese4' => $allData["uscitaDSCRCFmese4"],
                'uscitaDSCRCFmese5' => $allData["uscitaDSCRCFmese5"],
                'uscitaDSCRCFmese6' => $allData["uscitaDSCRCFmese6"],
                'rimborsoDSCRmese1' => $allData["rimborsoDSCRmese1"],
                'rimborsoDSCRmese2' => $allData["rimborsoDSCRmese2"],
                'rimborsoDSCRmese3' => $allData["rimborsoDSCRmese3"],
                'rimborsoDSCRmese4' => $allData["rimborsoDSCRmese4"],
                'rimborsoDSCRmese5' => $allData["rimborsoDSCRmese5"],
                'rimborsoDSCRmese6' => $allData["rimborsoDSCRmese6"],
                'agenziaEntrate1' => $allData["agenziaEntrate1"],
                'agenziaEntrate3' => $allData["agenziaEntrate3"],
                'agenziaEntrate2' => $allData["agenziaEntrate2"],
                'agenziaEntrate4' => $allData["agenziaEntrate4"],
                'alertAgenziaEntrate' => $allData['alertAgenziaEntrate'],
                'INPS1' => $allData["INPS1"],
                'INPS2' => $allData["INPS2"],
                'INPS3' => $allData["INPS3"],
                'alertINPS' => $allData['alertINPS'],
                'riscossione' => $allData["riscossione"],
                'alertRiscossione' => $allData['alertRiscossione'],
                'retribuzioni1' => $allData["retribuzioni1"],
                'retribuzioni2' => $allData["retribuzioni2"],
                'retribuzioni3' => $allData["retribuzioni3"],
                'alertRetribuzioni' => $allData['alertRetribuzioni'],
                'fornitori1' => $allData["fornitori1"],
                'fornitori2' => $allData["fornitori2"],
                'alertFornitori' => $allData['alertFornitori']
            ]);
        } else {
            DB::table('basic')->where('bilancio_id', '=', $idBilancio)->update([
                'bilancio_id' => $allData["idBilancio"],
                'DSCR' => $allData["DSCR"],
                'alertDSCR' => $allData['alertDSCR'],
                'DSCRDate' => $allData["DSCRDate"],
                'DSCRdispLiquida' => $allData["DSCRdispLiquida"],
                'entrataDSCRCFmese1' => $allData["entrataDSCRCFmese1"],
                'entrataDSCRCFmese2' => $allData["entrataDSCRCFmese2"],
                'entrataDSCRCFmese3' => $allData["entrataDSCRCFmese3"],
                'entrataDSCRCFmese4' => $allData["entrataDSCRCFmese4"],
                'entrataDSCRCFmese5' => $allData["entrataDSCRCFmese5"],
                'entrataDSCRCFmese6' => $allData["entrataDSCRCFmese6"],
                'uscitaDSCRCFmese1' => $allData["uscitaDSCRCFmese1"],
                'uscitaDSCRCFmese2' => $allData["uscitaDSCRCFmese2"],
                'uscitaDSCRCFmese3' => $allData["uscitaDSCRCFmese3"],
                'uscitaDSCRCFmese4' => $allData["uscitaDSCRCFmese4"],
                'uscitaDSCRCFmese5' => $allData["uscitaDSCRCFmese5"],
                'uscitaDSCRCFmese6' => $allData["uscitaDSCRCFmese6"],
                'rimborsoDSCRmese1' => $allData["rimborsoDSCRmese1"],
                'rimborsoDSCRmese2' => $allData["rimborsoDSCRmese2"],
                'rimborsoDSCRmese3' => $allData["rimborsoDSCRmese3"],
                'rimborsoDSCRmese4' => $allData["rimborsoDSCRmese4"],
                'rimborsoDSCRmese5' => $allData["rimborsoDSCRmese5"],
                'rimborsoDSCRmese6' => $allData["rimborsoDSCRmese6"],
                'agenziaEntrate1' => $allData["agenziaEntrate1"],
                'agenziaEntrate2' => $allData["agenziaEntrate2"],
                'agenziaEntrate3' => $allData["agenziaEntrate3"],
                'agenziaEntrate4' => $allData["agenziaEntrate4"],
                'alertAgenziaEntrate' => $allData['alertAgenziaEntrate'],
                'INPS1' => $allData["INPS1"],
                'INPS2' => $allData["INPS2"],
                'INPS3' => $allData["INPS3"],
                'alertINPS' => $allData['alertINPS'],
                'riscossione' => $allData["riscossione"],
                'alertRiscossione' => $allData['alertRiscossione'],
                'retribuzioni1' => $allData["retribuzioni1"],
                'retribuzioni2' => $allData["retribuzioni2"],
                'retribuzioni3' => $allData["retribuzioni3"],
                'alertRetribuzioni' => $allData['alertRetribuzioni'],
                'fornitori1' => $allData["fornitori1"],
                'fornitori2' => $allData["fornitori2"],
                'alertFornitori' => $allData['alertFornitori']
            ]);
        }
        return back();
    }

    /**
     * Show the form for editing the specified analisi.
     *
     * @param int $id
     *
     * @return Illuminate\View\View
     */
    public function edit($id)
    {
        $analisi = Analisi::findOrFail($id);
        $bilancis = Bilanci::pluck('name', 'id')->all();
        $accounts = Account::pluck('name', 'id')->all();
        $analisisType = AnalisisType::pluck('name', 'id')->all();

        return view('analisis.edit', compact('analisi', 'bilancis', 'accounts', 'analisisType'));
    }

    /**
     * Update the specified analisi in the storage.
     *
     * @param int $id
     * @param Illuminate\Http\Request $request
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function update($id, Request $request)
    {

        try {

            $data = $this->getData($request);

            $analisi = Analisi::findOrFail($id);
            $analisi->update($data);

            return redirect()->route('admin.analisis.analisi.index')
                ->with('success_message', 'Analisi was successfully updated.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }

    /**
     * Remove the specified analisi from the storage.
     *
     * @param int $id
     *
     * @return Illuminate\Http\RedirectResponse | Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        try {
            $analisi = Analisi::findOrFail($id);
            $analisi->delete();

            return redirect()->route('admin.analisis.analisi.index')
                ->with('success_message', 'Analisi was successfully deleted.');
        } catch (Exception $exception) {

            return back()->withInput()
                ->withErrors(['unexpected_error' => 'Unexpected error occurred while trying to process your request.']);
        }
    }


    /**
     * Get the request's data from the request.
     *
     * @param Illuminate\Http\Request\Request $request
     * @return array
     */
    protected function getData(Request $request)
    {
        $rules = [
            'bilanci_id' => 'required',
            'account_id' => 'required',

        ];


        $data = $request->validate($rules);




        return $data;
    }
}
