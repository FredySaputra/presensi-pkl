<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siswa extends Model
{
    use HasFactory, SoftDeletes; 
    protected $fillable = [
        'sekolah_id',
        'id_kartu',
        'nama_siswa',
        'mulai_pkl',
        'selesai_pkl',
    ];

    
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    
    public function presensis(): HasMany
    {
        return $this->hasMany(Presensi::class);
    }
}
