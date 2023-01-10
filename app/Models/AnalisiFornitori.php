<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisiFornitori extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analisi_fornitori';



    protected $primaryKey = 'id';

    public $incrementing = true;




    protected $fillable = [
        'document_id',
        'fornitori1',
        'fornitori2',
        "alertFornitori"
    ];
}
