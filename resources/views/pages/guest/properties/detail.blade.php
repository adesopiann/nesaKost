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

                                    <div class="space-y-3" data-availability-calendar
                                        data-room-id="{{ $room->id }}"
                                        data-availability-url="{{ route('guest.rooms.availability', $room->id) }}"
                                        data-initial-date="{{ $isSelectedRoom ? $selectedStartDate : '' }}"
                                        data-initial-duration="{{ $isSelectedRoom ? $selectedDurationMonth : '' }}">
                                        <label class="text-sm font-medium text-slate-700" for="duration_month_{{ $room->id }}">
                                            Pilih durasi dan tanggal mulai sewa
                                        </label>

                                        <input type="hidden" id="start_date_{{ $room->id }}" name="start_date"
                                            value="{{ $isSelectedRoom ? $selectedStartDate : '' }}" required>

                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-xs text-slate-500">Pilih durasi terlebih dahulu</span>
                                            <select id="duration_month_{{ $room->id }}" name="duration_month" required
                                                class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-1 focus:ring-blue-900">
                                                <option value="">Durasi</option>
                                                <option value="1" @selected($isSelectedRoom && $selectedDurationMonth === 1)>1 Bulan</option>
                                                <option value="3" @selected($isSelectedRoom && $selectedDurationMonth === 3)>3 Bulan</option>
                                                <option value="6" @selected($isSelectedRoom && $selectedDurationMonth === 6)>6 Bulan</option>
                                            </select>
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

                                        <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-600">
                                            <span class="inline-flex items-center gap-1"><i class="h-3 w-3 rounded bg-white ring-1 ring-slate-300"></i>Tersedia</span>
                                            <span class="inline-flex items-center gap-1"><i class="h-3 w-3 rounded bg-rose-100"></i>Terisi / tidak tersedia</span>
                                            <span class="inline-flex items-center gap-1"><i class="h-3 w-3 rounded bg-blue-900"></i>Tanggal dipilih</span>
                                        </div>
                                        <div data-calendar-feedback role="status" aria-live="polite"
                                            class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
                                            Pilih durasi sewa, kemudian pilih tanggal mulai.
                                        </div>
                                    </div>


                                    <button type="submit" data-calendar-submit disabled
                                        class="mt-5 w-full cursor-not-allowed rounded-lg bg-slate-300 px-4 py-3 text-sm font-semibold text-slate-500 transition disabled:cursor-not-allowed">
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

    {{-- DYNAMIC DATE AVAILABILITY CALENDAR --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const toISO = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            const fromISO = (value) => {
                const [year, month, day] = value.split('-').map(Number);
                return new Date(year, month - 1, day);
            };
            const addMonthsNoOverflow = (date, months) => {
                const result = new Date(date.getFullYear(), date.getMonth() + months, 1);
                const lastDay = new Date(result.getFullYear(), result.getMonth() + 1, 0).getDate();
                result.setDate(Math.min(date.getDate(), lastDay));
                return result;
            };
            const formatDate = (value) => new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(fromISO(value));

            document.querySelectorAll('[data-availability-calendar]').forEach((root) => {
                const form = root.closest('form');
                const roomId = root.dataset.roomId;
                const url = root.dataset.availabilityUrl;
                const dateInput = root.querySelector('input[name="start_date"]');
                const durationSelect = root.querySelector('select[name="duration_month"]');
                const daysElement = root.querySelector('[data-calendar-days]');
                const titleElement = root.querySelector('[data-calendar-title]');
                const feedback = root.querySelector('[data-calendar-feedback]');
                const submit = form.querySelector('[data-calendar-submit]');
                const prevButton = root.querySelector('[data-calendar-prev]');
                const nextButton = root.querySelector('[data-calendar-next]');
                let unavailableRanges = [];
                let loading = true;
                let displayedMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                let selectedDate = root.dataset.initialDate || '';
                let requestToken = 0;

                const setFeedback = (message, type = 'info') => {
                    const styles = {
                        info: 'bg-slate-50 text-slate-600',
                        success: 'bg-green-50 text-green-800',
                        warning: 'bg-amber-50 text-amber-800',
                        error: 'bg-rose-50 text-rose-800'
                    };
                    feedback.className = `rounded-lg p-3 text-sm ${styles[type]}`;
                    feedback.textContent = message;
                };

                const reasonLabel = (reason) => ({
                    rent: 'Sedang disewa',
                    booking: 'Sudah memiliki booking',
                    renewal_priority: 'Dalam masa prioritas perpanjangan penghuni lama'
                }[reason] || 'Tidak tersedia');

                const rangeForDate = (iso) => unavailableRanges.find((range) => iso >= range.start && iso <= range.end);
                const periodEnd = (start, months) => {
                    const end = addMonthsNoOverflow(fromISO(start), months);
                    end.setDate(end.getDate() - 1);
                    return toISO(end);
                };
                const periodConflict = (start, end) => unavailableRanges.find((range) => start <= range.end && end >= range.start);
                const selectedEnd = () => selectedDate && durationSelect.value
                    ? periodEnd(selectedDate, Number(durationSelect.value))
                    : null;
                const findAlternativeStarts = (start, months, limit = 3) => {
                    const suggestions = [];
                    let candidate = fromISO(start);
                    for (let day = 0; day < 370 && suggestions.length < limit; day++) {
                        candidate.setDate(candidate.getDate() + 1);
                        const candidateStart = toISO(candidate);
                        const candidateEnd = periodEnd(candidateStart, months);
                        if (!periodConflict(candidateStart, candidateEnd)) suggestions.push(candidateStart);
                    }
                    return suggestions;
                };

                const updateSelection = () => {
                    submit.disabled = true;
                    submit.className = 'mt-5 w-full cursor-not-allowed rounded-lg bg-slate-300 px-4 py-3 text-sm font-semibold text-slate-500 transition disabled:cursor-not-allowed';
                    if (!selectedDate || !durationSelect.value) {
                        setFeedback('Pilih durasi sewa, kemudian pilih tanggal mulai.', 'info');
                        return;
                    }
                    const months = Number(durationSelect.value);
                    const end = periodEnd(selectedDate, months);
                    const conflict = periodConflict(selectedDate, end);
                    if (conflict) {
                        const alternatives = findAlternativeStarts(selectedDate, months);
                        const suggestionText = alternatives.length
                            ? ` Alternatif tanggal mulai: ${alternatives.map(formatDate).join(', ')}.`
                            : ' Belum ditemukan alternatif dalam 12 bulan ke depan.';
                        setFeedback(`Periode ${formatDate(selectedDate)} – ${formatDate(end)} tidak tersedia. ${reasonLabel(conflict.reason)} pada ${formatDate(conflict.start)} – ${formatDate(conflict.end)}. Pilih tanggal atau durasi lain.${suggestionText}`, 'error');
                        return;
                    }
                    setFeedback(`Periode tersedia: ${formatDate(selectedDate)} – ${formatDate(end)} (${months} bulan). Kamu dapat melanjutkan pemeriksaan dan booking.`, 'success');
                    submit.disabled = false;
                    submit.className = 'mt-5 w-full rounded-lg bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800';
                };

                const renderCalendar = () => {
                    titleElement.textContent = `${monthNames[displayedMonth.getMonth()]} ${displayedMonth.getFullYear()}`;
                    daysElement.replaceChildren();
                    const firstDay = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth(), 1);
                    const offset = firstDay.getDay();
                    const daysInMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() + 1, 0).getDate();
                    for (let i = 0; i < offset; i++) daysElement.append(document.createElement('span'));

                    for (let day = 1; day <= daysInMonth; day++) {
                        const date = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth(), day);
                        const iso = toISO(date);
                        const unavailable = rangeForDate(iso);
                        const isPast = date < today;
                        const isSelected = iso === selectedDate;
                        const endOfSelection = selectedEnd();
                        const isWithinSelectedPeriod = endOfSelection && iso >= selectedDate && iso <= endOfSelection;
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = day;
                        button.className = 'h-9 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500';
                        button.disabled = loading || isPast || Boolean(unavailable);
                        if (isSelected) {
                            button.className += ' bg-blue-900 font-semibold text-white';
                        } else if (isWithinSelectedPeriod && !unavailable) {
                            button.className += ' bg-blue-100 font-medium text-blue-900';
                        } else if (unavailable) {
                            button.className += ' cursor-not-allowed bg-rose-100 text-rose-400 line-through';
                            button.title = `${reasonLabel(unavailable.reason)}: ${unavailable.start} – ${unavailable.end}`;
                        } else if (isPast) {
                            button.className += ' cursor-not-allowed bg-slate-100 text-slate-300';
                        } else {
                            button.className += ' bg-white text-slate-700 hover:bg-blue-50';
                        }
                        button.addEventListener('click', () => {
                            selectedDate = iso;
                            dateInput.value = iso;
                            updateSelection();
                            renderCalendar();
                        });
                        daysElement.append(button);
                    }
                    prevButton.disabled = displayedMonth.getFullYear() === today.getFullYear() && displayedMonth.getMonth() <= today.getMonth();
                    const maxMonth = new Date(today.getFullYear() + 2, today.getMonth(), 1);
                    nextButton.disabled = displayedMonth >= maxMonth;
                    prevButton.classList.toggle('opacity-30', prevButton.disabled);
                    nextButton.classList.toggle('opacity-30', nextButton.disabled);
                };

                prevButton.addEventListener('click', () => {
                    displayedMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() - 1, 1);
                    renderCalendar();
                });
                nextButton.addEventListener('click', () => {
                    displayedMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() + 1, 1);
                    renderCalendar();
                });
                durationSelect.addEventListener('change', updateSelection);

                const loadAvailability = async () => {
                    loading = true;
                    renderCalendar();
                    setFeedback('Memuat informasi ketersediaan kamar...', 'info');
                    const token = ++requestToken;
                    const from = toISO(today);
                    const horizon = new Date(today.getFullYear() + 2, today.getMonth(), today.getDate());
                    try {
                        const response = await fetch(`${url}?from=${from}&to=${toISO(horizon)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!response.ok) throw new Error('Gagal memuat ketersediaan kamar.');
                        const data = await response.json();
                        if (token !== requestToken) return;
                        unavailableRanges = data.unavailable_ranges || [];
                        loading = false;
                        renderCalendar();
                        if (selectedDate) {
                            dateInput.value = selectedDate;
                            updateSelection();
                        } else {
                            const nextAvailable = unavailableRanges
                                .map((range) => range.end)
                                .sort()
                                .filter((date) => date >= from);
                           setFeedback(
                                unavailableRanges.length
                                    ? `Kalender berhasil dimuat. Ada ${unavailableRanges.length} rentang tanggal yang tidak tersedia (ditandai merah). Silakan pilih tanggal mulai dan durasi sewa untuk mengecek ketersediaan kamar.`
                                    : 'Kalender berhasil dimuat. Belum ada tanggal yang terisi pada periode ini. Silakan pilih tanggal mulai dan durasi sewa.',
                                'info'
                            );
                        }
                    } catch (error) {
                        loading = false;
                        submit.disabled = true;
                        renderCalendar();
                        setFeedback('Informasi ketersediaan gagal dimuat. Silakan muat ulang halaman sebelum memilih tanggal.', 'error');
                    }
                };

                if (selectedDate) displayedMonth = new Date(fromISO(selectedDate).getFullYear(), fromISO(selectedDate).getMonth(), 1);
                loadAvailability();
            });
        });
    </script>

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
