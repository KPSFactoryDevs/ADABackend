<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisiDscr extends Model
{
    use HasFactory;

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analisi_dscr';



    protected $primaryKey = 'id';

    public $incrementing = true;




    protected $fillable = [
        'document_id',
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
        'alertDSCR',
        'resultDSCR',
        'DSCRDate'
    ];
}
