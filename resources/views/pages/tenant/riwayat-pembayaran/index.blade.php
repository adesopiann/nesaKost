@extends('components.layouts.tenant.container')

@section('title', 'Riwayat Pembayaran')

@section('content')
<div class="min-h-screen bg-slate-50 px-6 py-10">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8">
            <p class="text-sm font-medium text-blue-900">Area Penyewa</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 md:text-3xl">Riwayat Pembayaran</h1>
            <p class="mt-2 text-sm text-slate-500">Pantau seluruh transaksi pembayaran penyewaanmu.</p>
        </div>

        <div class="mb-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Total transaksi</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-400">Seluruh percobaan pembayaran</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Total pembayaran berhasil</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">Rp{{ number_format((float) $totalPaid, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-400">Akumulasi transaksi berstatus berhasil</p>
            </div>
        </div>

        <form method="GET" action="{{ route('tenant.riwayat-pembayaran') }}" class="mb-6 rounded-xl border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center gap-2">
                <i data-feather="filter" class="h-4 w-4 text-slate-500"></i>
                <h2 class="font-semibold text-slate-900">Filter Transaksi</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="status" class="mb-1.5 block text-sm font-medium text-slate-700">Status pembayaran</label>
                    <select id="status" name="status" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-900 focus:ring-blue-900">
                        @foreach (['all' => 'Semua status', 'pending' => 'Menunggu pembayaran', 'paid' => 'Berhasil', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan'] as $value => $label)
                            <option value="{{ $value }}" @selected(($validated['status'] ?? 'all') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="mb-1.5 block text-sm font-medium text-slate-700">Dari tanggal</label>
                    <input id="date_from" type="date" name="date_from" value="{{ $validated['date_from'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-900 focus:ring-blue-900">
                </div>
                <div>
                    <label for="date_to" class="mb-1.5 block text-sm font-medium text-slate-700">Sampai tanggal</label>
                    <input id="date_to" type="date" name="date_to" value="{{ $validated['date_to'] ?? '' }}" min="{{ $validated['date_from'] ?? '' }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-900 focus:ring-blue-900">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Terapkan</button>
                    <a href="{{ route('tenant.riwayat-pembayaran') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Reset</a>
                </div>
            </div>
            @error('date_to')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </form>

        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Daftar Transaksi</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $payments->total() }} transaksi ditemukan</p>
            </div>
        </div>

        @if ($payments->isNotEmpty())
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                @include('components.tenant.payment-table', ['payments' => $payments])
            </div>
        @else
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100"><i data-feather="credit-card" class="h-5 w-5 text-slate-500"></i></div>
                <p class="mt-3 text-sm font-semibold text-slate-800">Belum ada transaksi pembayaran</p>
                <p class="mt-1 text-sm text-slate-500">Transaksi pembayaranmu akan muncul di halaman ini.</p>
            </div>
        @endif

        @if ($payments->hasPages())
            <div class="mt-6">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
@endsection
