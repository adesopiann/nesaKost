@extends('components.layouts.owner.container')

@section('title', 'Data Sewa')

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
            'scheduled' => 'Terjadwal',
            'active' => 'Berjalan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ];
        $statusClasses = [
            'scheduled' => 'bg-sky-50 text-sky-700',
            'active' => 'bg-emerald-50 text-emerald-700',
            'completed' => 'bg-slate-100 text-slate-700',
            'cancelled' => 'bg-rose-50 text-rose-700',
        ];
        $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    @endphp
    <div class="space-y-6">
        <section
            class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Data Sewa</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">Pantau seluruh kontrak sewa dari cabang yang kamu kelola.
                </p>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-5">
            @foreach ([['Total Kontrak', 'total', 'bg-indigo-50 text-indigo-800'], ['Berjalan', 'active', 'bg-emerald-50 text-emerald-800'], ['Terjadwal', 'scheduled', 'bg-sky-50 text-sky-800'], ['Selesai', 'completed', 'bg-slate-100 text-slate-700'], ['Dibatalkan', 'cancelled', 'bg-rose-50 text-rose-800']] as [$label, $key, $classes])
                <article class="rounded-xl border border-slate-100 bg-white p-4">
                    <p class="text-xs text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold {{ $classes }} inline-flex rounded-md px-2 py-1">
                        {{ number_format($summary[$key]) }}</p>
                </article>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <form method="GET" action="{{ route('owner.rents') }}"
                class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-5">
                <select name="branch_id" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="">Semua Cabang</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <select name="month" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="" @selected($month === null)>Semua Bulan</option>
                    @foreach ($monthNames as $number => $name)
                        <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="year" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="" @selected($year === null)>Semua Tahun</option>
                    @foreach ($yearOptions as $optionYear)
                        <option value="{{ $optionYear }}" @selected($year === $optionYear)>{{ $optionYear }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="all">Semua Status</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2"><input type="search" name="q" value="{{ $search }}"
                        placeholder="Cari kode, kamar, penyewa..."
                        class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2.5 text-sm"><button
                        class="rounded-lg bg-blue-900 hover:bg-blue-800 px-4 text-sm font-semibold text-white">Filter</button>
                </div>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[950px] text-left text-sm">
                    <thead class="bg-indigo-50 text-[11px] uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-4">Kode Sewa</th>
                            <th class="px-5 py-4">Penyewa</th>
                            <th class="px-5 py-4">Kamar / Cabang</th>
                            <th class="px-5 py-4">Periode Sewa</th>
                            <th class="px-5 py-4">Tanggal Dibuat</th>
                            <th class="px-5 py-4 text-right">Total</th>
                            <th class="px-5 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                        @forelse($rents as $rent)
                            @php $tenantName=trim(($rent->fname??'').' '.($rent->lname??'')) ?: 'Penyewa'; @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-[#315F96]">
                                    {{ $rent->rent_code ?: 'RENT-' . $rent->id }}</td>
                                <td class="px-5 py-4 font-medium text-slate-800">{{ $tenantName }}</td>
                                <td class="px-5 py-4">
                                    <p class="font-medium text-slate-800">{{ $rent->room_code }}</p>
                                    <p class="mt-1 text-slate-400">{{ $rent->branch_name }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    {{ \Carbon\Carbon::parse($rent->start_date)->format('d M Y') }} –
                                    {{ \Carbon\Carbon::parse($rent->end_date)->format('d M Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    {{ \Carbon\Carbon::parse($rent->created_at)->format('d M Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-800">
                                    {{ $money($rent->total_price) }}</td>
                                <td class="px-5 py-4"><span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses[$rent->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabels[$rent->status] ?? ucfirst($rent->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-14 text-center text-sm text-slate-500">Belum ada kontrak
                                    sewa pada periode dan filter yang dipilih.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div
                class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>Menampilkan {{ $rents->firstItem() ?? 0 }}–{{ $rents->lastItem() ?? 0 }} dari {{ $rents->total() }}
                    kontrak</p>{{ $rents->links() }}
            </div>
        </section>
    </div>
@endsection
