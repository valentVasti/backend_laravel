<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenCloseQueueLog extends Model
{
    use HasFactory;

    protected $table = 'open_close_queue_log';

    protected $fillable = [
        'user_id_opener',
        'user_id_closer',
        'opened_at',
        'closed_at',
        'created_at',
        'updated_at'
    ];

    public function userOpener(){
        return $this->belongsTo(User::class, 'user_id_opener', 'id');
    }

    public function userCloser(){
        return $this->belongsTo(User::class, 'user_id_closer', 'id');
    }
}
