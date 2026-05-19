<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'siswa_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'status',
        'keterangan',
        'metode_izin',
        'menit_telat',
        'menit_pulang_cepat'
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
