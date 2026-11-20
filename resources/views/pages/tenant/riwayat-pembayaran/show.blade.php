@extends('components.layouts.tenant.container')

@section('title', 'Detail Pembayaran')

@section('content')
@php
    $booking = $payment->booking;
    $room = $booking?->room;
    $branch = $room?->branchDorm;
    $rent = $booking?->rent;
    $statusLabels = ['pending' => 'Menunggu Pembayaran', 'paid' => 'Berhasil', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan'];
    $statusClasses = ['pending' => 'bg-amber-50 text-amber-700', 'paid' => 'bg-green-50 text-green-700', 'failed' => 'bg-red-50 text-red-700', 'expired' => 'bg-slate-100 text-slate-600', 'cancelled' => 'bg-slate-100 text-slate-600'];
    $methodLabels = ['va_bca' => 'BCA Virtual Account', 'va_mandiri' => 'Mandiri Virtual Account', 'va_bni' => 'BNI Virtual Account', 'va_bri' => 'BRI Virtual Account', 'va_permata' => 'Permata Virtual Account', 'qris' => 'QRIS', 'gopay' => 'GoPay', 'ovo' => 'OVO', 'dana' => 'DANA', 'shopeepay' => 'ShopeePay'];
@endphp
<div class="min-h-screen bg-slate-50 px-6 py-10">
    <div class="mx-auto max-w-4xl">
        <a href="{{ route('tenant.riwayat-pembayaran') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-900 hover:underline">
            <i data-feather="arrow-left" class="h-4 w-4"></i> Kembali ke Riwayat Pembayaran
        </a>

        <div class="mb-6 mt-6">
            <p class="text-sm font-medium text-blue-900">Detail Transaksi</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 md:text-3xl">{{ $payment->payment_code ?? 'PAY-' . $payment->id }}</h1>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-6 text-center">
                <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClasses[$payment->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabels[$payment->status] ?? ucfirst($payment->status) }}</span>
                <p class="mt-4 text-sm text-slate-500">Nominal transaksi</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ ($payment->status === 'paid' ? $payment->paid_at : $payment->created_at)?->translatedFormat('d F Y, H:i') ?? '-' }}</p>
            </div>

            <div class="grid gap-8 p-6 md:grid-cols-2 md:p-8">
                <div>
                    <h2 class="font-semibold text-slate-900">Informasi Transaksi</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Kode pembayaran</dt><dd class="text-right font-medium text-slate-900">{{ $payment->payment_code ?? 'PAY-' . $payment->id }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Kode booking</dt><dd class="text-right font-medium text-slate-900">{{ $booking?->booking_code ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Kode sewa</dt><dd class="text-right font-medium text-slate-900">{{ $rent?->rent_code ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Jenis transaksi</dt><dd class="text-right font-medium text-slate-900">{{ $booking?->booking_type === 'renewal' ? 'Perpanjangan sewa' : 'Booking baru' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Metode pembayaran</dt><dd class="text-right font-medium text-slate-900">{{ $methodLabels[$payment->payment_method] ?? ($payment->payment_method ? strtoupper($payment->payment_method) : '-') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Provider</dt><dd class="text-right font-medium text-slate-900">{{ $payment->provider ? strtoupper($payment->provider) : '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Referensi pembayaran</dt><dd class="max-w-[60%] break-all text-right font-medium text-slate-900">{{ $payment->payment_reference ?? $payment->provider_reference ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Batas pembayaran</dt><dd class="text-right font-medium text-slate-900">{{ $payment->expires_at?->translatedFormat('d M Y, H:i') ?? '-' }}</dd></div>
                    </dl>
                </div>

                <div>
                    <h2 class="font-semibold text-slate-900">Informasi Penyewaan</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Nama kost</dt><dd class="text-right font-medium text-slate-900">{{ $branch?->name ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Kamar</dt><dd class="text-right font-medium text-slate-900">{{ $room?->room_code ?? '-' }} · {{ $room?->type ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Alamat</dt><dd class="max-w-[60%] text-right font-medium text-slate-900">{{ $branch?->address ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Periode sewa</dt><dd class="text-right font-medium text-slate-900">{{ $booking?->start_date?->format('d M Y') ?? '-' }} – {{ $booking?->end_date?->format('d M Y') ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Durasi</dt><dd class="text-right font-medium text-slate-900">{{ $booking?->duration_month ?? '-' }} bulan</dd></div>
                    </dl>
                </div>
            </div>

            @if ($payment->payment_instruction && $payment->status === 'pending')
                <div class="border-t border-slate-100 bg-amber-50 p-6">
                    <h2 class="font-semibold text-amber-900">Instruksi Pembayaran</h2>
                    <p class="mt-2 whitespace-pre-line text-sm text-amber-900">{{ $payment->payment_instruction }}</p>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
