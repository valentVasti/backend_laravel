<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mesin extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mesin';

    protected $fillable = [
        'kode_mesin',
        'jenis_mesin',
        'identifier',
        'durasi_penggunaan',
        'status_maintenance',
        'created_at',
        'updated_at'
    ];

    public function queue(){
        return $this->hasMany(TempQueue::class, 'id_mesin', 'id');
    }
    
    public function pencuci(){
        return $this->hasMany(DoneQueue::class, 'pencuci', 'id');
    }
    
    public function pengering(){
        return $this->hasMany(DoneQueue::class, 'pengering', 'id');
    }

}
