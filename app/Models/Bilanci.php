<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Account;
use App\Models\Analisi;

class Bilanci extends Model
{
    use HasFactory;


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bilanci';



    protected $primaryKey = 'id';

    public $incrementing = true;




    protected $fillable = ['id', 'json_data', 'name', 'account_id', 'json_data_prev', 'json_data_anag', 'current_year', 'prev_year', 'year', 'tipo_azienda', 'forma_giuridica', 'provvisorio', 'predefinito', 'company_id'];


    /**
     * Get the phone associated with the user.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the bilanci for this model.
     *
     * @return App\Models\Bilanci
     */
    public function analisis()
    {
        return $this->belongsTo(Analisi::class);
    }
}
