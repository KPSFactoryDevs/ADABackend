<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisiRiscossione extends Model
{
    use HasFactory;

      /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analisi_riscossione';



    protected $primaryKey = 'id';

    public $incrementing = true;




    protected $fillable = [
        'document_id',
        'riscossione',
        "alertRiscossione",
    ];
}
