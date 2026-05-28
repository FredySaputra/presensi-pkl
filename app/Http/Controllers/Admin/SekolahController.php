<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SekolahController extends Controller
{
    public function index()
    {
        $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        return view('admin.sekolah.index', compact('sekolahs'));
    }

    public function create()
    {
        return view('admin.sekolah.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_sekolah' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sekolahs')->whereNull('deleted_at')
            ],
            'hari_libur'   => 'nullable|integer|between:1,6',
            'alamat'       => 'nullable|string|max:1000',
            'latitude'     => 'nullable|string|max:255',
            'longitude'    => 'nullable|string|max:255',
        ]);

        $sekolah = Sekolah::create($request->all());

        $this->generateJadwalLibur($sekolah);

        return redirect()->route('admin.sekolah.index')
                         ->with('success', 'Data sekolah berhasil ditambahkan.');
    }

    public function edit(Sekolah $sekolah)
    {
        return view('admin.sekolah.edit', compact('sekolah'));
    }

    public function update(Request $request, Sekolah $sekolah)
    {
        $request->validate([
            'nama_sekolah' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sekolahs')->ignore($sekolah->id)->whereNull('deleted_at')
            ],
            'hari_libur'   => 'nullable|integer|between:1,6',
            'alamat'       => 'nullable|string|max:1000',
            'latitude'     => 'nullable|string|max:255',
            'longitude'    => 'nullable|string|max:255',
        ]);

        $sekolah->update($request->all());

        $this->generateJadwalLibur($sekolah);

        return redirect()->route('admin.sekolah.index')
                         ->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(Sekolah $sekolah)
    {
        $sekolah->delete();
        return redirect()->route('admin.sekolah.index')
                         ->with('success', 'Data sekolah berhasil dihapus.');
    }

    /**
     * Generate hari libur fisik ke database untuk 6 bulan ke depan
     */
    private function generateJadwalLibur($sekolah)
    {
        // 1. Paksa data menjadi angka bulat (Integer) agar Carbon tidak error
        $hariLiburInt = (int) $sekolah->hari_libur;

        // 2. Bersihkan sisa jadwal lama di masa depan
        HariLibur::where('sekolah_id', $sekolah->id)
                 ->where('tanggal', '>=', Carbon::today()->toDateString())
                 ->delete();

        // 3. Eksekusi HANYA jika harinya valid (1=Senin sampai 6=Sabtu)
        if ($hariLiburInt >= 1 && $hariLiburInt <= 6) {
            $startDate = Carbon::today();
            $endDate = Carbon::today()->addMonths(6);

            // Gunakan copy() untuk menduplikasi waktu agar aman
            $nextHoliday = $startDate->copy()->next($hariLiburInt);
            if ($startDate->dayOfWeekIso === $hariLiburInt) {
                $nextHoliday = $startDate->copy();
            }

            $holidays = [];

            // Lakukan perulangan tambah 1 minggu ke depan
            for ($date = $nextHoliday->copy(); $date->lte($endDate); $date->addWeek()) {
                $holidays[] = [
                    'tanggal'    => $date->format('Y-m-d'),
                    'keterangan' => 'Libur Mingguan: ' . $sekolah->nama_sekolah,
                    'sekolah_id' => $sekolah->id,
                    // 4. Paksa waktu menjadi String agar diterima oleh PDO bulk insert
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
            }

            // Insert data ke database
            if (!empty($holidays)) {
                HariLibur::insert($holidays);
            }
        }
    }
}
