@extends('components.layouts.owner.container')
@section('main')
    <div class="space-y-6 text-slate-800">
        @include('pages.owner.rooms.partials.flash')
        <section
            class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Data Kamar</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">Kelola kode, tipe, harga, foto, dan status kamar dari
                    seluruh cabang yang kamu miliki.</p>
            </div>
            <a href="{{ route('owner.rooms.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800"><span
                    class="mr-2 text-lg">+</span> Tambah Kamar</a>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total Kamar</p>
                <p class="mt-3 text-3xl font-bold">{{ $totalRooms }} <span
                        class="text-base font-medium text-slate-500">kamar</span></p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Kamar Tersedia</p>
                <p class="mt-3 text-3xl font-bold">{{ $availableRooms }} <span
                        class="text-base font-medium text-slate-500">kamar</span></p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Kamar Terisi</p>
                <p class="mt-3 text-3xl font-bold">{{ $occupiedRooms }} <span
                        class="text-base font-medium text-slate-500">kamar</span></p>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-between">
                <form method="GET" action="{{ route('owner.rooms.index') }}"
                    class="flex w-full flex-col gap-2 md:max-w-3xl md:flex-row">
                    <input type="search" name="q" value="{{ $search }}"
                        placeholder="Cari kode, tipe, deskripsi, atau cabang..."
                        class="w-full rounded-lg border-0 bg-indigo-50 px-4 py-3 text-sm outline-none ring-blue-300 focus:ring-2">
                    <select name="branch"
                        class="rounded-lg border-0 bg-indigo-50 px-4 py-3 text-sm outline-none ring-blue-300 focus:ring-2">
                        <option value="">Semua cabang</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-blue-900 px-4 py-3 text-sm font-semibold text-white">Cari</button>
                    @if ($search !== '' || $branchId)
                        <a href="{{ route('owner.rooms.index') }}"
                            class="rounded-lg bg-slate-100 px-4 py-3 text-center text-sm">Reset</a>
                    @endif
                </form>
                <a href="{{ route('owner.rooms.index') }}" title="Muat ulang"
                    class="self-end rounded-lg p-2 text-slate-500 hover:bg-slate-100">↻</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="bg-indigo-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-6 py-4">Kamar</th>
                            <th class="px-6 py-4">Cabang</th>
                            <th class="px-6 py-4">Tipe</th>
                            <th class="px-6 py-4">Harga / Bulan</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rooms as $room)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $primaryImage = $room->primaryImage;
                                        @endphp

                                        @if ($room->firstImage)
                                            <img src="{{ asset('storage/' . $room->firstImage->image_path) }}"
                                                alt="Foto {{ $room->room_code }}"
                                                class="h-14 w-16 rounded-lg object-cover">
                                        @else
                                            <div
                                                class="flex h-14 w-16 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                                Foto tidak tersedia
                                            </div>
                                        @endif
                                        <div><a href="{{ route('owner.rooms.show', $room->id) }}"
                                                class="font-semibold text-slate-900 hover:text-blue-800">{{ $room->room_code }}</a>
                                            <p class="mt-1 text-xs text-slate-500">{{ $room->images->count() }} foto · ID:
                                                {{ $room->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">{{ $room->branch?->name ?? '—' }}</td>
                                <td class="px-6 py-5">{{ $room->type }}</td>
                                <td class="px-6 py-5 font-semibold">Rp
                                    {{ number_format((float) $room->monthly_price, 0, ',', '.') }}</td>
                                <td class="px-6 py-5">
                                    @php($statusClasses = ['available' => 'bg-emerald-100 text-emerald-800', 'occupied' => 'bg-blue-100 text-blue-800', 'maintenance' => 'bg-amber-100 text-amber-800', 'inactive' => 'bg-slate-100 text-slate-600'])
                                    @php($statusLabels = ['available' => 'Tersedia', 'occupied' => 'Terisi', 'maintenance' => 'Perawatan', 'inactive' => 'Nonaktif'])
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$room->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabels[$room->status] ?? ucfirst($room->status) }}</span>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('owner.rooms.show', $room->id) }}"
                                            class="font-semibold text-blue-800 hover:underline">Detail</a>
                                        <a href="{{ route('owner.rooms.edit', $room->id) }}"
                                            class="font-semibold text-slate-700 hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('owner.rooms.destroy', $room->id) }}"
                                            onsubmit="return confirm('Yakin ingin menghapus kamar ini?')">
                                            @csrf @method('DELETE')
                                            <button class="font-semibold text-red-600 hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-slate-500">Belum ada data kamar yang
                                    cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div
                class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
                <p>Menampilkan {{ $rooms->firstItem() ?? 0 }}–{{ $rooms->lastItem() ?? 0 }} dari {{ $rooms->total() }}
                    kamar</p>
                {{ $rooms->links() }}
            </div>
        </section>
    </div>
@endsection
