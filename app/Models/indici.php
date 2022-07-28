<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AnalisisType;

class indici extends Model
{


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'indicis';

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
                  'name',
                  'formula',
                  'analisistype_id'
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
     * Get the Analisistype for this model.
     *
     * @return App\Models\Analisistype
     */
    public function Analisistype()
    {
        return $this->belongsTo('App\Models\Analisistype','analisistype_id','id');

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
