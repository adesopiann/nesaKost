@extends('components.layouts.owner.container')

@section('title', 'Data Pembayaran')

@section('main')
    @php
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
        $yearOptions = range(now()->year, max(2020, now()->year - 5));
        $statusLabels = [
            'pending' => 'Menunggu',
            'paid' => 'Berhasil',
            'failed' => 'Gagal',
            'expired' => 'Kedaluwarsa',
        ];
        $statusClasses = [
            'pending' => 'bg-amber-50 text-amber-700',
            'paid' => 'bg-emerald-50 text-emerald-700',
            'failed' => 'bg-rose-50 text-rose-700',
            'expired' => 'bg-slate-100 text-slate-700',
        ];
        $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    @endphp
    <div class="space-y-6">
        <section
            class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Data Pembayaran</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">Pantau transaksi pembayaran dari seluruh cabang. Owner
                    memiliki akses
                    pemantauan, bukan konfirmasi manual.
                </p>
            </div>
        </section>
        <section class="grid gap-3 sm:grid-cols-2">
            <article class="rounded-xl border border-slate-100 bg-white p-5">
                <p class="text-sm text-slate-500">Pendapatan Diterima</p>
                <p class="mt-2 text-2xl font-bold text-[#1D4E80]">{{ $money($summary['amount']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Hanya transaksi paid yang memiliki tanggal
                    penerimaan{{ $month !== null ? ' pada ' . $monthNames[$month] : '' }}{{ $year !== null ? ' ' . $year : '' }}.
                </p>
            </article>
            <article class="rounded-xl border border-slate-100 bg-white p-5">
                <p class="text-sm text-slate-500">Jumlah Pembayaran Berhasil</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($summary['transactions']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Transaksi yang telah diterima.</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <form method="GET" action="{{ route('owner.payments') }}"
                class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-5">
                <select name="branch_id" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="" @selected($branchId === null)>Semua Cabang</option>

                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                <select name="month" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="" @selected($month === null)>Semua Bulan</option>

                    @foreach ($monthNames as $number => $name)
                        <option value="{{ $number }}" @selected($month === $number)>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                <select name="year" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="" @selected($year === null)>Semua Tahun</option>

                    @foreach ($yearOptions as $optionYear)
                        <option value="{{ $optionYear }}" @selected($year === $optionYear)>
                            {{ $optionYear }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="all">Semua Status</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2"><input type="search" name="q" value="{{ $search }}"
                        placeholder="Cari kode, penyewa, kamar..."
                        class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2.5 text-sm"><button
                        class="rounded-lg bg-blue-900 hover:bg-blue-800 px-4 text-sm font-semibold text-white">Filter</button>
                </div>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-sm">
                    <thead class="bg-indigo-50 text-[11px] uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-4">Kode Pembayaran</th>
                            <th class="px-5 py-4">Kode Booking</th>
                            <th class="px-5 py-4">Penyewa</th>
                            <th class="px-5 py-4">Kamar / Cabang</th>
                            <th class="px-5 py-4">Metode</th>
                            <th class="px-5 py-4">Tanggal</th>
                            <th class="px-5 py-4 text-right">Nominal</th>
                            <th class="px-5 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                        @forelse($payments as $payment)
                            @php
                                $tenantName =
                                    trim(($payment->fname ?? '') . ' ' . ($payment->lname ?? '')) ?: 'Penyewa';
                                $transactionDate =
                                    $payment->status === 'paid' && $payment->paid_at
                                        ? $payment->paid_at
                                        : $payment->created_at;
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-[#315F96]">
                                    {{ $payment->payment_code ?: 'PAY-' . $payment->id }}</td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $payment->booking_code }}</td>
                                <td class="px-5 py-4 font-medium text-slate-800">{{ $tenantName }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-slate-800">{{ $payment->room_code }}</p>
                                    <p class="mt-1 text-slate-400">{{ $payment->branch_name }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">{{ $payment->payment_method ?: 'Belum dipilih' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    {{ \Carbon\Carbon::parse($transactionDate)->format('d M Y H:i') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-800">
                                    {{ $money($payment->amount) }}</td>
                                <td class="px-5 py-4"><span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses[$payment->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabels[$payment->status] ?? ucfirst($payment->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-14 text-center text-sm text-slate-500">Belum ada transaksi
                                    pada periode dan filter yang dipilih.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div
                class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>Menampilkan {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} dari
                    {{ $payments->total() }} transaksi</p>{{ $payments->links() }}
            </div>
        </section>
    </div>
@endsection
