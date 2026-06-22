<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
		          'company_id',
                  'taxonomy',
                  'user_id', 
                  'nome_azienda', 
                  'anno_inizio', 
                  'anno_fine',
                  'forma_giuridica',
                  'tipo_azienda',
                  'predefinito'
              ];


}
