<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempQueue extends Model
{
    use HasFactory;

    protected $table = 'temp_queue';

    protected $fillable = [
        'id_transaction',
        'id_mesin',
        'nomor_antrian',
        'layanan',
        'status',
        'next_queue_id',
        'created_at',
        'updated_at'
    ];

    public function mesin(){
        return $this->belongsTo(Mesin::class, 'id_mesin', 'id');
    }

    public function queuedQueue(){
        return $this->hasMany(QueuedQueue::class, 'queue_id', 'id');
    }

    public function transaction(){
        return $this->belongsTo(Transaction::class, 'id_transaction', 'id');
    }
}
