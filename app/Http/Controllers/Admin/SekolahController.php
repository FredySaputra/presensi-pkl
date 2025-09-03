<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use Illuminate\Http\Request;

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
                'nama_sekolah' => 'required|string|max:255|unique:sekolahs,nama_sekolah',
                'hari_libur' => 'nullable|integer|between:1,6', // Validasi baru
            ]);

            Sekolah::create($request->all());

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
                'nama_sekolah' => 'required|string|max:255|unique:sekolahs,nama_sekolah,' . $sekolah->id,
                'hari_libur' => 'nullable|integer|between:1,6', // Validasi baru
            ]);

            $sekolah->update($request->all());

            return redirect()->route('admin.sekolah.index')
                             ->with('success', 'Data sekolah berhasil diperbarui.');
        }

        public function destroy(Sekolah $sekolah)
        {
            $sekolah->delete();
            return redirect()->route('admin.sekolah.index')
                             ->with('success', 'Data sekolah berhasil dihapus.');
        }
    }
