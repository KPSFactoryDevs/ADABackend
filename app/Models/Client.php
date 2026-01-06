<?php

// app/Models/Client.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'company_id','user_id','nome','piva','vat_number','regione','citta','email',
        'fatturato','rating','status','is_customer','is_supplier',
    ];

    protected $casts = [
        'is_customer'=>'bool',
        'is_supplier'=>'bool',
    ];

    public function company(){ return $this->belongsTo(Company::class); }
    public function user(){ return $this->belongsTo(\App\Domains\Auth\Models\User::class); }

      public function invoices() {
        return $this->hasMany(Invoice::class);
    }


        // opzionale: scope comodi
    public function scopeCustomers($q){ return $q->where('is_customer', 1); }
    public function scopeSuppliers($q){ return $q->where('is_supplier', 1); }
}
