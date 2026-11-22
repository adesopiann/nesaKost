@extends('components.layouts.guest.container')

@section('title', $property->name . ' - nesaKost')

@push('styles')
    <style>
        html {
            scroll-behavior: auto;
        }
    </style>
@endpush

@section('content')
    <section data-page="property-detail" class="bg-slate-50 py-12">
        <div class="mx-auto max-w-7xl px-6">

            {{-- BREADCRUMB --}}
            <nav data-property-breadcrumb aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('guest.home') }}" class="hover:text-blue-900 hover:underline">
                    Cari Kost
                </a>
                <span aria-hidden="true">/</span>
                <span class="font-medium text-blue-900"> {{ $property->name }} </span>
            </nav>

            {{-- HEADER PROPERTI --}}
            <div data-property-heading class="mt-5">
                <h1 data-motion-hero class="text-3xl font-semibold text-slate-900">
                    {{ $property->name }}
                </h1>

                <p class="mt-3 text-sm text-slate-500">
                    {{ $property->address }}
                </p>

                <span
                    class="mt-4 inline-block rounded-full bg-blue-50 px-3 py-2 text-xs font-medium capitalize text-blue-900">
                    Kost {{ $property->gender_type }}
                </span>
            </div>

            {{-- FOTO PROPERTI --}}
            <div data-property-gallery class="mt-8 overflow-hidden rounded-2xl bg-white">
                @if ($property->image_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($property->image_path) }}"
                        alt="{{ $property->name }}" class="max-h-[450px] w-full object-cover">
                @else
                    <div class="flex h-72 items-center justify-center text-slate-400">
                        Foto properti belum tersedia
                    </div>
                @endif
            </div>

            {{-- FASILITAS UMUM --}}
            <div data-property-section class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 md:p-8">
                <div>
                    <h2 data-motion-reveal class="text-2xl font-semibold text-slate-900">
                        Fasilitas Umum
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        Fasilitas yang tersedia untuk penghuni kost ini.
                    </p>
                </div>

                @if ($property->facilities->isNotEmpty())
                    <div data-property-facilities class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($property->facilities as $facility)
                            <div data-property-facility-card class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 p-4 transition-shadow hover:shadow-md hover:shadow-slate-200/60">

                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-900">
                                    <i data-feather="sliders" class="h-5 w-5"></i>
                                </div>

                                <div>
                                    <h3 class="text-sm font-semibold text-slate-800">
                                        {{ $facility->facility_name }}
                                    </h3>

                                    @if ($facility->description)
                                        <p class="mt-1 text-xs leading-relaxed text-slate-500">
                                            {{ $facility->description }}
                                        </p>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-xl bg-slate-50 p-6 text-center">
                        <p class="text-sm text-slate-500">
                            Informasi fasilitas umum belum tersedia.
                        </p>
                    </div>
                @endif
            </div>



            {{-- PILIHAN KAMAR --}}
            <div class="mt-10" id="daftar-kamar">
                <h2 data-motion-reveal class="text-2xl font-semibold text-slate-900">
                    Pilihan Kamar
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Lihat informasi lengkap kamar dan periksa ketersediaan pada halaman detail kamar.
                </p>

                <div data-property-rooms class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">

                    @forelse($property->rooms as $room)

                        @php
                            $currentStatus = $room->availability['status'] ?? 'unavailable';

                            $statusLabel = match ($currentStatus) {
                                'available' => 'Tersedia hari ini',
                                'occupied' => 'Sedang ditempati',
                                'reserved' => 'Sudah dipesan',
                                default => 'Tidak tersedia',
                            };

                            $statusClass = match ($currentStatus) {
                                'available' => 'bg-green-50 text-green-700',
                                'occupied' => 'bg-amber-50 text-amber-700',
                                'reserved' => 'bg-blue-50 text-blue-700',
                                default => 'bg-slate-100 text-slate-600',
                            };

                        @endphp

                        <div data-property-room-card class="overflow-hidden rounded-xl border border-slate-200 bg-white transition-shadow duration-300 hover:shadow-xl hover:shadow-slate-200/60">

                            {{-- FOTO KAMAR --}}
                            @if ($room->image_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($room->image_path) }}"
                                    alt="{{ $room->type }}" class="h-48 w-full object-cover" loading="lazy">
                            @else
                                <div class="flex h-48 items-center justify-center bg-slate-100 text-sm text-slate-400">
                                    Foto kamar belum tersedia
                                </div>
                            @endif

                            <div class="p-5">

                                <h3 class="font-semibold text-slate-800">
                                    {{ $room->type }}
                                </h3>

                                <p class="mt-3 text-lg font-semibold text-blue-900">
                                    Rp{{ number_format((float) $room->monthly_price, 0, ',', '.') }}

                                    <span class="text-xs font-normal text-slate-500">
                                        / bulan
                                    </span>
                                </p>

                                <p class="mt-3 text-sm text-slate-500">
                                    {{ $room->description }}
                                </p>

                                {{-- STATUS HARI INI --}}
                                <span
                                    class="mt-4 inline-block rounded-full px-3 py-1 text-xs font-medium {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>

                                <a href="{{ route('guest.rooms.show', $room->room_code) }}"
                                    class="mt-6 inline-flex w-full items-center justify-center rounded-lg bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800">
                                    Lihat Detail & Cek Ketersediaan
                                </a>

                            </div>
                        </div>

                    @empty

                        <p class="text-sm text-slate-500">
                            Belum ada kamar aktif pada properti ini.
                        </p>

                    @endforelse

                </div>
            </div>



            {{-- LOKASI KOST --}}
            <div data-property-map-panel class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 md:p-8">

                <div>
                    <h2 data-motion-reveal class="text-2xl font-semibold text-slate-900">
                        Lokasi Kost
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        Temukan lokasi properti dan lihat area di sekitarnya.
                    </p>

                    <p class="mt-3 text-sm text-slate-700">
                        {{ $property->address }}
                    </p>
                </div>

                @if ($property->latitude !== null && $property->longitude !== null)
                    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
                        <div id="property-map" class="z-0 h-[350px] w-full md:h-[450px]"
                            data-lat="{{ $property->latitude }}" data-lng="{{ $property->longitude }}"
                            data-name="{{ $property->name }}"></div>
                    </div>

                    <a href="https://www.google.com/maps/search/?api=1&query={{ $property->latitude }},{{ $property->longitude }}"
                        target="_blank" rel="noopener noreferrer"
                        class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-900 hover:underline">
                        Buka lokasi di Google Maps
                        <span>↗</span>
                    </a>
                @else
                    <div class="mt-6 rounded-xl bg-slate-50 p-8 text-center">
                        <p class="text-sm text-slate-500">
                            Lokasi peta belum tersedia untuk properti ini.
                        </p>
                    </div>
                @endif
            </div>

        </div>
    </section>

    {{-- LEAFLET MAP --}}
    @if ($property->latitude !== null && $property->longitude !== null)
        @once
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">

            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        @endonce

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const mapElement = document.getElementById('property-map');

                if (!mapElement || typeof L === 'undefined') {
                    return;
                }

                const lat = Number(mapElement.dataset.lat);
                const lng = Number(mapElement.dataset.lng);
                const propertyName = mapElement.dataset.name;

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return;
                }

                const map = L.map('property-map').setView(
                    [lat, lng],
                    17
                );

                L.tileLayer(
                    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }
                ).addTo(map);

                L.marker([lat, lng])
                    .addTo(map)
                    .bindPopup(
                        `<strong>${escapeHtml(propertyName)}</strong><br>Lokasi kost`
                    )
                    .openPopup();

                function escapeHtml(value) {
                    const element = document.createElement('div');
                    element.textContent = value;
                    return element.innerHTML;
                }

            });
        </script>
    @endif

@endsection
