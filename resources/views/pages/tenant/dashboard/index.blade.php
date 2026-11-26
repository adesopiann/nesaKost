
@extends('components.layouts.tenant.container')

@section('title', 'Dashboard Tenant')

@section('content')

<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-10">

        {{-- HEADER --}}
        <div class="mb-8">
            <p class="mb-2 text-sm font-medium text-blue-900">
                Dashboard Penyewa
            </p>

            <h1 class="text-2xl font-bold text-slate-900 md:text-3xl">
                Selamat datang,
                {{ $user->fname }}!
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Pantau informasi kamar dan aktivitas penyewaanmu di sini.
            </p>
        </div>


        {{-- SUMMARY CARDS --}}
        <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

            {{-- KAMAR SAYA --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-900">
                        <i data-feather="home" class="h-5 w-5"></i>
                    </div>
                    <span class="text-xs text-slate-400">
                        Penyewaan aktif
                    </span>
                </div>

                <p class="text-sm text-slate-500">
                    Kamar Saya
                </p>

                <h3 class="mt-1 text-2xl font-bold text-slate-900">
                    {{ $activeRent ? '1 Kamar' : 'Belum ada' }}
                </h3>
            </div>


            {{-- TOTAL PEMBAYARAN --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-50 text-green-700">
                        <i data-feather="wallet" class="h-5 w-5"></i>
                    </div>
                    <span class="text-xs text-slate-400">
                        Pembayaran berhasil
                    </span>
                </div>

                <p class="text-sm text-slate-500">
                    Total Pembayaran
                </p>

                <h3 class="mt-1 text-xl font-bold text-slate-900">
                    Rp{{ number_format((float) $paidTotal, 0, ',', '.') }}
                </h3>
            </div>


            {{-- SISA MASA SEWA --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                        <i data-feather="calendar" class="h-5 w-5"></i>
                    </div>
                    <span class="text-xs text-slate-400">
                        Masa penyewaan
                    </span>
                </div>

                <p class="text-sm text-slate-500">
                    Sisa Masa Sewa
                </p>

                <h3 class="mt-1 text-2xl font-bold text-slate-900">
                    @if($activeRent)
                        {{ max(0, now()->startOfDay()->diffInDays($activeRent->end_date, false)) }}
                        <span class="text-sm font-medium text-slate-500">hari</span>
                    @elseif($scheduledRent)
                        Terjadwal
                    @else
                        -
                    @endif
                </h3>
            </div>

        </div>


        {{-- INFORMASI PENYEWAAN --}}
        <div class="mb-8 rounded-xl border border-slate-200 bg-white">

            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="font-semibold text-slate-900">
                        Informasi Penyewaan
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Informasi kamar dan periode penyewaanmu.
                    </p>
                </div>

                <a href="{{ url('/tenant/informasi-sewa') }}"
                   class="text-sm font-medium text-blue-900 hover:underline">
                    Lihat detail
                </a>
            </div>

            <div class="p-6">

                @if($currentRent)

                    <div class="flex flex-col gap-6 md:flex-row">

                        {{-- GAMBAR KAMAR --}}
                        <div class="h-48 w-full overflow-hidden rounded-lg bg-slate-100 md:w-64">
                            @if($currentRent->room?->image_path)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($currentRent->room->image_path) }}"
                                    alt="Foto kamar"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <div class="flex h-full items-center justify-center text-sm text-slate-400">
                                    Foto kamar belum tersedia
                                </div>
                            @endif
                        </div>

                        {{-- DETAIL KAMAR --}}
                        <div class="flex-1">

                            <div class="mb-3 flex flex-wrap items-center gap-3">
                                <h3 class="text-lg font-bold text-slate-900">
                                    {{ $currentRent->room?->branchDorm?->name ?? 'Nama kost tidak tersedia' }}
                                </h3>

                                @if($currentRent->status === 'active')
                                    <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                        Terjadwal
                                    </span>
                                @endif
                            </div>

                            <p class="mb-5 text-sm text-slate-500">
                                Kamar {{ $currentRent->room?->room_code ?? '-' }}
                            </p>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                                <div>
                                    <p class="text-xs text-slate-400">
                                        Tanggal Mulai
                                    </p>
                                    <p class="mt-1 text-sm font-medium text-slate-800">
                                        {{ $currentRent->start_date->format('d M Y') }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-400">
                                        Tanggal Berakhir
                                    </p>
                                    <p class="mt-1 text-sm font-medium text-slate-800">
                                        {{ $currentRent->end_date->format('d M Y') }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-400">
                                        Total Harga Sewa
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-blue-900">
                                        Rp{{ number_format((float) $currentRent->total_price, 0, ',', '.') }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-400">
                                        Kode Penyewaan
                                    </p>
                                    <p class="mt-1 text-sm font-medium text-slate-800">
                                        {{ $currentRent->rent_code ?? '-' }}
                                    </p>
                                </div>

                            </div>

                            @php
                                $renewalRent = $renewalBooking?->rent;
                                $renewalPayment = $renewalBooking?->payments
                                    ?->where('status', 'paid')
                                    ->sortByDesc('paid_at')
                                    ->first();
                                $renewalPaid = $renewalBooking
                                    && $renewalBooking->status === 'confirmed'
                                    && $renewalPayment
                                    && $renewalRent;
                            @endphp

                            @if($renewalPaid)
                                <div class="mt-6 rounded-lg border border-blue-200 p-5">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-green-950">
                                            Perpanjangan berhasil!
                                        </p>
                                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                            Berhasil
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm">
                                        Pembayaran perpanjangan telah berhasil dan masa sewa baru kamu sudah tercatat.
                                    </p>

                                    <div class="mt-4 border-t border-blue-200 pt-4">
                                        <p class="mb-3 text-sm font-semibold text-slate-900">
                                            Informasi Penyewaan Baru
                                        </p>

                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <p class="text-xs text-slate-500">Kode Rent Baru</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-900">
                                                    {{ $renewalRent->rent_code ?? '-' }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-slate-500">Status Rent</p>
                                                @if($renewalRent->status === 'active')
                                                    <span class="mt-1 inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                        Aktif
                                                    </span>
                                                @else
                                                    <span class="mt-1 inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                        Terjadwal
                                                    </span>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-xs text-slate-500">Tanggal Mulai</p>
                                                <p class="mt-1 text-sm font-medium text-slate-800">
                                                    {{ $renewalRent->start_date->format('d M Y') }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-slate-500">Tanggal Berakhir</p>
                                                <p class="mt-1 text-sm font-medium text-slate-800">
                                                    {{ $renewalRent->end_date->format('d M Y') }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-slate-500">Durasi Perpanjangan</p>
                                                <p class="mt-1 text-sm font-medium text-slate-800">
                                                    {{ $renewalBooking->duration_month }} bulan
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-slate-500">Total Pembayaran</p>
                                                <p class="mt-1 text-sm font-semibold text-blue-900">
                                                    Rp{{ number_format((float) $renewalPayment->amount, 0, ',', '.') }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-slate-500">Kode Booking</p>
                                                <p class="mt-1 text-sm font-medium text-slate-800">
                                                    {{ $renewalBooking->booking_code ?? '-' }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-slate-500">Tanggal Pembayaran</p>
                                                <p class="mt-1 text-sm font-medium text-slate-800">
                                                    {{ $renewalPayment->paid_at?->format('d M Y, H:i') ?? '-' }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-blue-200 pt-3">
                                            <span class="text-sm text-blue-900">Status Pembayaran</span>
                                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                Lunas
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @elseif(
                                $renewalBooking
                                && $renewalBooking->status === 'pending_payment'
                                && !$renewalPaymentDeadlinePassed
                            )
                                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-amber-950">Perpanjangan menunggu pembayaran</p>
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Menunggu</span>
                                    </div>
                                    <p class="mt-1 text-sm text-amber-800">
                                        Selesaikan pembayaran sebelum {{ $renewalBooking->expires_at?->format('d M Y, H:i') ?? '-' }} agar kamar tetap dialokasikan untukmu.
                                    </p>
                                    <a href="{{ route('tenant.bookings.show', $renewalBooking) }}"
                                       class="mt-3 inline-flex rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">
                                        Lihat Pembayaran
                                    </a>
                                </div>
                            @elseif($renewalAttemptExpired)
                                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <div class="flex items-start gap-3">
                                        <i data-feather="alert-circle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"></i>
                                        <div>
                                            <p class="text-sm font-semibold text-amber-950">Kesempatan perpanjangan sudah berakhir</p>
                                            <p class="mt-1 text-sm leading-6 text-amber-800">
                                                Pengajuan perpanjangan sebelumnya tidak diselesaikan dalam batas waktu pembayaran 72 jam. Kamu tidak dapat mengajukan perpanjangan lagi untuk masa sewa ini. Jika masih ingin tinggal, silakan cari kamar dan lakukan booking baru melalui halaman utama.
                                            </p>
                                            <a href="{{ route('guest.home') }}"
                                               class="mt-3 inline-flex items-center rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">
                                                Cari Kamar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @elseif($renewalWindowOpen)
                                <div class="mt-6 rounded-lg border border-blue-100 bg-blue-50 p-4">
                                    <div class="flex items-start gap-3">
                                        <i data-feather="calendar" class="mt-0.5 h-5 w-5 shrink-0 text-blue-900"></i>
                                        <div>
                                            <p class="text-sm font-semibold text-blue-950">
                                                @if($daysUntilRentEnd === 7)
                                                    Hari terakhir untuk mengajukan perpanjangan
                                                @else
                                                    Masa perpanjangan tersedia
                                                @endif
                                            </p>
                                            <p class="mt-1 text-sm leading-6 text-blue-800">
                                                @if($daysUntilRentEnd === 7)
                                                    Hari ini adalah kesempatan terakhir untuk mengajukan perpanjangan. Ajukan sekarang dan selesaikan pembayaran dalam 72 jam.
                                                @else
                                                    Masa sewa kamu berakhir dalam {{ $daysUntilRentEnd }} hari. Kamu dapat mengajukan perpanjangan mulai sekarang hingga H-7.
                                                @endif
                                            </p>
                                            <a href="{{ route('tenant.rents.renewal.create', $currentRent) }}"
                                               class="mt-3 inline-flex items-center rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-950">
                                                Ajukan Perpanjangan
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @elseif($renewalWindowClosed)
                                <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-start gap-3">
                                        <i data-feather="calendar" class="mt-0.5 h-5 w-5 shrink-0 text-slate-600"></i>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">Periode perpanjangan telah ditutup</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                                Batas pengajuan perpanjangan adalah H-7 dan sudah terlewati. Kamu tidak dapat memperpanjang masa sewa ini. Jika masih ingin menyewa, silakan cari kamar dan buat booking baru melalui halaman utama.
                                            </p>
                                            <a href="{{ route('guest.home') }}"
                                               class="mt-3 inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                                                Cari Kamar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                @else

                    <div class="py-10 text-center">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i data-feather="home" class="h-7 w-7"></i>
                        </div>

                        <h3 class="font-semibold text-slate-800">
                            Belum Ada Penyewaan
                        </h3>

                        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                            Kamu belum memiliki informasi penyewaan.
                            Temukan kost yang sesuai dengan kebutuhanmu.
                        </p>

                        <a href="{{ route('guest.home') }}"
                           class="mt-5 inline-flex rounded-lg bg-blue-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                            Cari Kost
                        </a>
                    </div>

                @endif

            </div>
        </div>


        {{-- RIWAYAT PEMBAYARAN --}}
        <div class="rounded-xl border border-slate-200 bg-white">

            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="font-semibold text-slate-900">
                        Pembayaran Terbaru
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Lima transaksi pembayaran terakhir.
                    </p>
                </div>

                <a href="{{ url('/tenant/riwayat-pembayaran') }}"
                   class="text-sm font-medium text-blue-900 hover:underline">
                    Lihat semua
                </a>
            </div>

            <div class="overflow-x-auto">

                @if($recentPayments->isNotEmpty())
                    @include('components.tenant.payment-table', ['payments' => $recentPayments])
                @else
                    <div class="py-10 text-center">
                        <p class="text-sm text-slate-500">
                            Belum ada riwayat pembayaran.
                        </p>
                    </div>
                @endif

            </div>
        </div>

    </div>
</div>

@endsection
