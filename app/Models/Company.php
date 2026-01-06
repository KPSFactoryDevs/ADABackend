<?php

// app/Models/Company.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model {
    protected $fillable = [
        'user_id','partita_iva','ragione_sociale','indirizzo','provincia','citta',
        'cap','ateco','capitale_sociale','pec','ultimo_bilancio','fatturato','settore','telefono'
    ];

    public function user() {
        return $this->belongsTo(\App\Domains\Auth\Models\User::class);
    }
}
