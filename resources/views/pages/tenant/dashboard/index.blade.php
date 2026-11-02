
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

                        <a href="{{ route('customer.home') }}"
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

                    <table class="w-full min-w-[650px] text-left text-sm">

                        <thead class="bg-slate-50 text-xs text-slate-500">
                            <tr>
                                <th class="px-6 py-4 font-medium">Kode Pembayaran</th>
                                <th class="px-6 py-4 font-medium">Tanggal</th>
                                <th class="px-6 py-4 font-medium">Metode</th>
                                <th class="px-6 py-4 font-medium">Jumlah</th>
                                <th class="px-6 py-4 font-medium">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($recentPayments as $payment)

                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        {{ $payment->payment_code ?? '-' }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-500">
                                        {{ $payment->paid_at
                                            ? $payment->paid_at->format('d M Y')
                                            : $payment->created_at->format('d M Y') }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-500">
                                        {{ $payment->payment_method ?? '-' }}
                                    </td>

                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}
                                    </td>

                                    <td class="px-6 py-4">
                                        @if($payment->status === 'paid')
                                            <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700">
                                                Berhasil
                                            </span>
                                        @elseif($payment->status === 'pending')
                                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                                                Menunggu
                                            </span>
                                        @elseif($payment->status === 'failed')
                                            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700">
                                                Gagal
                                            </span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                                {{ ucfirst($payment->status) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                            @endforeach

                        </tbody>
                    </table>

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
