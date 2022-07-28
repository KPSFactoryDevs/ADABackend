<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Bilanci;
use App\Models\cr;
use DateTime;

/**
 * Class DashboardController.
 */
class DashboardController extends Controller
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index()
    {





        $accounts = Account::All();
        $crs = cr::select('id', 'anno', 'mese', 'date')->get();

        $crData = array();

        $mesiList = [0 => 'gennaio', 1 => 'febbraio', 2 => 'marzo', 3 => 'aprile', 4 => 'maggio', 5 => 'giugno', 6 => 'luglio', 7 => 'agosto', 8 => 'settembre', 9 => 'ottobre', 10 => 'novembre', 11 => 'dicembre'];
        $formattedMonths = ['gennaio' => '01', 'febbraio' => '02', 'marzo' => '03', 'aprile' => '04', 'maggio' => '05', 'giugno' => '06', 'luglio' => '07', 'agosto' => '08', 'settembre' => '09', 'ottobre' => '10', 'novembre' => '11', 'dicembre' => '12'];

        foreach ($crs as $tmpLabel => $toFixData) {
            $tmpDate = new DateTime($toFixData->date);

            if ($mesiList[((int)$tmpDate->format('m') - 1)] != $toFixData->mese) {
                cr::where('id', $toFixData->id)->update(['date' => $tmpDate->format('Y') . '-' . $formattedMonths[$toFixData->mese] . '-' . $tmpDate->format('d')]);
            }
        }

        $banks = cr::select('nome_banca')->distinct()->get()->toArray();

        $periods = array();
        $latestYear = false;
        $latestMonth = false;
        $lastYear = false;
        $lastTwoYears = false;
        $lastThreeYears = false;
        $earliestDate = false;
        $lastDate = false;
        if (count($crs) > 0) {



            foreach ($crs as $data => $value) {
                $periods[$value->anno][$value->mese] = null;
                $periods[$value->anno][$value->mese] = array_search($value->mese, $mesiList);
                asort($periods[$value->anno]);
            }

            ksort($periods);

            $latestYear = array_key_last($periods);
            $latestMonth = array_key_last($periods[$latestYear]);

            $lastDate = new DateTime((cr::select('date')->where('anno', $latestYear)->where('mese', $latestMonth)->first())->date);

            $earliestDate = new DateTime((cr::select('date')->orderBy('date', 'asc')->first())->date);

            $lastYear = (new DateTime($lastDate->format('d-m-Y')))->modify('-11 months');

            if (cr::select('date')->where('date', $lastYear)->count() == 0) {
                $lastYear = new DateTime($earliestDate->format('d-m-Y'));
            }

            $lastTwoYears = (new DateTime($lastDate->format('d-m-Y')))->modify('-23 months');

            if (cr::select('date')->where('date', $lastTwoYears)->count() == 0) {
                $lastTwoYears = new DateTime($earliestDate->format('d-m-Y'));
            }

            $lastThreeYears = (new DateTime($lastDate->format('d-m-Y')))->modify('-35 months');

            if (cr::select('date')->where('date', $lastThreeYears)->count() == 0) {
                $lastThreeYears = new DateTime($earliestDate->format('d-m-Y'));
            }
        }
            $bilancis = Bilanci::paginate(25);

            return view('backend.dashboard', compact('bilancis', 'banks', 'lastYear', 'lastTwoYears', 'lastThreeYears', 'earliestDate', 'lastDate', 'periods', 'accounts', 'crData'));

    }


    public function faq()
    {
        return view('cortesia.faq');
    }

    public function Home()
    {
        return view('cortesia.Home');
    }

    public function page2()
    {
        return view('cortesia.page2');
    }

    public function page3()
    {
        return view('cortesia.page3');
    }

    public function manutenzione()
    {
        return view('cortesia.manutenzione');
    }
}

