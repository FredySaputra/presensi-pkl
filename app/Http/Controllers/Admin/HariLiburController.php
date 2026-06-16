<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Services\SyncToLiveService;

class HariLiburController extends Controller
{
    public function index()
    {
        return view('admin.harilibur.index');
    }

    public function getEvents(Request $request)
    {
        $start = $request->query('start')
                 ? Carbon::parse($request->query('start'))->toDateString()
                 : Carbon::now()->startOfMonth()->toDateString();

        $end = $request->query('end')
               ? Carbon::parse($request->query('end'))->toDateString()
               : Carbon::now()->endOfMonth()->toDateString();

        $liburs = HariLibur::whereBetween('tanggal', [$start, $end])->get();

        $events = [];

        foreach ($liburs as $libur) {
            $events[] = [
                'id' => $libur->sekolah_id ? 'sekolah_' . $libur->id : 'global_' . $libur->id,
                'title' => $libur->keterangan,
                'start' => $libur->tanggal,
                'color' => $libur->sekolah_id ? '#fd7e14' : '#dc3545',
                'textColor' => '#fff',
                'allDay' => true
            ];
        }

        return response()->json($events);
    }

    public function store(Request $request, SyncToLiveService $syncService)
    {
        $request->validate([
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan'      => 'required|string|max:255'
        ]);

        $period = CarbonPeriod::create($request->tanggal_mulai, $request->tanggal_selesai);

        foreach ($period as $date) {
            HariLibur::updateOrCreate(
                ['tanggal' => $date->format('Y-m-d')],
                ['keterangan' => $request->keterangan]
            );
        }

        // Sync to Live Monitoring
        $syncService->syncHolidays();

        return redirect()->route('admin.harilibur.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroy(HariLibur $harilibur, SyncToLiveService $syncService)
    {
        $harilibur->delete();

        // Sync to Live Monitoring (Send full list)
        $syncService->syncHolidays();

        return response()->json(['success' => true]);
    }

    public function fetchAuto()
    {
        $url = "https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/calendar.json";
        $response = @file_get_contents($url);
        
        if (!$response) {
            return redirect()->back()->with('error', 'Gagal menghubungi server penyedia Hari Libur.');
        }

        $data = json_decode($response, true);
        if (!$data) {
            return redirect()->back()->with('error', 'Format data Hari Libur tidak valid.');
        }

        $currentYear = date('Y');
        $added = 0;

        foreach ($data as $date => $info) {
            // Hanya ambil tahun ini dan tahun depan
            if (strpos($date, $currentYear . '-') === 0 || strpos($date, ($currentYear + 1) . '-') === 0) {
                
                $descriptions = implode(" ", $info['description'] ?? []);
                $summaries = implode(" ", $info['summary'] ?? []);

                // Hanya ambil jika "Hari libur nasional" dan BUKAN "Cuti Bersama"
                if (stripos($descriptions, 'Hari libur nasional') !== false && stripos($summaries, 'Cuti') === false) {
                    
                    $keterangan = $info['summary'][0] ?? 'Hari Libur Nasional';

                    $holiday = HariLibur::firstOrCreate(
                        ['tanggal' => $date],
                        ['keterangan' => $keterangan]
                    );

                    if ($holiday->wasRecentlyCreated) {
                        $added++;
                    }
                }
            }
        }

        if ($added > 0) {
            return redirect()->route('admin.harilibur.index')->with('success', "$added Hari libur nasional berhasil ditarik dan ditambahkan otomatis.");
        } else {
            return redirect()->route('admin.harilibur.index')->with('success', "Tidak ada hari libur baru yang perlu ditambahkan (semuanya sudah up-to-date).");
        }
    }
}
