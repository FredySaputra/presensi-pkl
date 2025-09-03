<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sekolah extends Model
{
    use HasFactory, SoftDeletes; 

    protected $fillable = [
        'nama_sekolah',
        'hari_libur',
    ];

     protected function setNamaSekolahAttribute($value)
    {
     
        $this->attributes['nama_sekolah'] = Str::upper($value);
    }
    
    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }
}
