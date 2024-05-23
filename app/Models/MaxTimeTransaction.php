<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaxTimeTransaction extends Model
{
    use HasFactory;

    protected $table = 'max_time_transaction';

    protected $fillable = [
        'max_time',
        'created_at',
        'updated_at'
    ];
}
