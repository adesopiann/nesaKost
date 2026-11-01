@php
    $latitudeValue = old('latitude', $branch->latitude ?? '');
    $longitudeValue = old('longitude', $branch->longitude ?? '');
    $genderValue = old('gender_type', $branch->gender_type ?? '');
@endphp

<div class="space-y-6">

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <label for="branch_code" class="mb-2 block text-sm font-semibold">Kode Cabang</label>
        <input id="branch_code" value="{{ isset($branch) ? $branch->branch_code : 'Dibuat otomatis setelah disimpan' }}" disabled class="w-full rounded-lg border-0 bg-indigo-50 px-4 py-3 text-sm text-slate-600">
        <p class="mt-2 text-xs text-slate-500">Kode tampilan dibentuk otomatis berdasarkan ID cabang.</p>
    </div>
    <div>
        <label for="status" class="mb-2 block text-sm font-semibold">Status Cabang <span class="text-red-500">*</span></label>
        <select id="status" name="status" required class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
            <option value="active" @selected(old('status', $branch->status ?? 'active') === 'active')>Aktif</option>
            <option value="inactive" @selected(old('status', $branch->status ?? 'active') === 'inactive')>Nonaktif</option>
        </select>
        @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="name" class="mb-2 block text-sm font-semibold">Nama Cabang Kos <span class="text-red-500">*</span></label>
    <input id="name" name="name" value="{{ old('name', $branch->name ?? '') }}" required maxlength="150" placeholder="Contoh: Melati Residence" class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="mb-2 block text-sm font-semibold">Pemilik Properti</label>
    <input value="{{ trim(($owner->user->fname ?? $branch->owner->user->fname ?? '') . ' ' . ($owner->user->lname ?? $branch->owner->user->lname ?? '')) }}" disabled class="w-full rounded-lg border-0 bg-indigo-50 px-4 py-3 text-sm text-slate-700">
    <p class="mt-2 text-xs text-slate-500">Cabang akan terhubung dengan akun pemilik yang sedang login.</p>
</div>

<div>
        <label for="gender_type" class="mb-2 block text-sm font-semibold">Kategori Kost <span class="text-red-500">*</span></label>
        <select id="gender_type" name="gender_type" required class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">
            <option value="">Pilih kategori kost</option>
            <option value="putra" @selected($genderValue === 'putra')>Kost Putra</option>
            <option value="putri" @selected($genderValue === 'putri')>Kost Putri</option>
            <option value="campuran" @selected($genderValue === 'campuran')>Kost Campuran</option>
        </select>
        <p class="mt-2 text-xs text-slate-500">Kategori berlaku untuk keseluruhan cabang kost.</p>
        @error('gender_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="mb-2 block text-sm font-semibold">Fasilitas Umum</label>
    <p class="mb-3 text-xs text-slate-500">Pilih fasilitas yang tersedia dan dapat digunakan penghuni cabang ini.</p>
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($facilities as $facility)
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 hover:bg-indigo-50">
                <input type="checkbox" name="facilities[]" value="{{ $facility->id }}" @checked(in_array($facility->id, old('facilities', $selectedFacilities ?? []))) class="mt-1 rounded border-slate-300 text-blue-800 focus:ring-blue-700">
                <span><span class="block text-sm font-medium">{{ $facility->facility_name }}</span>@if($facility->description)<span class="mt-1 block text-xs text-slate-500">{{ $facility->description }}</span>@endif</span>
            </label>
        @empty
            <p class="text-sm text-slate-500">Master fasilitas belum tersedia. Tambahkan fasilitas terlebih dahulu.</p>
        @endforelse
    </div>
    @error('facilities')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    @error('facilities.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label for="address" class="mb-2 block text-sm font-semibold">Alamat Lengkap Cabang <span class="text-red-500">*</span></label>
    <textarea id="address" name="address" rows="4" required maxlength="5000" placeholder="Nama jalan, RT/RW, kelurahan, kecamatan, kabupaten/kota, provinsi, dan kode pos" class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none">{{ old('address', $branch->address ?? '') }}</textarea>
    <p class="mt-2 text-xs text-slate-500">Masukkan alamat lengkap agar lokasi properti mudah diverifikasi.</p>
    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>

<section id="branch-location-picker" class="space-y-4 rounded-xl border border-slate-200 p-4 md:p-5"
    data-geocode-url="{{ route('owner.branches.geocode') }}"
    data-initial-lat="{{ $latitudeValue }}" data-initial-lng="{{ $longitudeValue }}">
    <div>
        <h3 class="text-base font-bold text-slate-900">Tentukan titik lokasi kost <span class="text-red-500">*</span></h3>
        <p class="mt-1 text-sm text-slate-500">Cari alamat, pilih hasil yang sesuai, lalu geser pin ke lokasi bangunan kost yang tepat.</p>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row">
        <button type="button" id="geocode-address" class="rounded-lg bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">Cari Lokasi dari Alamat</button>
        <button type="button" id="reset-location" class="rounded-lg bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200">Hapus Titik</button>
    </div>

    <p id="geocode-status" class="text-sm text-slate-500" aria-live="polite">
        {{ $latitudeValue !== '' && $longitudeValue !== '' ? 'Koordinat tersimpan. Periksa posisi marker.' : 'Belum ada titik lokasi. Cari alamat atau klik langsung pada peta.' }}
    </p>
    <div id="geocode-results" class="hidden space-y-2"></div>
    <div id="branch-map" class="z-0 h-[360px] w-full rounded-xl border border-slate-200"></div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="latitude" class="mb-2 block text-sm font-semibold">Latitude</label>
            <input id="latitude" name="latitude" value="{{ $latitudeValue }}" readonly required placeholder="Otomatis dari titik peta" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            @error('latitude')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="longitude" class="mb-2 block text-sm font-semibold">Longitude</label>
            <input id="longitude" name="longitude" value="{{ $longitudeValue }}" readonly required placeholder="Otomatis dari titik peta" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            @error('longitude')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>
    <p class="text-xs text-slate-500">Peta © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer" class="underline">OpenStreetMap contributors</a>.</p>
</section>

</div>

@once
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@endonce

<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('branch-location-picker');
    if (!wrapper || wrapper.dataset.initialized === '1') return;
    wrapper.dataset.initialized = '1';

    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const addressInput = document.getElementById('address');
    const status = document.getElementById('geocode-status');
    const resultsBox = document.getElementById('geocode-results');
    const searchButton = document.getElementById('geocode-address');
    const resetButton = document.getElementById('reset-location');

    if (typeof L === 'undefined') {
        status.textContent = 'Library peta gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.';
        return;
    }

    const initialLat = Number(wrapper.dataset.initialLat);
    const initialLng = Number(wrapper.dataset.initialLng);
    const hasPoint = wrapper.dataset.initialLat !== '' && wrapper.dataset.initialLng !== ''
        && Number.isFinite(initialLat) && Number.isFinite(initialLng);

    const map = L.map('branch-map').setView(hasPoint ? [initialLat, initialLng] : [-7.2575, 112.7521], hasPoint ? 17 : 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
    }).addTo(map);

    let marker = null;

    function setPoint(lat, lng) {
        lat = Number(lat);
        lng = Number(lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            status.textContent = 'Koordinat tidak valid.';
            return;
        }

        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);

        if (!marker) {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function (event) {
                const point = event.target.getLatLng();
                setPoint(point.lat, point.lng);
            });
        } else {
            marker.setLatLng([lat, lng]);
        }

        map.setView([lat, lng], 17);
        status.textContent = 'Titik lokasi ditentukan. Geser marker jika perlu.';
    }

    if (hasPoint) setPoint(initialLat, initialLng);
    map.on('click', event => setPoint(event.latlng.lat, event.latlng.lng));

    searchButton.addEventListener('click', async function () {
        const query = addressInput.value.trim();
        if (query.length < 5) {
            status.textContent = 'Masukkan alamat yang lebih lengkap.';
            addressInput.focus();
            return;
        }

        searchButton.disabled = true;
        status.textContent = 'Sedang mencari alamat...';
        resultsBox.innerHTML = '';
        resultsBox.classList.add('hidden');

        try {
            const url = new URL(wrapper.dataset.geocodeUrl, window.location.origin);
            url.searchParams.set('q', query);
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) {
                let errorData = {};

                try {
                    errorData = await response.json();
                } catch (e) {
                    // Response bukan JSON
                }

                throw new Error(
                    errorData.message ||
                    (response.status === 429
                        ? 'Permintaan terlalu sering. Tunggu sebentar.'
                        : 'Pencarian alamat gagal. Coba kembali.')
                );
            }

            const payload = await response.json();
            const results = payload.results || [];

            if (payload.fallback_used && results.length > 0) {
                status.textContent =
                    'Alamat lengkap tidak ditemukan. Sistem berhasil menemukan lokasi menggunakan pencarian alamat yang disederhanakan. Silakan pilih hasil yang sesuai.';
            }

            if (!results.length) {
                status.textContent = payload.message ||
                    'Alamat tidak ditemukan. Silakan tentukan lokasi secara manual pada peta.';
                return;
            }

            resultsBox.classList.remove('hidden');
            results.forEach(result => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full rounded-lg border border-slate-200 p-3 text-left text-sm text-slate-700 hover:border-blue-500 hover:bg-blue-50';
                button.textContent = result.display_name;
                button.addEventListener('click', function () {
                    setPoint(result.latitude, result.longitude);
                    resultsBox.classList.add('hidden');
                    resultsBox.innerHTML = '';
                });
                resultsBox.appendChild(button);
            });
            if (!payload.fallback_used) {
                status.textContent =
                    'Alamat ditemukan. Pilih hasil yang paling sesuai, lalu periksa posisi marker.';
            }
        } catch (error) {
            status.textContent = error.message || 'Terjadi kesalahan saat mencari alamat.';
        } finally {
            searchButton.disabled = false;
        }
    });

    resetButton.addEventListener('click', function () {
        latInput.value = '';
        lngInput.value = '';
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
        resultsBox.innerHTML = '';
        resultsBox.classList.add('hidden');
        status.textContent = 'Titik lokasi dihapus. Cari alamat atau klik peta.';
    });

    const form = wrapper.closest('form');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (!latInput.value || !lngInput.value) {
                event.preventDefault();
                status.textContent = 'Tentukan titik lokasi kost pada peta sebelum menyimpan.';
                wrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    setTimeout(() => map.invalidateSize(), 150);
});
</script>

