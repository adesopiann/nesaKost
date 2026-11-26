@extends('components.layouts.guest.container')

@section('title', $room->room_code . ' - ' . $room->branchDorm->name . ' | nesaKost')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        .room-gallery-image { transition: transform .25s ease; }
        .room-gallery-image:hover { transform: scale(1.025); }
        #room-location-map { min-height: 320px; }
        @media (min-width: 768px) {
            #room-location-map { min-height: 390px; }
        }
    </style>
@endpush

@section('content')
    @php
        $branch = $room->branchDorm;
        $roomPhotos = $room->images;
        $primaryPhoto = $roomPhotos->first();
        $additionalPhotos = $roomPhotos->skip(1)->take(4);
        $fallbackImage = $room->image_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($room->image_path)
            : null;
        $primaryImageUrl = $primaryPhoto
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($primaryPhoto->image_path)
            : $fallbackImage;
        $hasCoordinates = $branch->latitude !== null && $branch->longitude !== null;
    @endphp

    <section class="min-h-screen bg-slate-50 py-8 md:py-10">
        <div class="mx-auto max-w-7xl px-5 sm:px-6">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('guest.home') }}" class="hover:text-blue-900 hover:underline">Cari Kost</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('guest.properties.show', $branch) }}" class="hover:text-blue-900 hover:underline">
                    {{ $branch->name }}
                </a>
                <span aria-hidden="true">/</span>
                <span class="font-medium text-blue-900">Detail Kamar</span>
            </nav>

            <header data-motion-reveal class="mt-5 flex flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:p-7">
                <div>
                    <p class="text-sm text-slate-500">{{ $branch->name }}</p>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-900 md:text-3xl">
                        Kamar {{ $room->room_code }}
                    </h1>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-600">
                        <span class="font-medium text-blue-900">{{ $room->type }}</span>
                        <span aria-hidden="true">•</span>
                        <span>{{ $branch->address }}</span>
                    </div>
                </div>
                <div class="shrink-0 rounded-xl bg-blue-50 px-4 py-3">
                    <p class="text-xs text-blue-800">Harga sewa per bulan</p>
                    <p class="mt-1 text-xl font-semibold text-blue-950">
                        Rp{{ number_format((float) $room->monthly_price, 0, ',', '.') }}
                    </p>
                </div>
            </header>

            <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <main class="min-w-0 space-y-6">
                    {{-- GALERI FOTO --}}
                    <section data-motion-reveal aria-labelledby="room-gallery-title" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h2 id="room-gallery-title" class="text-lg font-semibold text-slate-900">Foto Kamar</h2>
                            @if ($roomPhotos->isNotEmpty())
                                <span class="text-xs text-slate-500">{{ $roomPhotos->count() }} foto</span>
                            @endif
                        </div>

                        @if ($primaryImageUrl)
                            <div class="grid gap-3 md:grid-cols-5">
                                <button type="button" class="group relative overflow-hidden rounded-xl bg-slate-100 text-left md:col-span-3"
                                    data-room-lightbox="{{ $primaryImageUrl }}" aria-label="Lihat foto utama kamar">
                                    <img src="{{ $primaryImageUrl }}" alt="Foto utama kamar {{ $room->room_code }}"
                                        class="room-gallery-image h-64 w-full object-cover md:h-[390px]" fetchpriority="high">
                                    <span class="absolute bottom-3 left-3 rounded-full bg-slate-950/75 px-3 py-1.5 text-xs font-medium text-white">
                                        Foto utama
                                    </span>
                                </button>

                                <div class="grid grid-cols-2 gap-3 md:col-span-2 md:grid-cols-1">
                                    @forelse ($additionalPhotos->take(2) as $photo)
                                        @php($photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($photo->image_path))
                                        <button type="button" class="group relative overflow-hidden rounded-xl bg-slate-100 text-left"
                                            data-room-lightbox="{{ $photoUrl }}" aria-label="Lihat foto tambahan kamar">
                                            <img src="{{ $photoUrl }}" alt="Foto tambahan kamar {{ $room->room_code }}"
                                                class="room-gallery-image h-32 w-full object-cover md:h-[188px]" loading="lazy">
                                        </button>
                                    @empty
                                        <div class="hidden rounded-xl bg-slate-50 md:flex md:items-center md:justify-center">
                                            <p class="px-4 text-center text-sm text-slate-400">Belum ada foto tambahan.</p>
                                        </div>
                                    @endforelse
                                    @if ($roomPhotos->count() > 3)
                                        <p class="col-span-2 text-right text-xs text-slate-500 md:col-span-1">
                                            +{{ $roomPhotos->count() - 3 }} foto lainnya
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if ($roomPhotos->count() > 3)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($roomPhotos->skip(3) as $photo)
                                        @php($photoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($photo->image_path))
                                        <button type="button" data-room-lightbox="{{ $photoUrl }}"
                                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:border-blue-300 hover:text-blue-900">
                                            Lihat foto {{ $loop->iteration + 3 }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="flex h-64 items-center justify-center rounded-xl bg-slate-100 text-sm text-slate-500">
                                Foto kamar belum tersedia.
                            </div>
                        @endif
                    </section>

                    {{-- INFORMASI KAMAR --}}
                    <section data-motion-reveal class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Informasi Kamar</h2>
                        <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm text-slate-500">Kode kamar</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $room->room_code }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-slate-500">Tipe kamar</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $room->type }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-slate-500">Cabang kost</dt>
                                <dd class="mt-1 font-medium text-slate-900">{{ $branch->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-slate-500">Harga sewa</dt>
                                <dd class="mt-1 font-semibold text-blue-900">
                                    Rp{{ number_format((float) $room->monthly_price, 0, ',', '.') }} / bulan
                                </dd>
                            </div>
                        </dl>
                        @if ($room->description)
                            <div class="mt-6 border-t border-slate-100 pt-5">
                                <h3 class="text-sm font-semibold text-slate-800">Deskripsi Kamar</h3>
                                <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $room->description }}</p>
                            </div>
                        @endif
                    </section>

                    {{-- FASILITAS KAMAR --}}
                    <section data-motion-reveal class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Fasilitas Kamar</h2>
                        <p class="mt-1 text-sm text-slate-500">Fasilitas yang tersedia di dalam kamar ini.</p>
                        @if ($room->facilities->isNotEmpty())
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($room->facilities as $facility)
                                    <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-900">
                                            <i data-feather="check" class="h-4 w-4"></i>
                                        </span>
                                        <span class="text-sm font-medium text-slate-700">{{ $facility->facility_name }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                                Informasi fasilitas kamar belum tersedia.
                            </p>
                        @endif
                    </section>

                    {{-- FASILITAS UMUM --}}
                    <section data-motion-reveal class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                        <h2 class="text-lg font-semibold text-slate-900">Fasilitas Umum Cabang</h2>
                        <p class="mt-1 text-sm text-slate-500">Fasilitas yang dapat digunakan oleh penghuni di area cabang kost.</p>
                        @if ($branch->facilities->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($branch->facilities as $facility)
                                    <span class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-900">
                                        <i data-feather="check" class="h-4 w-4"></i>
                                        {{ $facility->facility_name }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                                Informasi fasilitas umum belum tersedia.
                            </p>
                        @endif
                    </section>

                    {{-- LOKASI CABANG --}}
                    <section data-motion-reveal class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Lokasi Cabang Kost</h2>
                            <p class="mt-1 text-sm text-slate-500">Lihat lokasi cabang dan area di sekitarnya.</p>
                            <p class="mt-4 flex items-start gap-2 text-sm leading-6 text-slate-700">
                                <i data-feather="map-pin" class="mt-1 h-4 w-4 shrink-0 text-blue-900"></i>
                                {{ $branch->address }}
                            </p>
                        </div>

                        @if ($hasCoordinates)
                            <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                                <div id="room-location-map" class="z-0 w-full"
                                    data-lat="{{ $branch->latitude }}"
                                    data-lng="{{ $branch->longitude }}"
                                    data-name="{{ $branch->name }}"></div>
                            </div>
                            <a href="https://www.openstreetmap.org/?mlat={{ $branch->latitude }}&mlon={{ $branch->longitude }}#map=17/{{ $branch->latitude }}/{{ $branch->longitude }}"
                                target="_blank" rel="noopener noreferrer"
                                class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-900 hover:underline">
                                Buka lokasi di OpenStreetMap
                                <i data-feather="external-link" class="h-4 w-4"></i>
                            </a>
                        @else
                            <div class="mt-5 rounded-xl bg-slate-50 p-6 text-center">
                                <i data-feather="map-pin" class="mx-auto h-6 w-6 text-slate-400"></i>
                                <p class="mt-2 text-sm text-slate-500">Titik lokasi cabang belum tersedia.</p>
                            </div>
                        @endif
                    </section>
                </main>

                {{-- SIDEBAR BOOKING --}}
                <aside data-motion-reveal class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                    <p class="text-sm text-slate-500">Harga sewa per bulan</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-900">
                        Rp{{ number_format((float) $room->monthly_price, 0, ',', '.') }}
                    </p>

                    <form action="{{ route('guest.rooms.show', $room->room_code) }}" method="GET"
                        class="mt-5" id="room-booking-form">
                        <div data-availability-calendar
                            data-availability-url="{{ route('guest.rooms.availability', $room->room_code) }}"
                            data-initial-date="{{ $selectedStartDate ?? '' }}"
                            data-initial-duration="{{ $selectedDurationMonth ?? '' }}">
                            <label for="duration_month_detail" class="text-sm font-semibold text-slate-800">
                                Durasi sewa
                            </label>
                            <select id="duration_month_detail" name="duration_month" required
                                class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-900 focus:outline-none focus:ring-1 focus:ring-blue-900">
                                <option value="">Pilih durasi</option>
                                <option value="1" @selected($selectedDurationMonth === 1)>1 Bulan</option>
                                <option value="3" @selected($selectedDurationMonth === 3)>3 Bulan</option>
                                <option value="6" @selected($selectedDurationMonth === 6)>6 Bulan</option>
                            </select>

                            <input type="hidden" name="start_date" value="{{ $selectedStartDate ?? '' }}">

                            <div class="mt-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-slate-800">Tanggal mulai sewa</h3>
                                    <span class="text-xs text-slate-500">Pilih tanggal</span>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="mb-3 flex items-center justify-between">
                                        <button type="button" data-calendar-prev aria-label="Bulan sebelumnya"
                                            class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">‹</button>
                                        <h4 data-calendar-title class="text-sm font-semibold text-slate-800"></h4>
                                        <button type="button" data-calendar-next aria-label="Bulan berikutnya"
                                            class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">›</button>
                                    </div>
                                    <div class="mb-2 grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-400">
                                        <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                                    </div>
                                    <div data-calendar-days class="grid grid-cols-7 gap-1"></div>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-x-3 gap-y-2 text-[11px] text-slate-600">
                                <span class="inline-flex items-center gap-1"><i class="h-3 w-3 rounded bg-white ring-1 ring-slate-300"></i>Tersedia</span>
                                <span class="inline-flex items-center gap-1"><i class="h-3 w-3 rounded bg-rose-100"></i>Tidak tersedia</span>
                                <span class="inline-flex items-center gap-1"><i class="h-3 w-3 rounded bg-blue-900"></i>Dipilih</span>
                            </div>
                            <div data-calendar-feedback role="status" aria-live="polite"
                                class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
                                Pilih durasi sewa, kemudian pilih tanggal mulai.
                            </div>
                        </div>

                        <button type="submit" data-calendar-submit disabled
                            class="mt-4 w-full cursor-not-allowed rounded-lg bg-slate-300 px-4 py-3 text-sm font-semibold text-slate-500 transition disabled:cursor-not-allowed">
                            Periksa Ketersediaan
                        </button>
                    </form>

                    @if ($availabilityCheck)
                        <div class="mt-5 rounded-xl border p-4 {{ $availabilityCheck['available'] ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }}">
                            <h3 class="text-sm font-semibold {{ $availabilityCheck['available'] ? 'text-green-800' : 'text-amber-800' }}">
                                {{ $availabilityCheck['available'] ? 'Kamar tersedia pada periode ini' : 'Kamar tidak tersedia pada periode ini' }}
                            </h3>
                            <div class="mt-3 space-y-1 text-xs text-slate-600">
                                <p>Mulai: {{ \Carbon\Carbon::parse($selectedStartDate)->format('d M Y') }}</p>
                                <p>Selesai: {{ \Carbon\Carbon::parse($availabilityEndDate)->format('d M Y') }}</p>
                                <p>Durasi: {{ $selectedDurationMonth }} bulan</p>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ $availabilityCheck['message'] }}</p>
                            <div class="mt-3 border-t border-current/10 pt-3">
                                <p class="text-xs text-slate-600">Estimasi total sewa</p>
                                <p class="text-lg font-bold text-slate-900">
                                    Rp{{ number_format((float) $room->monthly_price * $selectedDurationMonth, 0, ',', '.') }}
                                </p>
                            </div>

                            @if ($availabilityCheck['available'])
                                <form action="{{ route('tenant.bookings.store') }}" method="POST" class="mt-4">
                                    @csrf
                                    <input type="hidden" name="room_id" value="{{ $room->id }}">
                                    <input type="hidden" name="start_date" value="{{ $selectedStartDate }}">
                                    <input type="hidden" name="duration_month" value="{{ $selectedDurationMonth }}">
                                    @auth
                                        @if (auth()->user()->role === 'tenant')
                                            <button type="submit" class="w-full rounded-lg bg-blue-900 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                                                Booking Kamar
                                            </button>
                                        @else
                                            <p class="text-xs text-amber-800">Login menggunakan akun penyewa untuk melakukan booking.</p>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="block w-full rounded-lg bg-blue-900 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-blue-800">
                                            Login untuk Booking
                                        </a>
                                    @endauth
                                </form>
                            @endif
                        </div>
                    @endif

                    <p class="mt-4 text-xs leading-5 text-slate-500">
                        Ketersediaan akan diperiksa kembali saat booking untuk memastikan periode masih dapat dipesan.
                    </p>
                </aside>
            </div>
        </div>
    </section>

    {{-- DYNAMIC AVAILABILITY CALENDAR --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const toISO = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            const fromISO = value => {
                const [year, month, day] = value.split('-').map(Number);
                return new Date(year, month - 1, day);
            };
            const addMonthsNoOverflow = (date, months) => {
                const result = new Date(date.getFullYear(), date.getMonth() + months, 1);
                result.setDate(Math.min(date.getDate(), new Date(result.getFullYear(), result.getMonth() + 1, 0).getDate()));
                return result;
            };
            const formatDate = value => new Intl.DateTimeFormat('id-ID', {
                day: 'numeric', month: 'long', year: 'numeric'
            }).format(fromISO(value));

            document.querySelectorAll('[data-availability-calendar]').forEach(root => {
                const form = root.closest('form');
                const url = root.dataset.availabilityUrl;
                const dateInput = root.querySelector('input[name="start_date"]');
                const duration = root.querySelector('select[name="duration_month"]');
                const days = root.querySelector('[data-calendar-days]');
                const title = root.querySelector('[data-calendar-title]');
                const feedback = root.querySelector('[data-calendar-feedback]');
                const submit = form.querySelector('[data-calendar-submit]');
                const previous = root.querySelector('[data-calendar-prev]');
                const next = root.querySelector('[data-calendar-next]');
                let ranges = [];
                let loading = true;
                let selected = root.dataset.initialDate || '';
                let displayed = selected ? fromISO(selected) : new Date(today.getFullYear(), today.getMonth(), 1);
                displayed = new Date(displayed.getFullYear(), displayed.getMonth(), 1);
                let token = 0;

                const reasonLabel = reason => ({
                    rent: 'Sedang disewa',
                    booking: 'Sudah memiliki booking',
                    renewal_priority: 'Dalam masa prioritas perpanjangan penghuni lama'
                }[reason] || 'Tidak tersedia');
                const rangeFor = iso => ranges.find(range => iso >= range.start && iso <= range.end);
                const periodEnd = (start, months) => {
                    const end = addMonthsNoOverflow(fromISO(start), months);
                    end.setDate(end.getDate() - 1);
                    return toISO(end);
                };
                const conflictFor = (start, end) => ranges.find(range => start <= range.end && end >= range.start);
                const setFeedback = (message, type = 'info') => {
                    const classes = {
                        info: 'bg-slate-50 text-slate-600',
                        success: 'bg-green-50 text-green-800',
                        error: 'bg-rose-50 text-rose-800'
                    };
                    feedback.className = `mt-4 rounded-lg p-3 text-sm ${classes[type]}`;
                    feedback.textContent = message;
                };
                const updateSelection = () => {
                    submit.disabled = true;
                    submit.className = 'mt-4 w-full cursor-not-allowed rounded-lg bg-slate-300 px-4 py-3 text-sm font-semibold text-slate-500';
                    if (!selected || !duration.value) {
                        setFeedback('Pilih durasi sewa, kemudian pilih tanggal mulai.');
                        return;
                    }
                    const end = periodEnd(selected, Number(duration.value));
                    const conflict = conflictFor(selected, end);
                    if (conflict) {
                        setFeedback(`Periode ${formatDate(selected)} – ${formatDate(end)} tidak tersedia. ${reasonLabel(conflict.reason)} pada ${formatDate(conflict.start)} – ${formatDate(conflict.end)}. Silakan pilih tanggal atau durasi lain.`, 'error');
                        return;
                    }
                    setFeedback(`Periode ${formatDate(selected)} – ${formatDate(end)} (${duration.value} bulan) dapat diajukan. Tekan “Periksa Ketersediaan” untuk konfirmasi.`, 'success');
                    submit.disabled = false;
                    submit.className = 'mt-4 w-full rounded-lg bg-blue-900 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800';
                };
                const render = () => {
                    title.textContent = `${monthNames[displayed.getMonth()]} ${displayed.getFullYear()}`;
                    days.replaceChildren();
                    const first = new Date(displayed.getFullYear(), displayed.getMonth(), 1);
                    for (let i = 0; i < first.getDay(); i++) days.append(document.createElement('span'));
                    const count = new Date(displayed.getFullYear(), displayed.getMonth() + 1, 0).getDate();
                    for (let day = 1; day <= count; day++) {
                        const date = new Date(displayed.getFullYear(), displayed.getMonth(), day);
                        const iso = toISO(date);
                        const unavailable = rangeFor(iso);
                        const past = date < today;
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = day;
                        button.className = 'h-9 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500';
                        button.disabled = loading || past || Boolean(unavailable);
                        // Highlight the entire proposed rental period when it is conflict-free.
                        const selectedEnd = selected && duration.value
                            ? periodEnd(selected, Number(duration.value))
                            : '';
                        const selectedConflict = selectedEnd
                            ? conflictFor(selected, selectedEnd)
                            : null;
                        const inSelectedPeriod = selectedEnd && !selectedConflict
                            && iso >= selected && iso <= selectedEnd;

                        if (unavailable) {
                            button.className += ' cursor-not-allowed bg-rose-100 text-rose-400 line-through';
                            button.title = `${reasonLabel(unavailable.reason)}: ${unavailable.start} – ${unavailable.end}`;
                        } else if (past) {
                            button.className += ' cursor-not-allowed bg-slate-100 text-slate-300';
                        } else if (inSelectedPeriod && (iso === selected || iso === selectedEnd)) {
                            button.className += ' bg-blue-900 font-semibold text-white';
                            button.title = iso === selected ? 'Tanggal mulai periode sewa' : 'Tanggal akhir periode sewa';
                        } else if (inSelectedPeriod) {
                            button.className += ' bg-blue-100 font-medium text-blue-900';
                            button.title = 'Termasuk periode sewa terpilih';
                        } else {
                            button.className += ' bg-white text-slate-700 hover:bg-blue-50';
                        }
                        button.addEventListener('click', () => {
                            selected = iso;
                            dateInput.value = iso;
                            updateSelection();
                            render();
                        });
                        days.append(button);
                    }
                    previous.disabled = displayed.getFullYear() === today.getFullYear() && displayed.getMonth() <= today.getMonth();
                    const maxMonth = new Date(today.getFullYear() + 2, today.getMonth(), 1);
                    next.disabled = displayed >= maxMonth;
                    previous.classList.toggle('opacity-30', previous.disabled);
                    next.classList.toggle('opacity-30', next.disabled);
                };
                previous.addEventListener('click', () => {
                    displayed = new Date(displayed.getFullYear(), displayed.getMonth() - 1, 1);
                    render();
                });
                next.addEventListener('click', () => {
                    displayed = new Date(displayed.getFullYear(), displayed.getMonth() + 1, 1);
                    render();
                });
                duration.addEventListener('change', updateSelection);

                const load = async () => {
                    loading = true;
                    render();
                    setFeedback('Memuat informasi ketersediaan kamar...');
                    const currentToken = ++token;
                    const from = toISO(today);
                    const horizon = new Date(today.getFullYear() + 2, today.getMonth(), today.getDate());
                    try {
                        const response = await fetch(`${url}?from=${from}&to=${toISO(horizon)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!response.ok) throw new Error('Availability request failed');
                        const data = await response.json();
                        if (currentToken !== token) return;
                        ranges = data.unavailable_ranges || [];
                        loading = false;
                        render();
                        if (selected) {
                            dateInput.value = selected;
                            updateSelection();
                        } else {
                            setFeedback(ranges.length
                                ? `Kalender berhasil dimuat. ${ranges.length} rentang tanggal tidak tersedia ditandai merah.`
                                : 'Kalender berhasil dimuat. Belum ada rentang tanggal yang terisi pada periode ini.');
                        }
                    } catch (error) {
                        loading = false;
                        render();
                        submit.disabled = true;
                        setFeedback('Informasi ketersediaan gagal dimuat. Silakan muat ulang halaman.', 'error');
                    }
                };
                if (selected && duration.value) updateSelection();
                load();
            });
        });
    </script>

    {{-- LIGHTBOX FOTO --}}
    <div id="room-photo-lightbox" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/90 p-4"
        role="dialog" aria-modal="true" aria-label="Pratinjau foto kamar">
        <button type="button" id="room-photo-lightbox-close"
            class="absolute right-5 top-5 rounded-full bg-white/15 px-4 py-2 text-sm font-medium text-white hover:bg-white/25">
            Tutup ✕
        </button>
        <img id="room-photo-lightbox-image" src="" alt="Pratinjau foto kamar"
            class="max-h-[85vh] max-w-full rounded-xl object-contain">
    </div>
@endsection

@push('scripts')
    @if ($hasCoordinates)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Pratinjau foto kamar.
            const lightbox = document.getElementById('room-photo-lightbox');
            const lightboxImage = document.getElementById('room-photo-lightbox-image');
            const closeLightbox = document.getElementById('room-photo-lightbox-close');

            document.querySelectorAll('[data-room-lightbox]').forEach((button) => {
                button.addEventListener('click', () => {
                    lightboxImage.src = button.dataset.roomLightbox;
                    lightbox.classList.remove('hidden');
                    lightbox.classList.add('flex');
                    closeLightbox.focus();
                });
            });

            const hideLightbox = () => {
                lightbox.classList.add('hidden');
                lightbox.classList.remove('flex');
                lightboxImage.src = '';
            };

            closeLightbox.addEventListener('click', hideLightbox);
            lightbox.addEventListener('click', (event) => {
                if (event.target === lightbox) hideLightbox();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !lightbox.classList.contains('hidden')) hideLightbox();
            });

            // Peta lokasi cabang (read-only).
            const mapElement = document.getElementById('room-location-map');
            if (mapElement && typeof L !== 'undefined') {
                const lat = Number(mapElement.dataset.lat);
                const lng = Number(mapElement.dataset.lng);
                const branchName = mapElement.dataset.name;

                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    const map = L.map(mapElement, {
                        scrollWheelZoom: false,
                        dragging: true,
                        doubleClickZoom: true,
                        touchZoom: true,
                    }).setView([lat, lng], 16);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);

                    L.marker([lat, lng]).addTo(map).bindPopup(
                        `<strong>${String(branchName).replace(/[&<>"']/g, (char) => ({
                            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                        }[char]))}</strong><br>Lokasi cabang kost`
                    );
                }
            }
        });
    </script>
@endpush
