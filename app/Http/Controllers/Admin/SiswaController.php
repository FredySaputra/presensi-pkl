<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\SyncToLiveService;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $sekolahId = $request->input('sekolah_id');
        $search = $request->input('search');
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();

        $query = Siswa::with('sekolah')->where('selesai_pkl', '>=', Carbon::today()->toDateString());

        if ($sekolahId) {
            $query->where('sekolah_id', $sekolahId);
        }
        if ($search) {
            // Hapus pencarian id_kartu
            $query->where('nama_siswa', 'like', '%' . $search . '%');
        }

        $siswas = $query->orderBy('nama_siswa', 'asc')->paginate(15);
        return view('admin.siswa.index', compact('siswas', 'sekolahs', 'sekolahId', 'search'));
    }

    public function arsip(Request $request)
    {
        $sekolahId = $request->input('sekolah_id');
        $search = $request->input('search');
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();

        $query = Siswa::with('sekolah')->where('selesai_pkl', '<', Carbon::today()->toDateString());

        if ($sekolahId) {
            $query->where('sekolah_id', $sekolahId);
        }
        if ($search) {
            // Hapus pencarian id_kartu
            $query->where('nama_siswa', 'like', '%' . $search . '%');
        }

        $siswas = $query->orderBy('selesai_pkl', 'desc')->paginate(15);
        return view('admin.siswa.arsip', compact('siswas', 'sekolahs', 'sekolahId', 'search'));
    }

    public function create()
    {
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        return view('admin.siswa.create', compact('sekolahs'));
    }

    public function store(Request $request, SyncToLiveService $syncService)
    {
        $request->validate([
            'sekolah_id' => 'required|exists:sekolahs,id',
            'mulai_pkl'  => 'required|date',
            'selesai_pkl' => 'required|date|after:mulai_pkl',
            'students'    => 'required|array|min:1',
            'students.*.nama_siswa' => 'required|string|max:255',
            // id_kartu dihapus
        ]);

        foreach ($request->students as $studentData) {
            Siswa::create([
                'sekolah_id'  => $request->sekolah_id,
                'mulai_pkl'   => $request->mulai_pkl,
                'selesai_pkl' => $request->selesai_pkl,
                'nama_siswa'  => $studentData['nama_siswa'],
            ]);
        }

        // Sync to Live Monitoring
        $syncService->syncStudents();

        return redirect()->route('admin.siswa.index')->with('success', 'Berhasil menambahkan sekelompok siswa PKL.');
    }

    public function edit(Siswa $siswa)
    {
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        return view('admin.siswa.edit', compact('siswa', 'sekolahs'));
    }

    public function update(Request $request, Siswa $siswa, SyncToLiveService $syncService)
    {
        $request->validate([
            'nama_siswa'  => 'required|string|max:255',
            'sekolah_id'  => 'required|exists:sekolahs,id',
            'mulai_pkl'   => 'required|date',
            'selesai_pkl' => 'required|date|after_or_equal:mulai_pkl',
        ]);

        $siswa->update($request->all());

        // Sync to Live Monitoring
        $syncService->syncStudents();

        return redirect()->route('admin.siswa.index')
                         ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa, SyncToLiveService $syncService)
    {
        $siswa->delete();

        // Sync to Live Monitoring
        $syncService->syncStudents();

        return redirect()->route('admin.siswa.index')
                         ->with('success', 'Data siswa berhasil dihapus.');
    }

    public function riwayat(Siswa $siswa)
    {
        $presensis = $siswa->presensis()->orderBy('tanggal', 'desc')->get();
        return view('admin.siswa.riwayat', compact('siswa', 'presensis'));
    }
}
