<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use Illuminate\Http\Request;

class SekolahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $sekolahs = Sekolah::orderBy('nama_sekolah', 'asc')->get();
        return view('admin.sekolah.index', compact('sekolahs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.sekolah.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
        'nama_sekolah' => 'required|string|max:255|unique:sekolahs,nama_sekolah',
    ]);

    Sekolah::create([
        'nama_sekolah' => $request->nama_sekolah,
    ]);

    return redirect()->route('admin.sekolah.index')
                     ->with('success', 'Data sekolah berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sekolah $sekolah)
{
    return view('admin.sekolah.edit', compact('sekolah'));
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sekolah $sekolah)
{
    $request->validate([
        'nama_sekolah' => 'required|string|max:255|unique:sekolahs,nama_sekolah,' . $sekolah->id,
    ]);

    $sekolah->update([
        'nama_sekolah' => $request->nama_sekolah,
    ]);

    return redirect()->route('admin.sekolah.index')
                     ->with('success', 'Data sekolah berhasil diperbarui.');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sekolah $sekolah)
{
    $sekolah->delete();

    return redirect()->route('admin.sekolah.index')
                     ->with('success', 'Data sekolah berhasil dihapus.');
}
}
