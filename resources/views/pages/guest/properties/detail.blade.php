@extends('components.layouts.guest.container')

@section('title', $property->name . ' - nesaKost')

@section('content')
    <section class="bg-slate-50 py-12">
        <div class="mx-auto max-w-7xl px-6">

            {{-- HEADER PROPERTI --}}
            <a href="{{ route('guest.home') }}" class="text-sm text-blue-900 hover:underline">
                ← Kembali ke pencarian
            </a>

            <div class="mt-8">
                <h1 class="text-3xl font-semibold text-slate-900">
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
            <div class="mt-8 overflow-hidden rounded-2xl bg-white">
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
            <div class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 md:p-8">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">
                        Fasilitas Umum
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        Fasilitas yang tersedia untuk penghuni kost ini.
                    </p>
                </div>

                @if ($property->facilities->isNotEmpty())
                    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($property->facilities as $facility)
                            <div class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 p-4">

                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 7h-9" />
                                        <path d="M14 17H5" />
                                        <circle cx="17" cy="17" r="3" />
                                        <circle cx="7" cy="7" r="3" />
                                    </svg>
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
                <h2 class="text-2xl font-semibold text-slate-900">
                    Pilihan Kamar
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Pilih kamar dan periode sewa untuk memeriksa ketersediaannya.
                </p>

                <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">

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

                            $isSelectedRoom = (int) $selectedRoomId === (int) $room->id;
                        @endphp

                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">

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

                                {{-- FORM PEMILIHAN PERIODE --}}
                                <form action="{{ route('guest.properties.show', $property->id) }}#daftar-kamar"
                                    method="GET" class="mt-6 border-t border-slate-100 pt-5">
                                    <input type="hidden" name="room_id" value="{{ $room->id }}">

                                    <div>
                                        <label for="start_date_{{ $room->id }}"
                                            class="text-sm font-medium text-slate-700">
                                            Tanggal mulai sewa
                                        </label>

                                        <input type="date" id="start_date_{{ $room->id }}" name="start_date"
                                            value="{{ $isSelectedRoom ? $selectedStartDate : '' }}"
                                            min="{{ now()->toDateString() }}" required
                                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-1 focus:ring-blue-900">
                                    </div>

                                    <div class="mt-4">
                                        <label for="duration_month_{{ $room->id }}"
                                            class="text-sm font-medium text-slate-700">
                                            Durasi sewa
                                        </label>

                                        <select id="duration_month_{{ $room->id }}" name="duration_month" required
                                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-1 focus:ring-blue-900">
                                            <option value="">Pilih durasi</option>

                                            <option value="1" @selected($isSelectedRoom && $selectedDurationMonth === 1)>
                                                1 Bulan
                                            </option>

                                            <option value="3" @selected($isSelectedRoom && $selectedDurationMonth === 3)>
                                                3 Bulan
                                            </option>

                                            <option value="6" @selected($isSelectedRoom && $selectedDurationMonth === 6)>
                                                6 Bulan
                                            </option>
                                        </select>
                                    </div>

                                    <button type="submit"
                                        class="mt-5 w-full rounded-lg bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800">
                                        Periksa Ketersediaan
                                    </button>
                                </form>

                                {{-- HASIL PENGECEKAN PERIODE --}}
                                @if ($isSelectedRoom && $availabilityCheck)
                                    <div
                                        class="mt-5 rounded-xl border p-4
                            {{ $availabilityCheck['available'] ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }}">

                                        <h4
                                            class="text-sm font-semibold
                                {{ $availabilityCheck['available'] ? 'text-green-800' : 'text-amber-800' }}">
                                            {{ $availabilityCheck['available']
                                                ? 'Kamar tersedia pada periode ini'
                                                : 'Kamar tidak tersedia pada periode ini' }}
                                        </h4>

                                        <div class="mt-3 space-y-1 text-xs text-slate-600">
                                            <p>
                                                Mulai:
                                                {{ \Carbon\Carbon::parse($selectedStartDate)->format('d M Y') }}
                                            </p>

                                            <p>
                                                Selesai:
                                                {{ \Carbon\Carbon::parse($availabilityEndDate)->format('d M Y') }}
                                            </p>

                                            <p>
                                                Durasi:
                                                {{ $selectedDurationMonth }} bulan
                                            </p>
                                        </div>

                                        <p class="mt-3 text-sm leading-relaxed text-slate-700">
                                            {{ $availabilityCheck['message'] }}
                                        </p>

                                        
                                        @if ($availabilityCheck['available'])
                                            <form action="{{ route('tenant.bookings.store') }}" method="POST"
                                                class="mt-4">
                                                @csrf

                                                <input type="hidden" name="room_id" value="{{ $room->id }}">

                                                <input type="hidden" name="start_date" value="{{ $selectedStartDate }}">

                                                <input type="hidden" name="duration_month"
                                                    value="{{ $selectedDurationMonth }}">

                                                @auth
                                                    @if (auth()->user()->role === 'tenant')
                                                        <button type="submit"
                                                            class="w-full rounded-lg bg-blue-900 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                                                            Booking Kamar
                                                        </button>
                                                    @else
                                                        <p class="text-xs text-amber-800">
                                                            Login menggunakan akun penyewa untuk melakukan booking.
                                                        </p>
                                                    @endif
                                                @else
                                                    <a href="{{ route('login') }}"
                                                        class="block w-full rounded-lg bg-blue-900 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-blue-800">
                                                        Login untuk Booking
                                                    </a>
                                                @endauth
                                            </form>
                                        @endif

                                    </div>
                                @endif

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
