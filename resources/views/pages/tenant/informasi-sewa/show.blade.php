@extends('components.layouts.tenant.container')

@section('title', 'Detail Sewa')

@section('content')
<div class="min-h-screen bg-slate-50 px-6 py-10">
    <div class="mx-auto max-w-4xl">
        <a href="{{ route('tenant.informasi-sewa') }}" class="text-sm font-medium text-blue-900 hover:underline">&larr; Kembali ke Informasi Sewa</a>

        <div class="mb-6 mt-5">
            <p class="text-sm font-medium text-blue-900">Detail Penyewaan</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 md:text-3xl">{{ $rent->rent_code ?? 'Detail Sewa' }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $rent->room?->branchDorm?->name ?? '-' }} · Kamar {{ $rent->room?->room_code ?? '-' }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-slate-900">Ringkasan Penyewaan</h2>
                @switch($rent->status)
                    @case('active') <span class="rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700">Sedang berlangsung</span> @break
                    @case('scheduled') <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">Akan dimulai</span> @break
                    @case('completed') <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">Selesai</span> @break
                    @default <span class="rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700">Dibatalkan</span>
                @endswitch
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div><p class="text-xs text-slate-500">Kode sewa</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->rent_code ?? '-' }}</p></div>
                <div><p class="text-xs text-slate-500">Kode booking</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->booking?->booking_code ?? '-' }}</p></div>
                <div><p class="text-xs text-slate-500">Nama kost</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->room?->branchDorm?->name ?? '-' }}</p></div>
                <div><p class="text-xs text-slate-500">Kamar</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->room?->room_code ?? '-' }} · {{ $rent->room?->type ?? '-' }}</p></div>
                <div><p class="text-xs text-slate-500">Alamat</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->room?->branchDorm?->address ?? '-' }}</p></div>
                <div><p class="text-xs text-slate-500">Durasi</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->booking?->duration_month ?? '-' }} bulan</p></div>
                <div><p class="text-xs text-slate-500">Tanggal mulai</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->start_date?->translatedFormat('d F Y') ?? '-' }}</p></div>
                <div><p class="text-xs text-slate-500">Tanggal berakhir</p><p class="mt-1 font-semibold text-slate-900">{{ $rent->end_date?->translatedFormat('d F Y') ?? '-' }}</p></div>
            </div>

            <div class="mt-7 rounded-xl bg-slate-50 p-5">
                <h3 class="font-semibold text-slate-900">Rincian biaya</h3>
                <div class="mt-4 flex justify-between gap-4 text-sm"><span class="text-slate-600">Harga per bulan saat booking</span><span class="font-medium text-slate-900">Rp{{ number_format((float) ($rent->booking?->price_snapshot ?? 0), 0, ',', '.') }}</span></div>
                <div class="mt-3 flex justify-between gap-4 text-sm"><span class="text-slate-600">Durasi</span><span class="font-medium text-slate-900">{{ $rent->booking?->duration_month ?? '-' }} bulan</span></div>
                <div class="mt-4 border-t border-slate-200 pt-4 flex justify-between gap-4"><span class="font-semibold text-slate-900">Total biaya sewa</span><span class="text-lg font-bold text-slate-900">Rp{{ number_format((float) $rent->total_price, 0, ',', '.') }}</span></div>
            </div>

            @if ($rent->status === 'active' || $rent->status === 'scheduled')
                <div class="mt-7 rounded-xl border border-slate-200 p-5">
                    <h3 class="font-semibold text-slate-900">Status masa sewa</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        @if ($rent->status === 'scheduled')
                            @if ($daysRemaining > 0) Masa sewa dimulai dalam {{ $daysRemaining }} hari.
                            @elseif ($daysRemaining === 0) Masa sewa dimulai hari ini.
                            @else Tanggal mulai telah lewat dan menunggu pembaruan status. @endif
                        @else
                            @if ($daysRemaining > 0) Tersisa {{ $daysRemaining }} hari sampai masa sewa berakhir.
                            @elseif ($daysRemaining === 0) Masa sewa berakhir hari ini.
                            @else Tanggal akhir masa sewa telah terlewati. @endif
                        @endif
                    </p>
                </div>
            @endif

            <div class="mt-7">
                <h3 class="font-semibold text-slate-900">Informasi Perpanjangan</h3>
                @if ($latestRenewal)
                    @php
                        $renewalExpired = $latestRenewal->status === 'pending_payment'
                            && $latestRenewal->expires_at
                            && $latestRenewal->expires_at->isPast();
                    @endphp
                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-5">
                        @if ($latestRenewal->status === 'pending_payment' && !$renewalExpired)
                            <p class="font-semibold text-amber-900">Menunggu pembayaran</p>
                            <p class="mt-1 text-sm text-amber-800">Selesaikan pembayaran sebelum {{ $latestRenewal->expires_at?->format('d M Y, H:i') ?? '-' }}.</p>
                            <a href="{{ route('tenant.bookings.show', $latestRenewal) }}" class="mt-3 inline-flex text-sm font-semibold text-blue-900 underline">Lihat pengajuan</a>
                        @elseif ($latestRenewal->status === 'confirmed')
                            <p class="font-semibold text-green-800">Perpanjangan berhasil</p>
                            <p class="mt-1 text-sm text-green-800">Periode baru: {{ $latestRenewal->start_date?->format('d M Y') }} – {{ $latestRenewal->end_date?->format('d M Y') }}.</p>
                        @else
                            <p class="font-semibold text-amber-900">Kesempatan perpanjangan sudah digunakan</p>
                            <p class="mt-1 text-sm text-amber-800">Status pengajuan: {{ $renewalExpired ? 'Kedaluwarsa' : ucfirst(str_replace('_', ' ', $latestRenewal->status)) }}. Pengajuan baru untuk masa sewa ini tidak tersedia.</p>
                        @endif
                    </div>
                @elseif ($renewalEligibility['eligible'])
                    <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 p-5">
                        <p class="font-semibold text-blue-950">Periode perpanjangan tersedia</p>
                        <p class="mt-1 text-sm text-blue-900">{{ $renewalEligibility['message'] }} Pembayaran harus diselesaikan dalam 72 jam setelah pengajuan.</p>
                        <a href="{{ route('tenant.rents.renewal.create', $rent) }}" class="mt-4 inline-flex rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-950">Ajukan Perpanjangan</a>
                    </div>
                @else
                    <p class="mt-2 text-sm text-slate-600">{{ $renewalEligibility['message'] }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
