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
                        ][$booking->status] ?? $booking->status;

                    $paymentStatus =
                        [
                            'pending' => 'Menunggu Pembayaran',
                            'paid' => 'Berhasil',
                            'failed' => 'Gagal',
                            'expired' => 'Kedaluwarsa',
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

                {{-- SIMULASI PEMBAYARAN --}}
                @if ($booking->status === 'pending_payment' && $latestPayment?->status === 'pending')
                    <div class="mt-8 rounded-xl border border-amber-200 bg-amber-50 p-5">

                        <h2 class="font-semibold text-amber-900">
                            Selesaikan Pembayaran
                        </h2>

                        <p class="mt-2 text-sm text-amber-800">
                            Batas pembayaran:
                            {{ $booking->expires_at?->format('d M Y H:i') ?? '-' }}
                        </p>

                        <p class="mt-3 text-xs leading-relaxed text-amber-700">
                            Ini merupakan simulasi pembayaran untuk keperluan pengembangan aplikasi.
                            Tidak ada transaksi uang sungguhan.
                        </p>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">

                            {{-- Simulasi berhasil --}}
                            <form
                                action="{{ route('tenant.bookings.payments.simulate', [
                                    'bookingId' => $booking->id,
                                    'paymentId' => $latestPayment->id,
                                ]) }}"
                                method="POST">
                                @csrf

                                <input type="hidden" name="outcome" value="success">

                                <button type="submit"
                                    class="w-full rounded-lg bg-green-700 px-4 py-3 text-sm font-semibold text-white hover:bg-green-800">
                                    Simulasikan Berhasil
                                </button>
                            </form>

                            {{-- Simulasi gagal --}}
                            <form
                                action="{{ route('tenant.bookings.payments.simulate', [
                                    'bookingId' => $booking->id,
                                    'paymentId' => $latestPayment->id,
                                ]) }}"
                                method="POST">
                                @csrf

                                <input type="hidden" name="outcome" value="failed">

                                <button type="submit"
                                    class="w-full rounded-lg border border-red-300 bg-white px-4 py-3 text-sm font-semibold text-red-700 hover:bg-red-50">
                                    Simulasikan Gagal
                                </button>
                            </form>

                        </div>
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

                <div class="mt-8">
                    <a href="{{ route('guest.home') }}"
                        class="block rounded-lg border border-slate-300 px-4 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Kembali ke Beranda
                    </a>
                </div>

            </div>
        </div>
    </section>
@endsection
