<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'company_id', 'name', 'bank_code', 'iban', 'currency',
        'balance_cached', 'provider', 'provider_account_id'
    ];

    public function transactions(): HasMany {
        return $this->hasMany(BankTransaction::class);
    }

    public function recalcBalance(): void {
        $bal = $this->transactions()->sum('amount');
        $this->balance_cached = $bal;
        $this->save();
    }

    protected $appends = ['balance'];
public function getBalanceAttribute()
{
    return (float) $this->transactions()->sum('amount');
}

}
