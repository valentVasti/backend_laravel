<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedQueue extends Model
{
    use HasFactory;
    
    protected $table = 'failed_queue';

    protected $fillable = [
        'transaction_id',
        'nomor_antrian',
        'failed_at',
        'created_at',
        'updated_at'
    ];
}
