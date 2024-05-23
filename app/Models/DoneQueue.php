<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoneQueue extends Model
{
    use HasFactory;

    protected $table = 'done_queue';

    protected $fillable = [
        'id_transaction',
        'pencuci',
        'pengering',
        'nomor_antrian',
        'done_at',
        'created_at',
        'updated_at'
    ];

    public function transaction(){
        return $this->belongsTo(Transaction::class, 'id_transaction', 'id');
    }

    public function pencuci(){
        return $this->belongsTo(Mesin::class, 'pencuci', 'id');
    }

    public function pengering(){
        return $this->belongsTo(Mesin::class, 'pengering', 'id');
    }
}
