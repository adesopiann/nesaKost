@extends('components.layouts.owner.container')
@section('main')

<div class="space-y-6 text-slate-800">
    @include('pages.owner.branches.partials.flash')

    <section class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Data Cabang Kos</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">Kelola informasi cabang properti kos, kapasitas kamar, fasilitas umum, dan informasi lokasi secara terpusat.</p>
        </div>
        <a href="{{ route('owner.branches.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">
            <span class="mr-2 text-lg">+</span> Tambah Cabang Kos
        </a>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Cabang Kos</p>
            <div class="mt-3 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $totalBranches }} <span class="text-base font-medium text-slate-500">Cabang</span></p>
                <span class="rounded-md bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">Milik Anda</span>
            </div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total Kapasitas Kamar</p>
            <div class="mt-3 flex items-end justify-between">
                <p class="text-3xl font-bold">{{ $totalRooms }} <span class="text-base font-medium text-slate-500">Kamar</span></p>
                <span class="rounded-md bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">{{ $occupancy }}% Terisi</span>
            </div>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Fasilitas Terdistribusi</p>
            <p class="mt-3 text-3xl font-bold">{{ $facilityCount }} <span class="text-base font-medium text-slate-500">Relasi fasilitas</span></p>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 md:flex-row md:items-center md:justify-between">
            <form method="GET" action="{{ route('owner.branches.index') }}" class="flex w-full max-w-xl gap-2">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama cabang, kode cabang, atau alamat..." class="w-full rounded-lg border-0 bg-indigo-50 px-4 py-3 text-sm outline-none ring-blue-300 focus:ring-2">
                <button class="rounded-lg bg-blue-900 px-4 text-sm font-semibold text-white">Cari</button>
                @if($search !== '')
                    <a href="{{ route('owner.branches.index') }}" class="rounded-lg bg-slate-100 px-4 py-3 text-sm">Reset</a>
                @endif
            </form>
            <a href="{{ route('owner.branches.index') }}" title="Muat ulang" class="self-end rounded-lg p-2 text-slate-500 hover:bg-slate-100">↻</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-indigo-50 text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-6 py-4">Nama Cabang</th>
                        <th class="px-6 py-4">Alamat Lengkap</th>
                        <th class="px-6 py-4 text-center">Kapasitas</th>
                        <th class="px-6 py-4">Fasilitas Umum</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($branches as $branch)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-5">
                                <a href="{{ route('owner.branches.show', $branch) }}" class="font-semibold text-slate-900 hover:text-blue-800">{{ $branch->name }}</a>
                                <p class="mt-1 text-xs text-blue-800">{{ $branch->branch_code }}</p>
                            </td>
                            <td class="max-w-xs px-6 py-5 text-slate-500">{{ \Illuminate\Support\Str::limit($branch->address, 100) }}</td>
                            <td class="px-6 py-5 text-center font-semibold">{{ $branch->rooms_count }} kamar</td>
                            <td class="px-6 py-5">
                                <div class="flex max-w-sm flex-wrap gap-1.5">
                                    @forelse($branch->facilities->take(4) as $facility)
                                        <span class="rounded-md bg-indigo-50 px-2 py-1 text-xs text-slate-700">{{ $facility->facility_name }}</span>
                                    @empty
                                        <span class="text-xs text-slate-400">Belum ada fasilitas</span>
                                    @endforelse
                                    @if($branch->facilities->count() > 4)
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs">+{{ $branch->facilities->count() - 4 }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $branch->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $branch->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('owner.branches.show', $branch) }}" class="font-semibold text-blue-800 hover:underline">Detail</a>
                                    <a href="{{ route('owner.branches.edit', $branch) }}" class="font-semibold text-slate-700 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('owner.branches.destroy', $branch) }}" onsubmit="return confirm('Yakin ingin menghapus cabang ini?')">
                                        @csrf @method('DELETE')
                                        <button class="font-semibold text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-16 text-center text-slate-500">Belum ada data cabang kos yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
            <p>Menampilkan {{ $branches->firstItem() ?? 0 }}–{{ $branches->lastItem() ?? 0 }} dari {{ $branches->total() }} cabang kos</p>
            {{ $branches->links() }}
        </div>
    </section>
</div>

@endsection
