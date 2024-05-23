<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'product';

    protected $fillable = [
        'product_name',
        'price',
        'status',
        'created_at',
        'updated_at'
    ];

    public function detailTransaction(){
        return $this->hasMany(DetailTransaction::class, 'product_id', 'id');
    }
}
