@extends('components.layouts.owner.container')

@section('title', 'Detail Booking')

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
        $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $tenantName =
            trim(($booking->tenant?->user?->fname ?? '') . ' ' . ($booking->tenant?->user?->lname ?? '')) ?: 'Penyewa';
    @endphp
    <div class="space-y-6">
        <section
            class="flex flex-col gap-3 rounded-xl bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div><a href="{{ route('owner.bookings.index') }}"
                    class="text-sm font-medium text-[#315F96] hover:underline">&larr; Kembali ke Data Booking</a>
                <h1 class="mt-3 text-2xl font-bold">{{ $booking->booking_code }}</h1>
                <p class="mt-1 text-sm text-slate-500">Detail pemesanan dan riwayat pembayaran.</p>
            </div>
            <span
                class="inline-flex w-fit rounded-full px-3 py-1.5 text-sm font-semibold {{ $classes[$booking->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $labels[$booking->status] ?? ucfirst($booking->status) }}</span>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
                <h2 class="text-lg font-semibold">Informasi Booking</h2>
                <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-slate-500">Jenis</dt>
                        <dd class="mt-1 font-medium">
                            {{ $booking->booking_type === 'renewal' ? 'Perpanjangan' : 'Booking Baru' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Durasi</dt>
                        <dd class="mt-1 font-medium">{{ $booking->duration_month }} bulan</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Tanggal Mulai</dt>
                        <dd class="mt-1 font-medium">{{ $booking->start_date?->format('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Tanggal Selesai</dt>
                        <dd class="mt-1 font-medium">{{ $booking->end_date?->format('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Harga per Bulan (snapshot)</dt>
                        <dd class="mt-1 font-medium">{{ $money($booking->price_snapshot) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Total Booking</dt>
                        <dd class="mt-1 font-medium">
                            {{ $money((float) $booking->price_snapshot * (int) $booking->duration_month) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Dibuat</dt>
                        <dd class="mt-1 font-medium">{{ $booking->created_at?->format('d F Y, H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Batas Pembayaran</dt>
                        <dd class="mt-1 font-medium">{{ $booking->expires_at?->format('d F Y, H:i') ?? '-' }}</dd>
                    </div>
                </dl>
                @if ($booking->cancellation_reason)
                    <div class="mt-5 rounded-lg bg-rose-50 p-4">
                        <p class="text-sm font-semibold text-rose-800">Alasan Pembatalan</p>
                        <p class="mt-1 text-sm text-rose-700">{{ $booking->cancellation_reason }}</p>
                        <p class="mt-2 text-xs text-rose-600">{{ $booking->cancelled_at?->format('d F Y, H:i') }}</p>
                    </div>
                @endif
            </section>

            <section class="rounded-xl border border-slate-100 bg-white p-5 sm:p-6">
                <h2 class="text-lg font-semibold">Penyewa & Kamar</h2>
                <dl class="mt-5 space-y-4">
                    <div>
                        <dt class="text-xs text-slate-500">Nama Penyewa</dt>
                        <dd class="mt-1 font-medium">{{ $tenantName }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Email</dt>
                        <dd class="mt-1 font-medium">{{ $booking->tenant?->user?->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Cabang</dt>
                        <dd class="mt-1 font-medium">{{ $booking->room?->branchDorm?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Kode Kamar</dt>
                        <dd class="mt-1 font-medium">{{ $booking->room?->room_code ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Jenis Kamar</dt>
                        <dd class="mt-1 font-medium">{{ $booking->room?->type ?? '-' }}</dd>
                    </div>
                </dl>
                @if ($booking->booking_type === 'renewal' && $booking->previousRent)
                    <div class="mt-5 rounded-lg bg-sky-50 p-4">
                        <p class="text-sm font-semibold text-sky-800">Pengajuan Perpanjangan</p>
                        <p class="mt-1 text-sm text-sky-700">Berasal dari sewa {{ $booking->previousRent->rent_code }}.</p>
                    </div>
                @endif
                @if ($booking->rent)
                    <div class="mt-5 rounded-lg bg-emerald-50 p-4">
                        <p class="text-sm font-semibold text-emerald-800">Rent Terbentuk</p>
                        <p class="mt-1 text-sm text-emerald-700">{{ $booking->rent->rent_code }} ·
                            {{ ucfirst($booking->rent->status) }}</p>
                    </div>
                @endif
            </section>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <div class="border-b border-slate-100 p-5 sm:p-6">
                <h2 class="text-lg font-semibold">Riwayat Pembayaran</h2>
                <p class="mt-1 text-sm text-slate-500">Percobaan pembayaran yang terkait dengan booking ini.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Kode Pembayaran</th>
                            <th class="px-5 py-3">Jumlah</th>
                            <th class="px-5 py-3">Metode</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Dibayar</th>
                            <th class="px-5 py-3">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($booking->payments->sortByDesc('created_at') as $payment)
                            <tr>
                                <td class="px-5 py-4 font-medium">{{ $payment->payment_code ?? '-' }}</td>
                                <td class="px-5 py-4 whitespace-nowrap">{{ $money($payment->amount) }}</td>
                                <td class="px-5 py-4 capitalize">{{ $payment->payment_method ?? '-' }}</td>
                                <td class="px-5 py-4"><span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $payment->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($payment->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700') }}">{{ ucfirst($payment->status) }}</span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    {{ $payment->paid_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    {{ $payment->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada catatan
                                    pembayaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
