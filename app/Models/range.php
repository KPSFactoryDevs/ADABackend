<?php

namespace App\Models;

use App\Models\pesi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class range extends Model
{
    use HasFactory;
 
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'ranges';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
                  'pesi_id',
                  'indice',
                  'range_min',
                  'range_max',
                  'score'
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
    


    public function pesi()
    {
        return $this->belongsTo(pesi::class);
    }

}
