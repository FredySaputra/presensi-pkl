<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sekolah extends Model
{
    use HasFactory, SoftDeletes; 

    protected $fillable = [
        'nama_sekolah',
    ];

    
    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }
}
