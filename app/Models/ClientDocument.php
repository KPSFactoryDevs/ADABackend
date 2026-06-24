<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientDocument extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'company_id',
        'type',           // 'bilancio' | 'cr' | 'fattura_xml'
        'filename',
        'original_filename',
        'path',
        'mime_type',
        'size',
        'extracted_data',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'size'           => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Domains\Auth\Models\User::class);
    }

    /* ----- Scope helpers ----- */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBilancio($query)
    {
        return $query->ofType('bilancio');
    }

    public function scopeCr($query)
    {
        return $query->ofType('cr');
    }

    public function scopeFatturaXml($query)
    {
        return $query->ofType('fattura_xml');
    }
}
