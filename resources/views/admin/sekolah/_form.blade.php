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

<div class="form-group">
    <label for="alamat">Alamat Lengkap</label>
    <textarea name="alamat" id="alamat" rows="3" class="form-control @error('alamat') is-invalid @enderror" placeholder="Tuliskan alamat lengkap sekolah...">{{ old('alamat', $sekolah->alamat ?? '') }}</textarea>
    @error('alamat')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label>Titik Lokasi Maps</label>
    <div id="map" style="height: 350px; width: 100%; border-radius: 5px; margin-bottom: 15px; border: 1px solid #ced4da; z-index: 1;"></div>

    <div class="row">
        <div class="col-md-6">
            <input type="text" name="latitude" id="latitude" class="form-control bg-light" placeholder="Latitude" value="{{ old('latitude', $sekolah->latitude ?? '') }}" readonly>
        </div>
        <div class="col-md-6">
            <input type="text" name="longitude" id="longitude" class="form-control bg-light" placeholder="Longitude" value="{{ old('longitude', $sekolah->longitude ?? '') }}" readonly>
        </div>
    </div>
    <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle"></i> Klik pada peta untuk menandai atau mengubah lokasi sekolah.</small>
</div>

<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ $submitButtonText ?? 'Simpan' }}</button>
<a href="{{ route('admin.sekolah.index') }}" class="btn btn-secondary">Batal</a>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var latInput = document.getElementById('latitude');
        var lngInput = document.getElementById('longitude');
        var alamatInput = document.getElementById('alamat');

        var defaultLat = latInput.value ? parseFloat(latInput.value) : -6.2349;
        var defaultLng = lngInput.value ? parseFloat(lngInput.value) : 106.7570;
        var zoomLevel = latInput.value ? 16 : 13;

        var map = L.map('map').setView([defaultLat, defaultLng], zoomLevel);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var marker;

        if (latInput.value && lngInput.value) {
            marker = L.marker([defaultLat, defaultLng]).addTo(map);
            if (alamatInput.value) {
                marker.bindPopup(`<b>Lokasi Saat Ini:</b><br>${alamatInput.value}`);
            }
        }

        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            if(marker) { map.removeLayer(marker); }
            marker = L.marker([lat, lng]).addTo(map);

            latInput.value = lat;
            lngInput.value = lng;

            alamatInput.value = "Mencari alamat detail...";
            marker.bindPopup('<i class="fas fa-spinner fa-spin"></i> Sedang mengambil alamat...').openPopup();

            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if(data && data.display_name) {
                        alamatInput.value = data.display_name;

                        marker.bindPopup(`<b>Detail Lokasi:</b><br>${data.display_name}`).openPopup();
                    } else {
                        alamatInput.value = "Alamat spesifik tidak ditemukan di koordinat ini.";
                        marker.bindPopup("Alamat tidak ditemukan.").openPopup();
                    }
                })
                .catch(error => {
                    console.error("Gagal mengambil alamat:", error);
                    alamatInput.value = "";
                    marker.bindPopup("Gagal terkoneksi ke server peta.").openPopup();
                });
        });

                // FITUR PENCARIAN (GEOCODER) - Diperbarui
        var geocoder = L.Control.geocoder({
            defaultMarkGeocode: false,
            placeholder: "Cari nama sekolah atau jalan...",

            geocoder: L.Control.Geocoder.nominatim({
                geocodingQueryParams: {
                    countrycodes: 'id',
                    limit: 5
                }
            })

        }).on('markgeocode', function(e) {
            var bbox = e.geocode.bbox;
            var latlng = e.geocode.center;

            map.fitBounds(bbox);

            if(marker) { map.removeLayer(marker); }
            marker = L.marker(latlng).addTo(map);

            latInput.value = latlng.lat;
            lngInput.value = latlng.lng;

            alamatInput.value = e.geocode.name;
            marker.bindPopup(`<b>Hasil Pencarian:</b><br>${e.geocode.name}`).openPopup();

        }).addTo(map);
    });
</script>
