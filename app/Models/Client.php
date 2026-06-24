<?php

// app/Models/Client.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'company_id','user_id','nome','piva','vat_number','regione','citta','email',
        'fatturato','rating','status','is_customer','is_supplier',
        // Factoring
        'bilancio_score','cr_score',
        'has_invoices','has_bilancio','has_cr',
        'evaluation_status','evaluation_sent_at',
    ];

    protected $casts = [
        'is_customer'     => 'bool',
        'is_supplier'     => 'bool',
        'has_invoices'    => 'bool',
        'has_bilancio'    => 'bool',
        'has_cr'          => 'bool',
        'bilancio_score'  => 'decimal:2',
        'cr_score'        => 'decimal:2',
        'evaluation_sent_at' => 'datetime',
    ];

    public function company(){ return $this->belongsTo(Company::class); }
    public function user(){ return $this->belongsTo(\App\Domains\Auth\Models\User::class); }

    public function invoices() {
        return $this->hasMany(Invoice::class);
    }

    public function documents() {
        return $this->hasMany(ClientDocument::class);
    }

    // opzionale: scope comodi
    public function scopeCustomers($q){ return $q->where('is_customer', 1); }
    public function scopeSuppliers($q){ return $q->where('is_supplier', 1); }
}
