<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use App\Models\Analisi;

class Basic extends Model
{
    use HasFactory;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'basic';



    protected $primaryKey = 'id';

    public $incrementing = true;




    protected $fillable = [
        'bilancio_id',
        'DSCR',
        'DSCRdispLiquida',
        'entrataDSCRCFmese1',
        'entrataDSCRCFmese2',
        'entrataDSCRCFmese3',
        'entrataDSCRCFmese4',
        'entrataDSCRCFmese5',
        'entrataDSCRCFmese6',
        'uscitaDSCRCFmese1',
        'uscitaDSCRCFmese2',
        'uscitaDSCRCFmese3',
        'uscitaDSCRCFmese4',
        'uscitaDSCRCFmese5',
        'uscitaDSCRCFmese6',
        'rimborsoDSCRmese1',
        'rimborsoDSCRmese2',
        'rimborsoDSCRmese3',
        'rimborsoDSCRmese4',
        'rimborsoDSCRmese5',
        'rimborsoDSCRmese6',
        'agenziaEntrate1',
        'agenziaEntrate3',
        'agenziaEntrate2',
        'agenziaEntrate4',
        'INPS1',
        'INPS2',
        'INPS3',
        'alertINPS',
        'riscossione',
        'retribuzioni1',
        'retribuzioni2',
        'retribuzioni3',
        'fornitori1',
        'fornitori2',
        "alertDSCR",
        "alertAgenziaEntrate",
        "alertINPS",
        "alertRiscossione",
        "alertRetribuzioni",
        "alertFornitori"
    ];
}
