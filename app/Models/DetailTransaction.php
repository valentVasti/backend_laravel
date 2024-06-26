<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTransaction extends Model
{
    use HasFactory;

    protected $table = 'detail_transaction';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity',
        'total_price',
        'created_at',
        'updated_at'
    ];

    public function transaction(){
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
