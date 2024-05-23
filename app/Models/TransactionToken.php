<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionToken extends Model
{
    use HasFactory;

    protected $table = 'transaction_token';

    protected $fillable = [
        'transaction_id',
        'token',
        'is_used',
        'expires_at',
        'created_at',
        'updated_at'
    ];

    public function transaction(){
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }
}
