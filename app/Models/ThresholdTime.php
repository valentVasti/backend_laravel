<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThresholdTime extends Model
{
    use HasFactory;

    protected $table = 'threshold_time';

    protected $fillable = [
        'threshold_time'
    ];
}
