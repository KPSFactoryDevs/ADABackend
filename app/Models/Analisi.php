<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Bilanci;
use App\Models\Account;
use App\Models\AnalisisType;

class Analisi extends Model
{


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'analisis';

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
                  'bilanci_id',
				  'account_id',
				  'analisistype_id',
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
     * Get the bilanci for this model.
     *
     * @return App\Models\Bilanci
     */
    public function bilanci()
    {
        return $this->belongsTo(Bilanci::class);
    }

   /**
     * Get the bilanci for this model.
     *
     * @return App\Models\Account
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

	public function analisistype() {
        return $this->belongsTo(AnalisisType::class);
    }


}
