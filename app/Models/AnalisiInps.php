<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisiInps extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analisi_inps';



    protected $primaryKey = 'id';

    public $incrementing = true;




    protected $fillable = [
        'document_id',
        'INPS1',
        'INPS2',
        'INPS3',
        'alertINPS',
    ];
}
