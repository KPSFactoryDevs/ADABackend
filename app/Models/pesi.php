<?php

namespace App\Models;

use App\Models\range;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pesi extends Model
{
    use HasFactory;

   
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pesi';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
                    'peso',
                  'indice',
                  'range_id'
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
    
	
	
    public function range()
    {
        return $this->belongsTo(range::class);
    }
}
