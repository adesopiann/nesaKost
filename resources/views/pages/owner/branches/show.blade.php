@extends('components.layouts.owner.container')
@section('main')

<div class="space-y-6 text-slate-800">
    @include('pages.owner.branches.partials.flash')

    <section class="rounded-xl bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500"><a href="{{ route('owner.branches.index') }}" class="hover:text-blue-800">Data Cabang Kos</a> / <span class="font-semibold text-blue-800">Detail Cabang Kos</span></p>
        <div class="mt-3 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-3xl font-bold">{{ $branch->name }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <span class="font-semibold text-blue-800">{{ $branch->branch_code }}</span>
                    <span>•</span>
                    <span>Dibuat {{ $branch->created_at?->format('d M Y') ?? '—' }}</span>
                    <span>•</span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $branch->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $branch->status === 'active' ? 'Aktif Beroperasi' : 'Nonaktif' }}</span>
                </div>
            </div>
            <a href="{{ route('owner.branches.edit', $branch) }}" class="rounded-lg bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">Edit Cabang</a>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Kapasitas Kamar</p><p class="mt-2 text-2xl font-bold">{{ $totalRooms }} <span class="text-sm font-medium text-slate-500">kamar</span></p></div>
        <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Kamar Terisi</p><p class="mt-2 text-2xl font-bold">{{ $occupiedRooms }}</p></div>
        <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Kamar Tersedia</p><p class="mt-2 text-2xl font-bold">{{ $availableRooms }}</p></div>
        <div class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Tingkat Okupansi</p><p class="mt-2 text-2xl font-bold">{{ $totalRooms ? round($occupiedRooms / $totalRooms * 100) : 0 }}%</p></div>
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-bold">Informasi Cabang</h2>
            <div>
                <p class="text-sm font-semibold text-slate-500">Alamat lengkap</p>
                <p class="mt-2 leading-relaxed">{{ $branch->address }}</p>
            </div>
            <div>
                    <p class="text-sm font-semibold text-slate-500">Kategori kost</p>
                    <p class="mt-2 font-medium capitalize">{{ $branch->gender_type ?: 'Belum ditentukan' }}</p>
            </div>
             <div class="space-y-3">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Titik lokasi cabang</p>
                    @if ($branch->latitude !== null && $branch->longitude !== null)
                        <p class="mt-1 text-sm text-slate-600">{{ $branch->latitude }}, {{ $branch->longitude }}</p>
                    @endif
                </div>
                @if ($branch->latitude !== null && $branch->longitude !== null)
                    <div id="branch-detail-map"
                        class="z-0 h-[320px] w-full rounded-xl border border-slate-200"
                        data-lat="{{ $branch->latitude }}"
                        data-lng="{{ $branch->longitude }}"
                        aria-label="Peta lokasi cabang {{ $branch->name }}"></div>
                    <a class="inline-flex text-sm font-semibold text-blue-800 hover:underline"
                        href="https://www.openstreetmap.org/?mlat={{ $branch->latitude }}&mlon={{ $branch->longitude }}#map=18/{{ $branch->latitude }}/{{ $branch->longitude }}"
                        target="_blank" rel="noopener noreferrer">Buka di OpenStreetMap ↗</a>
                @else
                    <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-700">Lokasi belum ditentukan. Lengkapi melalui menu Edit Cabang.</p>
                @endif
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-500">Pemilik properti</p>
                <p class="mt-2 font-medium">{{ $branch->owner?->user ? trim($branch->owner->user->fname . ' ' . $branch->owner->user->lname) : 'Pemilik tidak ditemukan' }}</p>
                @if($branch->owner?->user)
                    <p class="mt-1 text-sm text-slate-500">{{ $branch->owner->user->email }}</p>
                @endif
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-500">Fasilitas umum</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse($branch->facilities as $facility)
                        <span class="rounded-lg bg-indigo-50 px-3 py-2 text-sm">{{ $facility->facility_name }}</span>
                    @empty
                        <p class="text-sm text-slate-400">Belum ada fasilitas yang terhubung ke cabang ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <aside class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold">Ringkasan</h2>
            <dl class="mt-4 space-y-4 text-sm">
                <div><dt class="text-slate-500">Kode cabang</dt><dd class="mt-1 font-semibold">{{ $branch->branch_code }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="mt-1 font-semibold">{{ $branch->status === 'active' ? 'Aktif' : 'Nonaktif' }}</dd></div>
                <div><dt class="text-slate-500">Terakhir diperbarui</dt><dd class="mt-1 font-semibold">{{ $branch->updated_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
            </dl>
            <a href="{{ route('owner.branches.index') }}" class="mt-6 inline-flex w-full justify-center rounded-lg bg-slate-100 px-4 py-3 text-sm font-semibold hover:bg-slate-200">Kembali ke daftar</a>
        </aside>
    </section>
</div>

@if ($branch->latitude !== null && $branch->longitude !== null)
    @once
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @endonce
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mapElement = document.getElementById('branch-detail-map');
            if (!mapElement || mapElement.dataset.initialized === '1') return;
            mapElement.dataset.initialized = '1';

            const latitude = Number(mapElement.dataset.lat);
            const longitude = Number(mapElement.dataset.lng);
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                mapElement.textContent = 'Koordinat lokasi tidak valid.';
                return;
            }

            if (typeof L === 'undefined') {
                mapElement.textContent = 'Peta gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.';
                return;
            }

            const map = L.map(mapElement, {
                scrollWheelZoom: false,
                dragging: true,
                doubleClickZoom: true,
                touchZoom: true,
                keyboard: true,
            }).setView([latitude, longitude], 17);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>'
            }).addTo(map);

            L.marker([latitude, longitude]).addTo(map)
                .bindPopup(@json($branch->name))
                .openPopup();
        });
    </script>
@endif

@endsection
