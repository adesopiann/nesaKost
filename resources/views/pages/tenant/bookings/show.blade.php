@extends('components.layouts.guest.container')

@section('title', 'Detail Booking - nesaKost')

@section('content')
    <section class="min-h-screen bg-slate-50 py-12">
        <div class="mx-auto max-w-3xl px-6">

            <div class="rounded-2xl border border-slate-200 bg-white p-6 md:p-8">

                <h1 class="text-2xl font-semibold text-slate-900">
                    Detail Booking
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    Informasi booking dan status pembayaran kamu.
                </p>

                @if (session('success'))
                    <div class="mt-5 rounded-lg bg-green-50 p-4 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @php
                    $latestPayment = $booking->payments->sortByDesc('id')->first();

                    $bookingStatus =
                        [
                            'pending_payment' => 'Menunggu Pembayaran',
                            'confirmed' => 'Dikonfirmasi',
                            'expired' => 'Kedaluwarsa',
                            'cancelled' => 'Dibatalkan',
                            'cancelled' => 'Dibatalkan',
                        ][$booking->status] ?? $booking->status;

                    $paymentStatus =
                        [
                            'pending' => 'Menunggu Pembayaran',
                            'paid' => 'Berhasil',
                            'failed' => 'Gagal',
                            'expired' => 'Kedaluwarsa',
                            'cancelled' => 'Dibatalkan',
                        ][$latestPayment?->status] ?? 'Belum ada pembayaran';
                @endphp

                {{-- STATUS --}}
                <div class="mt-8 rounded-xl bg-slate-50 p-5">
                    <p class="text-xs text-slate-500">
                        Status Booking
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        {{ $bookingStatus }}
                    </p>

                    <p class="mt-4 text-xs text-slate-500">
                        Status Pembayaran
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        {{ $paymentStatus }}
                    </p>
                </div>

                {{-- DETAIL BOOKING --}}
                <div class="mt-8 space-y-4">

                    <div class="flex justify-between gap-4">
                        <span class="text-sm text-slate-500">Kode Booking</span>
                        <span class="text-sm font-semibold text-slate-800">
                            {{ $booking->booking_code }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-sm text-slate-500">Jenis Booking</span>
                        <span class="text-sm font-semibold text-slate-800">
                            {{ $booking->booking_type === 'renewal' ? 'Perpanjangan Sewa' : 'Booking Baru' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-sm text-slate-500">Kode Kamar</span>
                        <span class="text-sm font-semibold text-slate-800">
                            {{ $booking->room->room_code }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-sm text-slate-500">Tanggal Mulai</span>
                        <span class="text-sm font-semibold text-slate-800">
                            {{ $booking->start_date->format('d M Y') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-sm text-slate-500">Tanggal Selesai</span>
                        <span class="text-sm font-semibold text-slate-800">
                            {{ $booking->end_date->format('d M Y') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-sm text-slate-500">Durasi</span>
                        <span class="text-sm font-semibold text-slate-800">
                            {{ $booking->duration_month }} bulan
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-sm text-slate-500">Harga per Bulan</span>
                        <span class="text-sm font-semibold text-slate-800">
                            Rp{{ number_format((float) $booking->price_snapshot, 0, ',', '.') }}
                        </span>
                    </div>

                    <hr>

                    <div class="flex justify-between gap-4">
                        <span class="font-semibold text-slate-800">
                            Total Pembayaran
                        </span>
                        <span class="text-lg font-bold text-blue-900">
                            Rp{{ number_format((float) ($latestPayment?->amount ?? 0), 0, ',', '.') }}
                        </span>
                    </div>

                </div>

                {{-- AKSI BOOKING --}}
                @if ($booking->status === 'pending_payment')
                    @if ($booking->booking_type === 'renewal')
                        <div class="mt-6 rounded-lg border border-blue-100 bg-blue-50 p-4">
                            <p class="text-sm font-semibold text-blue-950">Batas pembayaran perpanjangan</p>
                            <p class="mt-1 text-sm text-blue-900">Selesaikan pembayaran paling lambat {{ $booking->expires_at?->format('d M Y, H:i') ?? '-' }} WIB. Kamar ditahan selama periode ini.</p>
                        </div>
                    @endif
                    <div class="mt-8 space-y-3">
                        <a href="{{ route('tenant.bookings.payments.create', $booking->booking_code) }}"
                            class="block w-full rounded-lg bg-blue-900 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-blue-950">
                            {{ $latestPayment && $latestPayment->status === 'pending' && $latestPayment->payment_method
                                ? 'Lihat / Ubah Metode Pembayaran'
                                : 'Pilih Metode Pembayaran' }}
                        </a>
                    </div>

                    {{-- STATUS PEMBATALAN BOOKING --}}
                @elseif ($booking->status === 'cancelled')
                    <div class="mt-8 rounded-xl border border-red-200 bg-red-50 p-5">
                        <h2 class="font-semibold text-red-900">
                            Booking Dibatalkan
                        </h2>

                        <p class="mt-2 text-sm text-red-800">
                            Booking ini telah dibatalkan dan kamar sudah dilepaskan.
                        </p>

                        @if ($booking->cancelled_at)
                            <p class="mt-2 text-xs text-red-700">
                                Dibatalkan pada {{ $booking->cancelled_at->format('d M Y H:i') }}
                            </p>
                        @endif

                        @if ($booking->cancellation_reason)
                            <p class="mt-3 text-sm text-red-800">
                                <strong>Alasan:</strong> {{ $booking->cancellation_reason }}
                            </p>
                        @endif
                    </div>
                @endif


                {{-- RENT YANG TERBENTUK --}}
                @if ($booking->status === 'confirmed' && $booking->rent)
                    <div class="mt-8 rounded-xl border border-green-200 bg-green-50 p-5">
                        <h2 class="font-semibold text-green-800">
                            Pembayaran Berhasil
                        </h2>

                        <p class="mt-2 text-sm text-green-700">
                            Masa sewa berhasil dibuat.
                        </p>

                        <div class="mt-4 space-y-2 text-sm text-green-800">
                            <p>
                                Kode Rent:
                                <strong>{{ $booking->rent->rent_code }}</strong>
                            </p>

                            <p>
                                Status Rent:
                                <strong>{{ ucfirst($booking->rent->status) }}</strong>
                            </p>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </section>
@endsection
