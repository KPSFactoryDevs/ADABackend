<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model {
    protected $fillable = [
        'user_id','company_id','client_id','direction','external_id','date','due_date',
        'number','document_number','client_name','client_vat','amount_net','amount_gross',
        'payment_method','status','remote_url'
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'amount_net' => 'decimal:2',
        'amount_gross' => 'decimal:2',
    ];

    public function customer() {
        return $this->belongsTo(Client::class);
    }

    public function client()
{
    return $this->belongsTo(Client::class);
}
}
