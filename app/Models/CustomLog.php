<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomLog extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'custom_log';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
                  'log_type',
                  'message',
                  'document_id',
                  'user_id',
                  'type',
              ];


    /**
     * Get created_at in array format
     *
     * @param  string  $value
     * @return array
     */
    public function getCreatedAtAttribute($value)
    {
        return \DateTime::createFromFormat($this->getDateFormat(), $value)->format('j/n/Y g:i A');
    }


    function addToLogCR($logType, $message, $documentId, $userId)
    {
        $log = [];
        $log['log_type'] = $logType;
        $log['message'] = $message;
        $log['document_id'] = $documentId ? $documentId : 0;

        static::create($log);

        return true;
    }


    function addToLogBilanci($logType, $message)
    {
        $log = [];
        $log['log_type'] = $logType;
        $log['message'] = $message;

        static::create($log);

        return true;
    }

}
