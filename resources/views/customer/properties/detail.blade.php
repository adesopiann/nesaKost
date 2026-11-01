
@extends('components.layouts.customer')

@section('title', $property->name . ' - nesaKost')

@section('content')
<section class="bg-slate-50 py-12">
    <div class="mx-auto max-w-7xl px-6">

        {{-- HEADER PROPERTI --}}
        <a href="{{ route('customer.home') }}"
           class="text-sm text-blue-900 hover:underline">
            ← Kembali ke pencarian
        </a>

        <div class="mt-8">
            <h1 class="text-3xl font-semibold text-slate-900">
                {{ $property->name }}
            </h1>

            <p class="mt-3 text-sm text-slate-500">
                {{ $property->address }}
            </p>

            <span class="mt-4 inline-block rounded-full bg-blue-50 px-3 py-2 text-xs font-medium capitalize text-blue-900">
                Kost {{ $property->gender_type }}
            </span>
        </div>

        {{-- FOTO PROPERTI --}}
        <div class="mt-8 overflow-hidden rounded-2xl bg-white">
            @if($property->image_path)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($property->image_path) }}"
                    alt="{{ $property->name }}"
                    class="max-h-[450px] w-full object-cover"
                >
            @else
                <div class="flex h-72 items-center justify-center text-slate-400">
                    Foto properti belum tersedia
                </div>
            @endif
        </div>

        {{-- FASILITAS UMUM --}}
        <div class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 md:p-8">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">
                    Fasilitas Umum
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Fasilitas yang tersedia untuk penghuni kost ini.
                </p>
            </div>

            @if($property->facilities->isNotEmpty())
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($property->facilities as $facility)
                        <div class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 p-4">

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-900">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     width="20"
                                     height="20"
                                     viewBox="0 0 24 24"
                                     fill="none"
                                     stroke="currentColor"
                                     stroke-width="1.8"
                                     stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path d="M20 7h-9"/>
                                    <path d="M14 17H5"/>
                                    <circle cx="17" cy="17" r="3"/>
                                    <circle cx="7" cy="7" r="3"/>
                                </svg>
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-slate-800">
                                    {{ $facility->facility_name }}
                                </h3>

                                @if($facility->description)
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
        <div class="mt-10">
            <h2 class="text-2xl font-semibold text-slate-900">
                Pilihan kamar tersedia
            </h2>

            <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">

                @forelse($property->rooms as $room)

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">

                        @if($room->image_path)
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($room->image_path) }}"
                                alt="{{ $room->type }}"
                                class="h-48 w-full object-cover"
                                loading="lazy"
                            >
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

                            <span class="mt-4 inline-block rounded-full bg-green-50 px-3 py-1 text-xs text-green-700">
                                Tersedia
                            </span>
                        </div>

                    </div>

                @empty

                    <p class="text-sm text-slate-500">
                        Belum ada kamar yang tersedia.
                    </p>

                @endforelse

            </div>
        </div>

          {{-- LOKASI KOST --}}
        <div class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 md:p-8">

            <div>
                <h2 class="text-2xl font-semibold text-slate-900">
                    Lokasi Kost
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Temukan lokasi properti dan lihat area di sekitarnya.
                </p>

                <p class="mt-3 text-sm text-slate-700">
                    {{ $property->address }}
                </p>
            </div>

            @if($property->latitude !== null && $property->longitude !== null)

                <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
                    <div
                        id="property-map"
                        class="z-0 h-[350px] w-full md:h-[450px]"
                        data-lat="{{ $property->latitude }}"
                        data-lng="{{ $property->longitude }}"
                        data-name="{{ $property->name }}"
                    ></div>
                </div>

                <a
                    href="https://www.google.com/maps/search/?api=1&query={{ $property->latitude }},{{ $property->longitude }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-900 hover:underline"
                >
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
@if($property->latitude !== null && $property->longitude !== null)

    @once
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            crossorigin=""
        >

        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            crossorigin=""
        ></script>
    @endonce

    <script>
        document.addEventListener('DOMContentLoaded', function () {

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
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
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
