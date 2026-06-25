<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatement extends Model
{
    protected $fillable = [
        'company_id',
        'filename',
        'filepath',
        'bank_name',
        'iban',
        'fido_accordato',
        'date_from',
        'date_to',
        'status',
        'analysis_data',
        'error_message',
    ];

    protected $casts = [
        'fido_accordato' => 'float',
        'date_from'      => 'date',
        'date_to'        => 'date',
        'analysis_data'  => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(BankStatementEntry::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
