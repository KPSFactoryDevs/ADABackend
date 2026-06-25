<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementEntry extends Model
{
    protected $fillable = [
        'bank_statement_id',
        'date_operazione',
        'date_valuta',
        'descrizione',
        'dare',
        'avere',
        'saldo',
    ];

    protected $casts = [
        'date_operazione' => 'date',
        'date_valuta'     => 'date',
        'dare'            => 'float',
        'avere'           => 'float',
        'saldo'           => 'float',
    ];

    public function statement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }
}
