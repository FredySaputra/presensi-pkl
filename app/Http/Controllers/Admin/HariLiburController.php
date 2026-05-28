<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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

    public function store(Request $request)
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

        return redirect()->route('admin.harilibur.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroy(HariLibur $harilibur)
    {
        $harilibur->delete();
        return response()->json(['success' => true]);
    }
}
