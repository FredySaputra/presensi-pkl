<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use Illuminate\Http\Request;

class PresensiController extends Controller
{
    /**
     * Menampilkan form edit presensi.
     */
    public function edit(Presensi $presensi)
    {
        $presensi->load('siswa.sekolah');

        return view('admin.presensi.edit', compact('presensi'));
    }

    /**
     * Menyimpan pembaruan data presensi.
     */
    public function update(Request $request, Presensi $presensi)
    {
        $request->validate([
            'status'      => 'required|string|in:Hadir,Izin,Kurang,Pulang Cepat,Telat,Alpa',
            'jam_masuk'   => 'nullable|date_format:H:i',
            'jam_pulang'  => 'nullable|date_format:H:i|after_or_equal:jam_masuk',
            'keterangan'  => 'nullable|string|max:255',
            'metode_izin' => 'nullable|in:WA,Surat',
        ]);

        $presensi->update([
            'status'      => $request->status,
            'jam_masuk'   => $request->jam_masuk,
            'jam_pulang'  => $request->jam_pulang,
            'keterangan'  => $request->keterangan,
            'metode_izin' => $request->status === 'Izin' ? $request->metode_izin : null, 
        ]);

        return redirect()->route('admin.laporan.index')
                         ->with('success', 'Data presensi siswa berhasil diperbarui.');
    }
}
