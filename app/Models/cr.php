<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class cr extends Model
{


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'crs';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;


    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
                  'account_id',
                  'anno',
                  'mese',
                  'nome_banca',
                  'sezione',
                  'categoria',
                  'accordato',
                  'accordato_operativo',
                  'utilizzato',
                  'durata_residua',
                  'durata_originaria',
                  'localizzazione',
                  'divisa',
                  'tipo_garanzia',
                  'stato_rapporto',
                  'tipo_attivita',
                  'ruolo_affidato',
                  'import_export'
              ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * Get the account for this model.
     *
     * @return App\Models\Account
     */
    public function account()
    {
        return $this->belongsTo('App\Models\Account','account_id');
    }


    /**
     * Get created_at in array format
     *
     * @param  string  $value
     * @return array
     */
    public function getCreatedAtAttribute($value)
    {
        return \DateTime::createFromFormat($this->getDateFormat(), $value)->format('j/n/Y g:i A');
    }

    /**
     * Get updated_at in array format
     *
     * @param  string  $value
     * @return array
     */
    public function getUpdatedAtAttribute($value)
    {
        return \DateTime::createFromFormat($this->getDateFormat(), $value)->format('j/n/Y g:i A');
    }

}
