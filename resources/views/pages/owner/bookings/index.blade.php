@extends('components.layouts.owner.container')

@section('title', 'Data Booking')

@section('main')
    @php
        $labels = [
            'pending_payment' => 'Menunggu Pembayaran',
            'confirmed' => 'Dikonfirmasi',
            'expired' => 'Kedaluwarsa',
            'cancelled' => 'Dibatalkan',
        ];
        $classes = [
            'pending_payment' => 'bg-amber-50 text-amber-700',
            'confirmed' => 'bg-emerald-50 text-emerald-700',
            'expired' => 'bg-slate-100 text-slate-700',
            'cancelled' => 'bg-rose-50 text-rose-700',
        ];
        $typeLabels = ['new' => 'Booking Baru', 'renewal' => 'Perpanjangan'];
        $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    @endphp
    <div class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold">Data Booking</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">Pantau pemesanan kamar dan pengajuan perpanjangan dari seluruh
                cabang yang kamu kelola. </p>
        </section>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-5">
            @foreach ([['Total Booking', 'total', 'bg-indigo-50 text-indigo-800'], ['Menunggu Pembayaran', 'pending_payment', 'bg-amber-50 text-amber-800'], ['Dikonfirmasi', 'confirmed', 'bg-emerald-50 text-emerald-800'], ['Kedaluwarsa', 'expired', 'bg-slate-100 text-slate-700'], ['Dibatalkan', 'cancelled', 'bg-rose-50 text-rose-800']] as [$label, $key, $color])
                <article class="rounded-xl border border-slate-100 bg-white p-4">
                    <p class="text-xs text-slate-500">{{ $label }}</p>
                    <p class="mt-2 inline-flex rounded-md px-2 py-1 text-2xl font-bold {{ $color }}">
                        {{ number_format($summary[$key]) }}</p>
                </article>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <form method="GET" action="{{ route('owner.bookings.index') }}"
                class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-6">
                <select name="branch_id" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="">Semua Cabang</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="all">Semua Status</option>
                    @foreach ($labels as $key => $label)
                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="type" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="all">Semua Jenis</option>
                    @foreach ($typeLabels as $key => $label)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex flex-col gap-1.5">
                    <label for="booking-from" class="text-xs font-medium text-slate-600">Booking dibuat sejak</label>
                    <input id="booking-from" type="date" name="from" value="{{ $from }}" aria-label="Booking dibuat sejak tanggal"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="booking-to" class="text-xs font-medium text-slate-600">Booking dibuat sampai</label>
                    <input id="booking-to" type="date" name="to" value="{{ $to }}" aria-label="Booking dibuat sampai tanggal"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div class="flex gap-2">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Kode, penyewa, kamar..."
                        class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <button class="rounded-lg bg-[#1D4E80] px-4 py-2.5 text-sm font-semibold text-white">Cari</button>
                </div>
                <div class="sm:col-span-2 lg:col-span-6 flex flex-col gap-2">
                    <p class="text-xs text-slate-500">Filter tanggal berdasarkan kapan booking dibuat (bukan tanggal mulai atau berakhirnya masa sewa). Isi tanggal awal dan/atau akhir untuk membatasi rentang data.</p>
                    <a href="{{ route('owner.bookings.index') }}" class="text-sm text-slate-500 hover:text-slate-800">Reset filter</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Booking</th>
                            <th class="px-5 py-3">Penyewa</th>
                            <th class="px-5 py-3">Cabang / Kamar</th>
                            <th class="px-5 py-3">Periode</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Pembayaran</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($bookings as $booking)
                            @php
                                $tenantName =
                                    trim(
                                        ($booking->tenant?->user?->fname ?? '') .
                                            ' ' .
                                            ($booking->tenant?->user?->lname ?? ''),
                                    ) ?:
                                    'Penyewa';
                                $paymentStatus = $booking->payments->contains(
                                    fn($payment) => $payment->status === 'paid',
                                )
                                    ? 'Berhasil'
                                    : $booking->payments->last()?->status ?? '-';
                                $total = (float) $booking->price_snapshot * (int) $booking->duration_month;
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-800">{{ $booking->booking_code }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $typeLabels[$booking->booking_type] ?? ucfirst($booking->booking_type ?? 'new') }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $booking->created_at?->format('d/m/Y H:i') }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">{{ $tenantName }}</td>
                                <td class="px-5 py-4">
                                    <p>{{ $booking->room?->branchDorm?->name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $booking->room?->room_code ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p>{{ $booking->start_date?->format('d/m/Y') }}</p>
                                    <p class="text-xs text-slate-500">s.d. {{ $booking->end_date?->format('d/m/Y') }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">{{ $money($total) }}</td>
                                <td class="px-5 py-4"><span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $classes[$booking->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $labels[$booking->status] ?? ucfirst($booking->status) }}</span>
                                </td>
                                <td class="px-5 py-4 capitalize">{{ $paymentStatus }}</td>
                                <td class="px-5 py-4"><a href="{{ route('owner.bookings.show', $booking->booking_code) }}"
                                        class="font-semibold text-[#315F96] hover:underline">Detail</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center text-sm text-slate-500">Belum ada booking
                                    untuk filter yang dipilih.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div
                class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>Menampilkan {{ $bookings->firstItem() ?? 0 }}–{{ $bookings->lastItem() ?? 0 }} dari
                    {{ $bookings->total() }} booking</p>{{ $bookings->links() }}
            </div>
        </section>
    </div>
@endsection
