@extends('components.layouts.owner.container')

@section('title', 'Data Penyewa')

@section('main')
    @php
        $statusLabels = [
            'active' => 'Aktif',
            'scheduled' => 'Terjadwal',
            'booking_pending' => 'Menunggu Pembayaran',
            'historical' => 'Riwayat',
        ];
        $statusClasses = [
            'active' => 'bg-emerald-50 text-emerald-700',
            'scheduled' => 'bg-sky-50 text-sky-700',
            'booking_pending' => 'bg-amber-50 text-amber-700',
            'historical' => 'bg-slate-100 text-slate-600',
        ];
        $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    @endphp
    <div class="space-y-6">
        <section
            class="flex flex-col gap-3 rounded-xl border border-slate-100 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Data Penyewa</h1>
                <p class="mt-1 text-sm text-slate-500">Pantau penyewa yang pernah bertransaksi di seluruh cabang yang kamu
                    kelola.</p>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <article class="rounded-xl border border-slate-100 bg-white p-4">
                <p class="text-xs text-slate-500">Total Penyewa</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($summary['total']) }}</p>
            </article>
            <article class="rounded-xl border border-slate-100 bg-white p-4">
                <p class="text-xs text-slate-500">Penyewa Aktif</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($summary['active']) }}</p>
            </article>
            <article class="rounded-xl border border-slate-100 bg-white p-4">
                <p class="text-xs text-slate-500">Menunggu Pembayaran</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ number_format($summary['booking_pending']) }}</p>
            </article>
            <article class="rounded-xl border border-slate-100 bg-white p-4">
                <p class="text-xs text-slate-500">Riwayat</p>
                <p class="mt-2 text-2xl font-bold text-slate-600">{{ number_format($summary['historical']) }}</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <form method="GET" action="{{ route('owner.tenants.index') }}"
                class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-4">
                <select name="branch_id" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="">Semua Cabang</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="all">Semua Status</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ $search }}"
                    placeholder="Cari nama, email, atau nomor HP"
                    class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                <div class="flex gap-2"><button
                        class="flex-1 rounded-lg bg-blue-900 hover:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white">Terapkan</button><a
                        href="{{ route('owner.tenants.index') }}"
                        class="rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-600">Reset</a></div>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="bg-indigo-50 text-[11px] uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-4">Penyewa</th>
                            <th class="px-5 py-4">Kontak</th>
                            <th class="px-5 py-4">Transaksi Terakhir</th>
                            <th class="px-5 py-4">Cabang</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                        @forelse ($tenants as $tenant)
                            @php
                                $user = $tenant->user;
                                $name = trim(($user?->fname ?? '') . ' ' . ($user?->lname ?? '')) ?: 'Penyewa';
                                $latest = $tenant->latest_transaction;
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-800">{{ $name }}</p>
                                    <p class="mt-1 text-slate-400">{{ $user?->email ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4">{{ $user?->phone_number ?: '-' }}</td>
                                <td class="px-5 py-4">
                                    @if ($latest)
                                        <p class="font-medium text-slate-800">
                                            {{ $latest->rent_code ?? ($latest->booking_code ?? ($latest instanceof \App\Models\Rent ? 'Rent #' : 'Booking #') . $latest->id) }}
                                        </p>
                                        <p class="mt-1 text-slate-400">{{ $latest->room?->room_code ?? '-' }}</p>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-5 py-4">{{ $latest?->room?->branchDorm?->name ?? 'Lihat detail transaksi' }}
                                </td>
                                <td class="px-5 py-4"><span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses[$tenant->derived_status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabels[$tenant->derived_status] ?? 'Riwayat' }}</span>
                                </td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('owner.tenants.show', $tenant->public_id) }}"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-[#315F96] hover:bg-slate-50">Detail
                                        <i data-feather="arrow-up-right" class="h-3.5 w-3.5"></i></a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center"><i data-feather="users"
                                        class="mx-auto h-8 w-8 text-slate-300"></i>
                                    <p class="mt-3 font-semibold text-slate-700">Belum ada data penyewa</p>
                                    <p class="mt-1 text-xs text-slate-400">Penyewa akan muncul setelah memiliki transaksi di
                                        cabang milikmu.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tenants->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $tenants->links() }}</div>
            @endif
        </section>
    </div>
@endsection
