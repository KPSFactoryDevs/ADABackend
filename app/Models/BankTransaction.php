<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
 use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id','company_id','date','method','title','subtitle',
        'amount','category_name','verified','meta'
    ];
    protected $casts = [
        'date' => 'date',
        'verified' => 'boolean',
        'meta' => 'array',
    ];

    /* ---- Scopes filtri ---- */
    public function scopeSearch(Builder $q, ?string $query): Builder {
        if (!$query) return $q;
        $like = '%'.mb_strtolower($query, 'UTF-8').'%';
        return $q->whereRaw('LOWER(title) LIKE ? OR LOWER(subtitle) LIKE ?', [$like, $like]);
    }
    public function scopeBetween(Builder $q, ?string $from, ?string $to): Builder {
        if ($from) $q->where('date','>=',$from);
        if ($to)   $q->where('date','<=',$to);
        return $q;
    }
    public function scopeType(Builder $q, ?string $type): Builder {
        if ($type === 'in')  return $q->where('amount','>',0);
        if ($type === 'out') return $q->where('amount','<',0);
        return $q;
    }
    public function scopeVerifiedState(Builder $q, ?string $v): Builder {
        // v: 'yes' | 'no' | 'todo'
        if ($v === 'yes') return $q->where('verified', true);
        if ($v === 'no')  return $q->where('verified', false);
        if ($v === 'todo')return $q->whereNull('verified');
        return $q;
    }
    public function scopeCategory(Builder $q, ?string $cat): Builder {
        if (!$cat) return $q;
        return $q->where('category_name', $cat);
    }

        public function account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}


