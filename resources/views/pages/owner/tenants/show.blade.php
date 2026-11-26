@extends('components.layouts.owner.container')

@section('title', 'Detail Penyewa')

@section('main')
    @php
        $user = $tenant->user;
        $name = trim(($user?->fname ?? '') . ' ' . ($user?->lname ?? '')) ?: 'Penyewa';
        $statusLabels = [
            'active' => 'Aktif',
            'scheduled' => 'Terjadwal',
            'booking_pending' => 'Menunggu Pembayaran',
            'historical' => 'Riwayat',
        ];
        $statusClasses = [
            'active' => 'bg-emerald-50 text-emerald-700',
            'scheduled' => 'bg-sky-50 text-sky-700',
            'booking_pending' => 'bg-amber-50 text-amber-700',
            'historical' => 'bg-slate-100 text-slate-600',
        ];
        $eventClasses = [
            'booking' => 'bg-indigo-50 text-indigo-700',
            'payment' => 'bg-emerald-50 text-emerald-700',
            'rent' => 'bg-sky-50 text-sky-700',
        ];
        $eventIcons = ['booking' => 'calendar', 'payment' => 'credit-card', 'rent' => 'file-text'];
        $eventStatus = [
            'pending_payment' => 'Menunggu Pembayaran',
            'confirmed' => 'Dikonfirmasi',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
            'paid' => 'Berhasil',
            'pending' => 'Menunggu',
            'failed' => 'Gagal',
            'scheduled' => 'Terjadwal',
            'active' => 'Berjalan',
            'completed' => 'Selesai',
        ];
        $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    @endphp
    <div class="space-y-6">
        <section
            class="flex flex-col gap-3 rounded-xl border border-slate-100 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div><a href="{{ route('owner.tenants.index') }}"
                    class="mb-2 inline-flex items-center gap-1 text-xs font-semibold text-[#315F96] hover:underline"><i
                        data-feather="arrow-left" class="h-3.5 w-3.5"></i> Kembali ke Data Penyewa</a>
                <h1 class="text-2xl font-bold text-slate-900">Detail Penyewa</h1>
                <p class="mt-1 text-sm text-slate-500">Informasi profil dan seluruh riwayat transaksi di cabang milik Owner.
                </p>
            </div>
            <span
                class="w-fit rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClasses[$tenant->derived_status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabels[$tenant->derived_status] ?? 'Riwayat' }}</span>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="rounded-xl border border-slate-100 bg-white p-5 lg:col-span-1">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-lg font-bold text-[#315F96]">
                        {{ strtoupper(substr($name, 0, 1)) }}</div>
                    <div class="min-w-0">
                        <h2 class="truncate font-bold text-slate-900">{{ $name }}</h2>
                    </div>
                </div>
                <div class="mt-5 space-y-4 text-sm">
                    <div>
                        <p class="text-xs text-slate-400">Email</p>
                        <p class="mt-1 break-all font-medium text-slate-700">{{ $user?->email ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Nomor HP</p>
                        <p class="mt-1 font-medium text-slate-700">{{ $user?->phone_number ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Alamat</p>
                        <p class="mt-1 font-medium text-slate-700">{{ $user?->address ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Pekerjaan</p>
                        <p class="mt-1 font-medium text-slate-700">{{ $tenant->profession ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Kontak Darurat</p>
                        <p class="mt-1 font-medium text-slate-700">{{ $tenant->emergency_name ?: '-' }}</p>
                        <p class="text-xs text-slate-500">{{ $tenant->emergency_number ?: '' }}</p>
                    </div>
                </div>
                <p class="mt-5 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500"><i data-feather="lock"
                        class="mr-1 inline h-3.5 w-3.5"></i> Informasi profil bersifat read-only. Pengelola hanya dapat
                    melakukan monitoring.</p>
            </article>
            <div class="flex flex-col gap-4">
                <section class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Informasi Sewa Saat Ini</h2>
                            <p class="mt-1 text-xs text-slate-500">Informasi kamar dan periode sewa yang sedang berlangsung.
                            </p>
                        </div>
                        @if ($currentRent)
                            <span
                                class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">Sedang
                                Ditempati</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-500">Tidak
                                Ada Sewa Aktif</span>
                        @endif
                    </div>

                    @if ($currentRent)
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-lg bg-slate-50 p-4">
                                <p class="text-xs text-slate-400">Kamar</p>
                                <p class="mt-1 font-semibold text-slate-800">{{ $currentRent->room?->room_code ?? '-' }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <p class="text-xs text-slate-400">Cabang Kost</p>
                                <p class="mt-1 font-semibold text-slate-800">
                                    {{ $currentRent->room?->branchDorm?->name ?? '-' }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-4">
                                <p class="text-xs text-slate-400">Periode Sewa</p>
                                <p class="mt-1 font-semibold text-slate-800">
                                    {{ $currentRent->start_date?->format('d M Y') ?? '-' }} –
                                    {{ $currentRent->end_date?->format('d M Y') ?? '-' }}
                                </p>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Kode sewa:
                            {{ $currentRent->rent_code ?: 'Rent #' . $currentRent->id }}</p>
                    @else
                        <div class="rounded-lg bg-slate-50 px-4 py-5 text-sm text-slate-500">
                            Penyewa ini tidak memiliki transaksi sewa berstatus aktif yang mencakup hari ini.
                        </div>
                    @endif
                </section>
                <div class="grid grid-cols-2 gap-3">
                    <article class="rounded-xl border border-slate-100 bg-white p-5">
                        <p class="text-xs text-slate-500">Total Booking</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($transactionSummary['bookings']) }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-100 bg-white p-5">
                        <p class="text-xs text-slate-500">Total Sewa</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($transactionSummary['rents']) }}
                        </p>
                    </article>
                    <article class="rounded-xl border border-slate-100 bg-white p-5">
                        <p class="text-xs text-slate-500">Total Pembayaran</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($transactionSummary['payments']) }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-100 bg-white p-5">
                        <p class="text-xs text-slate-500">Pembayaran Berhasil</p>
                        <p class="mt-2 text-xl font-bold text-emerald-700">{{ $money($transactionSummary['paid']) }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
            <div class="mb-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Riwayat Transaksi</h2>
                    <p class="text-xs text-slate-500">Booking, pembayaran, dan sewa dari seluruh cabang milik Owner,
                        diurutkan dari yang terbaru.</p>
                </div><span class="text-xs text-slate-400">{{ $events->count() }} aktivitas</span>
            </div>
            @if ($events->isEmpty())
                <div class="py-12 text-center"><i data-feather="clock" class="mx-auto h-8 w-8 text-slate-300"></i>
                    <p class="mt-3 font-semibold text-slate-700">Belum ada riwayat transaksi</p>
                </div>
            @else
                <div class="space-y-0">
                    @foreach ($events as $event)
                        <article
                            class="relative flex gap-4 border-l border-slate-200 pb-6 pl-6 last:border-l-transparent last:pb-0">
                            <div
                                class="absolute -left-[17px] top-0 flex h-8 w-8 items-center justify-center rounded-full {{ $eventClasses[$event['type']] ?? 'bg-slate-100 text-slate-600' }}">
                                <i data-feather="{{ $eventIcons[$event['type']] ?? 'activity' }}" class="h-4 w-4"></i>
                            </div>
                            <div class="min-w-0 flex-1 rounded-xl border border-slate-100 p-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="font-semibold text-slate-800">{{ $event['title'] }}</h3>
                                        <p class="mt-1 text-xs text-slate-500">{{ $event['code'] }}</p>
                                    </div>
                                    <div class="sm:text-right">
                                        <p class="text-xs text-slate-400">
                                            {{ $event['date']?->format('d M Y, H:i') ?? '-' }}</p><span
                                            class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-600">{{ $eventStatus[$event['status']] ?? ucfirst($event['status']) }}</span>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-slate-600">{{ $event['description'] }}</p>
                                @if ($event['period'])
                                    <p class="mt-1 text-xs text-slate-500">Periode: {{ $event['period'] }}</p>
                                @endif
                                @if ($event['amount'] !== null)
                                    <p class="mt-3 text-sm font-bold text-slate-800">{{ $money($event['amount']) }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
