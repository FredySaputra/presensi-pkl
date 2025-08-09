<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    protected function setNamaSiswaAttribute($value)
    {
     
        $this->attributes['nama_siswa'] = Str::upper($value);
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    
    public function presensis(): HasMany
    {
        return $this->hasMany(Presensi::class);
    }
}
