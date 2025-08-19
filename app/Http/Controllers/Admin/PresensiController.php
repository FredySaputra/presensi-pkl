<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use Illuminate\Http\Request;

class PresensiController extends Controller
{
    /**
     * Menampilkan form untuk mengedit data presensi.
     */
    public function edit(Presensi $presensi)
    {
        return view('admin.presensi.edit', compact('presensi'));
    }

    /**
     * Memperbarui data presensi di database.
     */
    public function update(Request $request, Presensi $presensi)
    {
        $request->validate([
            'status' => 'required|in:Hadir,Izin,Alpa',
            'jam_masuk' => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i|after_or_equal:jam_masuk',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $dataToUpdate = $request->only(['status', 'keterangan']);

        // Sesuaikan data berdasarkan status
        if ($request->status == 'Hadir') {
            $dataToUpdate['jam_masuk'] = $request->jam_masuk;
            $dataToUpdate['jam_pulang'] = $request->jam_pulang;
            $dataToUpdate['keterangan'] = null; // Kosongkan keterangan jika statusnya Hadir
        } else {
            $dataToUpdate['jam_masuk'] = null;
            $dataToUpdate['jam_pulang'] = null;
        }

        $presensi->update($dataToUpdate);

        return redirect()->route('admin.laporan.index')->with('success', 'Data presensi berhasil diperbarui.');
    }
}
