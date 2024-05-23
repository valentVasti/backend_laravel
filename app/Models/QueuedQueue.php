<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueuedQueue extends Model
{
    use HasFactory;

    protected $table = 'queued_queue';

    protected $fillable = [
        'queue_id',
        'transaction_id',
        'nomor_antrian',
        'created_at',
        'updated_at'
    ];

    public function transaction(){
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function todayQueue(){
        return $this->belongsTo(TempQueue::class, 'queue_id', 'id');
    }
}
