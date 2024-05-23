<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transaction';

    protected $fillable = [
        'user_id',
        'karyawan_id',
        'paying_method',
        'total',
        'paid_sum',
        'change',
        'transaction_from',
        'created_at',
        'updated_at'
    ];

    public function detailTransaction()
    {
        return $this->hasMany(DetailTransaction::class, 'transaction_id', 'id');
    }

    public function transactionToken()
    {
        return $this->hasOne(TransactionToken::class, 'transaction_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function karyawan()
    {
        return $this->belongsTo(User::class, 'karyawan_id', 'id');
    }

    public function queuedQueues()
    {
        return $this->hasMany(QueuedQueue::class);
    }

    public function tempQueue()
    {
        return $this->hasMany(TempQueue::class);
    }
}
