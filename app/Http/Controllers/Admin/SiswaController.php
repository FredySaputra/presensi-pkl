<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Carbon\Carbon; // Pastikan Carbon di-import

class SiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Ambil ID sekolah dari input filter
        $sekolahId = $request->input('sekolah_id');

        // Ambil semua data sekolah untuk ditampilkan di dropdown
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();

        // Bangun query siswa secara dinamis
        // PERUBAHAN: Tambahkan filter untuk hanya menampilkan siswa yang masa PKL-nya
        // lebih dari atau sama dengan tanggal hari ini.
        $query = Siswa::with('sekolah')->where('selesai_pkl', '>=', Carbon::today()->toDateString());

        // Jika ada sekolah yang dipilih, terapkan filter tambahan
        if ($sekolahId) {
            $query->where('sekolah_id', $sekolahId);
        }

        $siswas = $query->orderBy('nama_siswa', 'asc')->get();

        // Kirim semua data yang diperlukan ke view
        return view('admin.siswa.index', compact('siswas', 'sekolahs', 'sekolahId'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        return view('admin.siswa.create', compact('sekolahs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_siswa'  => 'required|string|max:255',
            'sekolah_id'  => 'required|exists:sekolahs,id',
            'id_kartu'    => 'required|string|max:100|unique:siswas,id_kartu',
            'mulai_pkl'   => 'required|date',
            'selesai_pkl' => 'required|date|after_or_equal:mulai_pkl',
        ]);
        Siswa::create($request->all());
        return redirect()->route('admin.siswa.index')
                         ->with('success', 'Data siswa berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Siswa $siswa)
    {
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        return view('admin.siswa.edit', compact('siswa', 'sekolahs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Siswa $siswa)
    {
        $request->validate([
            'nama_siswa'  => 'required|string|max:255',
            'sekolah_id'  => 'required|exists:sekolahs,id',
            'id_kartu'    => 'required|string|max:100|unique:siswas,id_kartu,' . $siswa->id,
            'mulai_pkl'   => 'required|date',
            'selesai_pkl' => 'required|date|after_or_equal:mulai_pkl',
        ]);
        $siswa->update($request->all());
        return redirect()->route('admin.siswa.index')
                         ->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Siswa $siswa)
    {
        $siswa->delete();
        return redirect()->route('admin.siswa.index')
                         ->with('success', 'Data siswa berhasil dihapus.');
    }
}
