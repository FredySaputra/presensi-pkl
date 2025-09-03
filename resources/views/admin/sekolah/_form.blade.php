    @csrf
    <div class="form-group">
        <label for="nama_sekolah">Nama Sekolah</label>
        <input type="text" name="nama_sekolah" class="form-control @error('nama_sekolah') is-invalid @enderror" id="nama_sekolah" value="{{ old('nama_sekolah', $sekolah->nama_sekolah ?? '') }}" required>
        @error('nama_sekolah')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="hari_libur">Hari Libur Spesifik (Selain Minggu)</label>
        <select name="hari_libur" id="hari_libur" class="form-control @error('hari_libur') is-invalid @enderror">
            <option value="">-- Tidak Ada --</option>
            @php
                $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
            @endphp
            @foreach($days as $key => $day)
                <option value="{{ $key }}" {{ old('hari_libur', $sekolah->hari_libur ?? '') == $key ? 'selected' : '' }}>
                    {{ $day }}
                </option>
            @endforeach
        </select>
        <small class="form-text text-muted">Pilih hari libur tambahan selain hari Minggu untuk sekolah ini.</small>
        @error('hari_libur')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary">{{ $submitButtonText ?? 'Simpan' }}</button>
    <a href="{{ route('admin.sekolah.index') }}" class="btn btn-secondary">Batal</a>
    