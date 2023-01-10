<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisiRetribuzioni extends Model
{
    use HasFactory;

     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analisi_retribuzioni';



    protected $primaryKey = 'id';

    public $incrementing = true;




    protected $fillable = [
        'document_id',
        'retribuzioni1',
        'retribuzioni2',
        'retribuzioni3',
        "alertRetribuzioni",
    ];
}
