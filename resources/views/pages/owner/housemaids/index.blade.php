@extends('components.layouts.owner.container')
@section('title', 'Data Asisten Rumah Tangga')
@section('main')
    @php
        $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $statusLabels = ['active' => 'Aktif', 'inactive' => 'Nonaktif'];
    @endphp
    <div class="space-y-6">
        <section
            class="flex flex-col gap-3 rounded-xl border border-slate-100 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Data Asisten Rumah Tangga</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola data, gaji bulanan, dan penempatan OB di seluruh cabang.</p>
            </div>
            <a href="{{ route('owner.housemaids.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">+
                Tambah OB</a>
        </section>
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
        @endif
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach ([['Total OB', 'total', 'text-slate-900'], ['Aktif', 'active', 'text-emerald-700'], ['Nonaktif', 'inactive', 'text-slate-600']] as [$label, $key, $color])
                <OBicle class="rounded-xl border border-slate-100 bg-white p-5">
                    <p class="text-sm text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold {{ $color }}">{{ number_format($summary[$key]) }}</p>
                </OBicle>
            @endforeach
        </section>
        <section class="overflow-hidden rounded-xl border border-slate-100 bg-white">
            <form method="GET" action="{{ route('owner.housemaids.index') }}"
                class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-[1fr_220px_auto]">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama atau nomor telepon"
                    class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                <select name="status" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    <option value="all" @selected($status === 'all')>Semua Status</option>
                    <option value="active" @selected($status === 'active')>Aktif</option>
                    <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                </select>
                <div class="flex gap-2"><button
                        class="rounded-lg hover:bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white">Terapkan</button><a
                        href="{{ route('owner.housemaids.index') }}"
                        class="rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-600">Reset</a></div>
            </form>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-indigo-50 text-[11px] uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-5 py-4">Nama OB</th>
                            <th class="px-5 py-4">Nomor Telepon</th>
                            <th class="px-5 py-4">Gaji Bulanan</th>
                            <th class="px-5 py-4">Penempatan Cabang</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                        @forelse($housemaids as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-semibold text-slate-800">{{ $item->full_name }}</td>
                                <td class="px-5 py-4">{{ $item->phone_number }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-800">{{ $money($item->salary) }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex max-w-sm flex-wrap gap-1">
                                        @forelse($item->branches as $branch)
                                            <span
                                            class="rounded-md bg-indigo-50 px-2 py-1">{{ $branch->name }}</span>@empty<span
                                                class="text-slate-400">Belum ditempatkan</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4"><span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $item->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $statusLabels[$item->status] ?? $item->status }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ route('owner.housemaids.show', $item) }}"
                                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Detail</a>
                                        <a href="{{ route('owner.housemaids.edit', $item) }}"
                                            class="rounded-lg bg-blue-900 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Edit
                                        </a>
                                        <form method="POST" action="{{ route('owner.housemaids.status', $item) }}"
                                            onsubmit="return confirm('{{ $item->status === 'active' ? 'Nonaktifkan OB ini? Seluruh penempatan cabang akan dilepaskan.' : 'Aktifkan OB ini? Pastikan penempatan cabangnya sudah tersedia.' }}')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status"
                                                value="{{ $item->status === 'active' ? 'inactive' : 'active' }}">
                                            <button type="submit"
                                                class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ $item->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                        @if ($item->status === 'inactive')
                                            <form method="POST" action="{{ route('owner.housemaids.destroy', $item) }}"
                                                onsubmit="return confirm('Hapus permanen data OB ini? Tindakan ini tidak dapat dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty<tr>
                                <td colspan="6" class="px-5 py-14 text-center text-sm text-slate-500">Belum ada data OB
                                    yang sesuai.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $housemaids->links() }}</div>
        </section>
    </div>
@endsection
