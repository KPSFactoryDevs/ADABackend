<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'documenti';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
                  'codice_documento',
                  'status',
                  'path',
                  'filename',
                  'type',
              ];


    protected $attributes = [
        'codice_documento' => '1234',
        'status' => 'Da Elaborare'
    ];
}
