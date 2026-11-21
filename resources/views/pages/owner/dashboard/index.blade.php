@extends('components.layouts.owner.container')

@section('title', 'Dashboard')

@section('main')
    @php
        $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $branchColors = ['#2450D8', '#12A5E5', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444', '#64748B'];
        $maxMethodCount = max(1, (int) ($paymentMethodTotals->max('transaction_count') ?? 1));
        $yearOptions = range((int) $year, max((int) $year, (int) now()->year - 4));
    @endphp

    <div class="space-y-6">
        <section class="rounded-xl border border-slate-100 bg-white px-5 py-5 sm:px-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Ringkasan operasional pengelolaan kos.</p>
        </section>



        <section class="flex flex-col gap-3 rounded-xl border border-slate-100 bg-white p-3 sm:flex-row sm:items-center">
            <div class="flex flex-wrap gap-2">

                {{-- Semua Cabang --}}
                <a href="{{ route('owner.dashboard') }}"
                    class="rounded-lg px-3 py-2 text-xs font-semibold transition
            {{ $selectedBranchId === null ? 'bg-[#1D4E80] text-white' : 'bg-[#F0F3FF] text-slate-600 hover:bg-blue-100' }}">
                    Semua Cabang
                </a>

                {{-- Seluruh Cabang Milik Owner --}}
                @foreach ($branches as $branch)
                    <a href="{{ route('owner.dashboard', ['branch_id' => $branch->id]) }}"
                        class="rounded-lg px-3 py-2 text-xs font-medium transition
                {{ $selectedBranchId === (string) $branch->id
                    ? 'bg-[#1D4E80] text-white font-semibold'
                    : 'bg-[#F0F3FF] text-slate-600 hover:bg-blue-100' }}">
                        {{ $branch->name }}
                    </a>
                @endforeach

            </div>
        </section>



        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @php
                $statCards = [
                    ['label' => 'Total Cabang Kos', 'value' => $stats['branches'], 'entity' => 'cabang', 'icon' => 'grid'],
                    ['label' => 'Total Kamar', 'value' => $stats['rooms'], 'entity' => 'seluruh status', 'icon' => 'columns'],
                    ['label' => 'Penyewa Aktif', 'value' => $stats['active_tenants'], 'entity' => 'penyewa saat ini', 'icon' => 'user-check'],
                    ['label' => 'Total Penyewa Historis', 'value' => $stats['tenants'], 'entity' => 'penyewa unik', 'icon' => 'users'],
                    ['label' => 'Booking Menunggu Pembayaran', 'value' => $stats['pending_bookings'], 'entity' => 'booking', 'icon' => 'clock'],
                    ['label' => 'Kamar Aktif', 'value' => $stats['active_rooms'], 'entity' => 'kamar', 'icon' => 'door-open'],
                ];
            @endphp
            @foreach ($statCards as $card)
                <article class="rounded-xl border border-slate-100 bg-white p-5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-[#315F96]">
                            <i data-feather="{{ $card['icon'] }}" class="h-5 w-5"></i>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span
                            class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($card['value']) }}</span>
                        <span class="text-xs font-medium text-slate-500">{{ $card['entity'] }}</span>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <article class="rounded-xl border border-slate-100 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Kamar Aktif</p>
                <p class="mt-1 text-2xl font-bold text-slate-800">{{ number_format($stats['active_rooms']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Masuk perhitungan okupansi</p>
            </article>
            <article class="rounded-xl border border-slate-100 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Kamar Nonaktif</p>
                <p class="mt-1 text-2xl font-bold text-slate-800">{{ number_format($stats['inactive_rooms']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Tidak masuk perhitungan okupansi</p>
            </article>
            <article class="rounded-xl border border-slate-100 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Kamar Maintenance</p>
                <p class="mt-1 text-2xl font-bold text-slate-800">{{ number_format($stats['maintenance_rooms']) }}</p>
                <p class="mt-1 text-[11px] text-slate-500">Tidak masuk perhitungan okupansi</p>
            </article>
        </section>

        <section class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="flex items-center gap-2 text-base font-semibold text-slate-800">Okupansi Kamar per Cabang
                    </h2>
                    <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500">Dihitung dari jumlah kamar dan data sewa
                        aktif yang tanggal sewanya mencakup hari ini.</p>
                </div>
                <div class="flex flex-wrap gap-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-700"></span>Kamar
                        Terisi</span>
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-sky-500"></span>Kamar
                        Kosong</span>
                </div>
            </div>

            @if ($branchOccupancy->isEmpty())
                <div class="mt-4 rounded-lg bg-[#F0F3FF] px-4 py-12 text-center text-sm text-slate-500">Belum ada data
                    cabang atau kamar untuk ditampilkan.</div>
            @else
                <div class="mt-4 rounded-lg bg-[#F0F3FF] p-4 sm:p-5">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3" id="occupancy-chart-grid">
                        @foreach ($branchOccupancy as $index => $branch)
                            <article class="occupancy-card min-w-0 rounded-lg" data-branch="{{ $branch['id'] }}">
                                <div class="mx-auto h-40 w-40 sm:h-44 sm:w-44">
                                    <canvas id="occupancy-chart-{{ $branch['id'] }}"
                                        aria-label="Grafik okupansi {{ $branch['name'] }}"></canvas>
                                </div>
                                <p class="mt-3 text-center text-xs font-semibold text-slate-600">{{ $branch['name'] }}</p>
                                <div class="mt-3 rounded-lg border border-slate-100 bg-white p-3">
                                    <p class="flex items-center gap-2 text-xs text-slate-500">
                                        <span class="h-2 w-2 rounded-full"
                                            style="background-color: {{ $branchColors[$index % count($branchColors)] }}"></span>
                                        {{ $branch['name'] }}
                                    </p>
                                    <p class="mt-1 text-xl font-bold text-slate-800">{{ $branch['percentage'] }}%</p>
                                    <p class="text-[11px] text-slate-500">{{ $branch['occupied'] }} terisi / {{ $branch['active_rooms'] }} kamar aktif · {{ $branch['vacant'] }} kosong</p>
                                    <p class="mt-1 text-[10px] text-slate-400">Nonaktif: {{ $branch['inactive_rooms'] }} · Maintenance: {{ $branch['maintenance_rooms'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <article class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 text-base font-semibold text-slate-800">Ringkasan Data Sewa</h2>
                        <p class="mt-1 text-xs text-slate-500">Kontrak yang dibuat pada periode terpilih.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="GET" action="{{ route('owner.dashboard') }}" class="flex items-center gap-2">
                            @if ($selectedBranchId !== null)<input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">@endif
                            <input type="hidden" name="payment_month" value="{{ $paymentMonth }}"><input type="hidden" name="payment_year" value="{{ $paymentYear }}"><input type="hidden" name="year" value="{{ $year }}">
                            <select name="rent_month" aria-label="Bulan ringkasan sewa" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs" onchange="this.form.submit()"><option value="" @selected($rentMonth === null)>Semua Bulan</option>@foreach($monthOptions as $number => $label)<option value="{{ $number }}" @selected($rentMonth === $number)>{{ $label }}</option>@endforeach</select>
                            <select name="rent_year" aria-label="Tahun ringkasan sewa" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs" onchange="this.form.submit()"><option value="" @selected($rentYear === null)>Semua Tahun</option>@foreach($yearOptions as $optionYear)<option value="{{ $optionYear }}" @selected($rentYear === $optionYear)>{{ $optionYear }}</option>@endforeach</select>
                        </form>
                        <a href="{{ route('owner.rents', array_filter(['branch_id' => $selectedBranchId, 'month' => $rentMonth, 'year' => $rentYear])) }}" class="inline-flex items-center gap-1 rounded-lg bg-[#1D4E80] px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Data Sewa <i data-feather="arrow-up-right" class="h-3 w-3"></i></a>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-5">
                    <div class="rounded-lg bg-[#F0F3FF] p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Total Kontrak</p>
                        <p class="mt-1 text-xl font-bold text-slate-800">{{ number_format($totalRents) }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">Kontrak tercatat</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-800">Berjalan</p>
                        <p class="mt-1 text-xl font-bold text-emerald-800">{{ number_format($activeRents) }}</p>
                        <p class="mt-1 text-[11px] text-emerald-800">Status active</p>
                    </div>
                    <div class="rounded-lg bg-slate-100 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Terjadwal</p>
                        <p class="mt-1 text-xl font-bold text-slate-700">{{ number_format($scheduledRents) }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">Belum dimulai</p>
                    </div>
                    <div class="rounded-lg bg-slate-100 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Selesai</p>
                        <p class="mt-1 text-xl font-bold text-slate-700">{{ number_format($finishedRents) }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">Kontrak selesai</p>
                    </div>
                    <div class="rounded-lg bg-rose-50 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-rose-700">Dibatalkan</p>
                        <p class="mt-1 text-xl font-bold text-rose-700">{{ number_format($cancelledRents) }}</p>
                        <p class="mt-1 text-[11px] text-rose-700">Kontrak batal</p>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="font-medium text-slate-600">Rasio Okupansi Kamar Aktif ({{ $occupiedRooms }} /
                            {{ $roomCount }})</span>
                        <span
                            class="font-semibold text-slate-700">{{ $roomCount > 0 ? round(($occupiedRooms / $roomCount) * 100) : 0 }}%
                            Terisi</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-blue-100">
                        <div class="h-full rounded-full bg-[#1D4E80]"
                            style="width: {{ $roomCount > 0 ? min(100, round(($occupiedRooms / $roomCount) * 100)) : 0 }}%">
                        </div>
                    </div>
                </div>
                <p class="mt-5 rounded-lg bg-slate-50 px-4 py-3 text-center text-xs text-slate-500">Ringkasan sewa mengikuti cabang yang sedang dipilih.</p>
            </article>

            <article class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><h2 class="flex items-center gap-2 text-base font-semibold text-slate-800">Ringkasan Pembayaran</h2><p class="mt-1 text-xs text-slate-500">Pembayaran berhasil yang diterima pada periode terpilih.</p></div>
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="GET" action="{{ route('owner.dashboard') }}" class="flex items-center gap-2">
                            @if ($selectedBranchId !== null)<input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">@endif
                            <input type="hidden" name="rent_month" value="{{ $rentMonth }}"><input type="hidden" name="rent_year" value="{{ $rentYear }}"><input type="hidden" name="year" value="{{ $year }}">
                            <select name="payment_month" aria-label="Bulan ringkasan pembayaran" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs" onchange="this.form.submit()"><option value="" @selected($paymentMonth === null)>Semua Bulan</option>@foreach($monthOptions as $number => $label)<option value="{{ $number }}" @selected($paymentMonth === $number)>{{ $label }}</option>@endforeach</select>
                            <select name="payment_year" aria-label="Tahun ringkasan pembayaran" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs" onchange="this.form.submit()"><option value="" @selected($paymentYear === null)>Semua Tahun</option>@foreach($yearOptions as $optionYear)<option value="{{ $optionYear }}" @selected($paymentYear === $optionYear)>{{ $optionYear }}</option>@endforeach</select>
                        </form>
                        <a href="{{ route('owner.payments', array_filter(['branch_id' => $selectedBranchId, 'month' => $paymentMonth, 'year' => $paymentYear])) }}" class="inline-flex items-center gap-1 rounded-lg bg-[#1D4E80] px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Data Pembayaran <i data-feather="arrow-up-right" class="h-3 w-3"></i></a>
                    </div>
                </div>
                <div class="mt-5 rounded-lg bg-[#F0F3FF] p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Pendapatan Diterima pada Periode Ini
                    </p>
                    <p class="mt-1 text-2xl font-extrabold tracking-tight text-[#1D4E80] sm:text-3xl">
                        {{ $money($totalPaidAmount) }}</p>
                    <div class="mt-2 flex items-center justify-between gap-3 text-xs text-slate-500">
                        <span>Pembayaran berhasil diterima</span>
                        <strong class="text-slate-700">{{ number_format($paidTransactions) }} transaksi</strong>
                    </div>
                </div>
                <div class="mt-3 space-y-2">
                    @forelse ($paymentMethodTotals as $method)
                        <div class="flex items-center gap-3 rounded-lg bg-[#F0F3FF] px-3 py-3 text-xs">
                            <i data-feather="credit-card" class="h-4 w-4 shrink-0 text-slate-500"></i>
                            <span
                                class="flex-1 text-slate-600">{{ $method->payment_method ?: 'Metode tidak dicatat' }}</span>
                            <div class="h-1.5 w-14 overflow-hidden rounded-full bg-blue-100">
                                <div class="h-full bg-[#1D4E80]"
                                    style="width: {{ min(100, round(($method->transaction_count / $maxMethodCount) * 100)) }}%">
                                </div>
                            </div>
                            <strong
                                class="w-10 text-right text-slate-700">{{ number_format($method->transaction_count) }}</strong>
                        </div>
                    @empty
                        <p class="rounded-lg bg-[#F0F3FF] px-3 py-4 text-xs text-slate-500">Belum ada pembayaran
                            terverifikasi.</p>
                    @endforelse
                </div>
                <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-center text-xs text-slate-500">Data pembayaran ditampilkan untuk pemantauan.</p>
            </article>
        </section>

        <section class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Ringkasan Booking</h2>
                    <p class="mt-1 text-xs text-slate-500">Status seluruh booking pada cabang yang dipilih.</p>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg bg-amber-50 p-3"><p class="text-[10px] font-semibold uppercase tracking-wide text-amber-700">Menunggu Pembayaran</p><p class="mt-1 text-2xl font-bold text-amber-800">{{ number_format($bookingSummary['pending_payment']) }}</p></div>
                <div class="rounded-lg bg-emerald-50 p-3"><p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Dikonfirmasi</p><p class="mt-1 text-2xl font-bold text-emerald-800">{{ number_format($bookingSummary['confirmed']) }}</p></div>
                <div class="rounded-lg bg-slate-100 p-3"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-600">Kedaluwarsa</p><p class="mt-1 text-2xl font-bold text-slate-700">{{ number_format($bookingSummary['expired']) }}</p></div>
                <div class="rounded-lg bg-rose-50 p-3"><p class="text-[10px] font-semibold uppercase tracking-wide text-rose-700">Dibatalkan</p><p class="mt-1 text-2xl font-bold text-rose-800">{{ number_format($bookingSummary['cancelled']) }}</p></div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="flex items-center gap-2 text-base font-semibold text-slate-800">Pendapatan Bulanan per Cabang
                    </h2>
                </div>
                <form method="GET" action="{{ route('owner.dashboard') }}" class="flex items-center gap-2">
                    @if ($selectedBranchId !== null)
                        <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">
                    @endif
                    <label for="dashboard-year" class="text-xs font-medium text-slate-500">Tahun</label>
                    <select id="dashboard-year" name="year" onchange="this.form.submit()" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 focus:border-blue-500 focus:outline-none"><option value="" @selected($year === null)>Semua Tahun</option>
                        @foreach ($yearOptions as $optionYear)
                            <option value="{{ $optionYear }}" @selected((int) $year === (int) $optionYear)>{{ $optionYear }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="mt-4 rounded-lg bg-[#F0F3FF] p-3 sm:p-5">
                <div class="h-[280px] sm:h-[320px]">
                    <canvas id="monthlyRevenueChart"
                        aria-label="Grafik pendapatan bulanan per cabang untuk tahun {{ $year }}"></canvas>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <article class="overflow-hidden rounded-xl border border-slate-100 bg-white">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Sewa Segera Berakhir</h2>
                    <p class="mt-1 text-xs text-slate-500">Penyewa dengan masa sewa berakhir dalam tujuh hari ke depan.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($expiringRents as $rent)
                        @php $name = trim(($rent->fname ?? '') . ' ' . ($rent->lname ?? '')) ?: 'Penyewa'; @endphp
                        <div class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $name }}</p>
                                <p class="mt-1 text-[11px] text-slate-500">{{ $rent->room_code }} · {{ $rent->branch_name }} · {{ $rent->rent_code }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xs font-semibold text-rose-700">{{ \Carbon\Carbon::parse($rent->end_date)->format('d M Y') }}</p>
                                <p class="mt-1 text-[10px] text-slate-500">{{ $rent->days_remaining === 0 ? 'Berakhir hari ini' : $rent->days_remaining . ' hari lagi' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-xs text-slate-500">Tidak ada sewa yang berakhir dalam tujuh hari ke depan.</p>
                    @endforelse
                </div>
            </article>

            <article class="overflow-hidden rounded-xl border border-slate-100 bg-white">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Aktivitas Terbaru</h2>
                    <p class="mt-1 text-xs text-slate-500">Aktivitas booking, sewa, dan pembayaran.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recentActivities as $activity)
                        @php
                            $activityName = trim($activity->tenant_name ?? '') ?: 'Penyewa';
                            $activityLabel = ['booking' => 'Booking', 'rent' => 'Sewa', 'payment' => 'Pembayaran'][$activity->activity_type] ?? 'Aktivitas';
                            $statusLabel = [
                                'pending_payment' => 'Menunggu pembayaran', 'confirmed' => 'Dikonfirmasi',
                                'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'scheduled' => 'Terjadwal',
                                'active' => 'Aktif', 'completed' => 'Selesai', 'paid' => 'Berhasil',
                                'pending' => 'Menunggu', 'failed' => 'Gagal',
                            ][$activity->activity_status] ?? ucfirst($activity->activity_status);
                        @endphp
                        <div class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $activityLabel }} · {{ $activityName }}</p>
                                <p class="mt-1 truncate text-[11px] text-slate-500">{{ $activity->reference_code ?: '-' }} · {{ $activity->room_code }} · {{ $activity->branch_name }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-xs font-semibold text-slate-700">{{ $statusLabel }}</p>
                                <p class="mt-1 text-[10px] text-slate-500">{{ \Carbon\Carbon::parse($activity->activity_at)->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-xs text-slate-500">Belum ada aktivitas untuk ditampilkan.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <div class="flex flex-col gap-3 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Pembayaran Terbaru</h2>
                    <p class="mt-1 text-xs text-slate-500">Lima transaksi terbaru yang berstatus paid.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <a href="{{ route('owner.payments', array_filter(['branch_id' => $selectedBranchId, 'month' => $paymentMonth, 'year' => $paymentYear])) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-[#1D4E80] hover:bg-slate-50">Lihat semua pembayaran</a>
                    <input id="dashboard-search" type="search" placeholder="Cari pembayaran..." class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none sm:w-48">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table id="recent-payments-table" class="min-w-[850px] w-full border-collapse text-left">
                    <thead class="bg-[#F0F3FF] text-[10px] uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-4 font-bold">Tanggal Pembayaran</th>
                            <th class="px-5 py-4 font-bold">Kode Pembayaran</th>
                            <th class="px-5 py-4 font-bold">Kode Booking</th>
                            <th class="px-5 py-4 font-bold">Penyewa</th>
                            <th class="px-5 py-4 font-bold">Metode Pembayaran</th>
                            <th class="px-5 py-4 text-right font-bold">Total Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                        @forelse ($recentPayments as $payment)
                            @php
                                $tenantName =
                                    trim(($payment->fname ?? '') . ' ' . ($payment->lname ?? '')) ?: 'Penyewa';
                                $paidDate = $payment->paid_at
                                    ? \Carbon\Carbon::parse($payment->paid_at)->format('d M Y')
                                    : '-';
                            @endphp
                            <tr class="payment-row hover:bg-slate-50"
                                data-search="{{ strtolower(($payment->payment_code ?? '') . ' ' . ($payment->booking_code ?? '') . ' ' . $tenantName . ' ' . ($payment->payment_method ?? '')) }}">
                                <td class="whitespace-nowrap px-5 py-4">{{ $paidDate }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-[#315F96]"><a href="{{ route('owner.payments', ['q' => $payment->payment_code, 'month' => \Carbon\Carbon::parse($payment->paid_at)->month, 'year' => \Carbon\Carbon::parse($payment->paid_at)->year, 'branch_id' => $selectedBranchId]) }}" class="hover:underline">{{ $payment->payment_code }}</a></td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $payment->booking_code }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-800">{{ $tenantName }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $payment->payment_method ?: '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-800">
                                    {{ $money($payment->amount) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada
                                    pembayaran terverifikasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <p id="payments-empty" class="hidden px-5 py-8 text-center text-sm text-slate-500">Tidak ada transaksi
                    yang cocok dengan pencarian.</p>
            </div>
        </section>

        <p class="text-center text-[11px] text-slate-400">&copy; 2026 nesaKost. All rights reserved.
        </p>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.feather) feather.replace();

            const branchOccupancy = @json($branchOccupancy);
            const branchColors = @json($branchColors);
            const monthLabels = @json($monthLabels);
            const revenueDatasets = @json($monthlyRevenueDatasets);

            if (typeof Chart !== 'undefined') {
                branchOccupancy.forEach(function(branch, index) {
                    const canvas = document.getElementById('occupancy-chart-' + branch.id);
                    if (!canvas) return;

                    new Chart(canvas, {
                        type: 'pie',
                        data: {
                            labels: ['Kamar Terisi', 'Kamar Kosong'],
                            datasets: [{
                                data: [branch.occupied, branch.vacant],
                                backgroundColor: ['#2450D8', '#12A5E5'],
                                hoverOffset: 5,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.label + ': ' + context.raw +
                                                ' kamar';
                                        }
                                    }
                                }
                            }
                        }
                    });
                });

                const revenueCanvas = document.getElementById('monthlyRevenueChart');
                if (revenueCanvas) {
                    const chartDatasets = revenueDatasets.map(function(dataset, index) {
                        return {
                            label: dataset.label,
                            data: dataset.data,
                            backgroundColor: branchColors[index % branchColors.length],
                            borderRadius: 4,
                            maxBarThickness: 18
                        };
                    });

                    new Chart(revenueCanvas, {
                        type: 'bar',
                        data: {
                            labels: monthLabels,
                            datasets: chartDatasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 7,
                                        font: {
                                            size: 11
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + new Intl.NumberFormat(
                                                'id-ID', {
                                                    style: 'currency',
                                                    currency: 'IDR',
                                                    maximumFractionDigits: 0
                                                }).format(context.raw);
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            size: 10
                                        }
                                    },
                                    border: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: '#64748b',
                                        font: {
                                            size: 10
                                        },
                                        callback: function(value) {
                                            return new Intl.NumberFormat('id-ID', {
                                                notation: 'compact',
                                                style: 'currency',
                                                currency: 'IDR',
                                                maximumFractionDigits: 1
                                            }).format(value);
                                        }
                                    },
                                    grid: {
                                        color: '#dfe6f5'
                                    },
                                    border: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Pencarian pembayaran terbaru.
            const searchInput = document.getElementById('dashboard-search');
            const paymentRows = document.querySelectorAll('.payment-row');
            const emptyMessage = document.getElementById('payments-empty');

            searchInput?.addEventListener('input', function() {
                const query = searchInput.value.trim().toLowerCase();
                let visibleRows = 0;
                paymentRows.forEach(function(row) {
                    const text = (row.dataset.search || row.textContent).toLowerCase();
                    const matches = text.includes(query);
                    row.classList.toggle('hidden', !matches);
                    if (matches) visibleRows++;
                });
                emptyMessage?.classList.toggle('hidden', visibleRows !== 0);
            });
        });
    </script>
@endpush
