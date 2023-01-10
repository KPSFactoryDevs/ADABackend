<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisiAgenziaEntrate extends Model
{
    use HasFactory;

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analisi_agenziaEntrate';



    protected $primaryKey = 'id';

    public $incrementing = true;




    protected $fillable = [
        'document_id',
        'agenziaEntrate1',
        'agenziaEntrate3',
        'agenziaEntrate2',
        'agenziaEntrate4',
        "alertAgenziaEntrate",
    ];
}
