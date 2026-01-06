<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FicToken extends Model
{
    protected $fillable = [
        'user_id','company_id','scope','access_token','refresh_token','expires_at'
    ];
    protected $casts = [
        'expires_at' => 'datetime',
    ];
    public function isExpired(int $leewaySeconds = 60): bool {
        return Carbon::now()->addSeconds($leewaySeconds)->gte($this->expires_at);
    }
}
