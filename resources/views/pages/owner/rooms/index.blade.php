@extends('components.layouts.owner.container')
@section('main')

    @include('components.owner.room-status-blockers')
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
                class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                + Tambah Kamar
            </a>
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
                <a href="{{ route('owner.rooms.index') }}" title="Muat ulang" aria-label="Muat ulang"
                    class="self-end rounded-lg p-2 text-slate-500 hover:bg-slate-100"><i data-feather="refresh-cw"
                        class="h-4 w-4"></i></a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-sm">
                    <thead class="bg-indigo-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-6 py-4">Kamar</th>
                            <th class="px-6 py-4">Cabang</th>
                            <th class="px-6 py-4">Tipe</th>
                            <th class="px-6 py-4">Harga / Bulan</th>
                            <th class="px-6 py-4">Status Oprasional</th>
                            <th class="px-6 py-4">Ketersediaan</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rooms as $room)
                            @php
                                $operationalClasses = [
                                    'active' => 'bg-emerald-100 text-emerald-800',
                                    'inactive' => 'bg-slate-100 text-slate-600',
                                    'maintenance' => 'bg-amber-100 text-amber-800',
                                ];
                                $operationalLabels = [
                                    'active' => 'Aktif',
                                    'inactive' => 'Nonaktif',
                                    'maintenance' => 'Perawatan',
                                ];
                                $availability = $room->availability ?? [];
                                $availabilityStatus = $availability['status'] ?? 'unavailable';
                                $availabilityClasses = [
                                    'available' => 'bg-emerald-100 text-emerald-800',
                                    'occupied' => 'bg-blue-100 text-blue-800',
                                    'reserved' => 'bg-indigo-100 text-indigo-800',
                                    'renewal_priority' => 'bg-amber-100 text-amber-800',
                                    'unavailable' => 'bg-slate-100 text-slate-600',
                                ];
                                $availabilityLabels = [
                                    'available' => 'Tersedia',
                                    'occupied' => 'Terisi',
                                    'reserved' => 'Dipesan',
                                    'renewal_priority' => 'Prioritas Renewal',
                                    'unavailable' => 'Tidak tersedia',
                                ];
                                $hasHistory = ($room->bookings_count ?? 0) > 0 || ($room->rents_count ?? 0) > 0;
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($room->firstImage)
                                            <img src="{{ asset('storage/' . $room->firstImage->image_path) }}"
                                                alt="Foto {{ $room->room_code }}"
                                                class="h-11 w-11 rounded-lg object-cover">
                                        @else
                                            <div
                                                class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                                <i data-feather="image" class="h-4 w-4"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('owner.rooms.show', $room) }}"
                                                class="font-semibold text-blue-900 hover:underline">
                                                {{ $room->room_code }}
                                            </a>
                                            <p class="text-xs text-slate-500">ID #{{ $room->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">{{ $room->branch?->name ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $room->type ?: '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    Rp {{ number_format((float) $room->monthly_price, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $operationalClasses[$room->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $operationalLabels[$room->status] ?? ucfirst($room->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $availabilityClasses[$availabilityStatus] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $availabilityLabels[$availabilityStatus] ?? ucfirst($availabilityStatus) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <a href="{{ route('owner.rooms.show', $room) }}"
                                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Detail
                                        </a>

                                        <details data-room-action-dropdown class="relative text-left">
                                            <summary
                                                class="inline-flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Lainnya
                                                <i data-feather="chevron-down" class="h-3.5 w-3.5"></i>
                                            </summary>
                                            <div data-dropdown-menu
                                                class="hidden w-56 rounded-xl border border-slate-200 bg-white p-2 text-left shadow-xl">
                                                <a href="{{ route('owner.rooms.edit', $room) }}"
                                                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                                    <i data-feather="edit-2" class="h-4 w-4"></i>
                                                    Edit kamar
                                                </a>

                                                <div class="my-1 border-t border-slate-100"></div>
                                                <p
                                                    class="px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                    Ubah status
                                                </p>

                                                @foreach ([
            'active' => 'Aktifkan kamar',
            'inactive' => 'Nonaktifkan kamar',
            'maintenance' => 'Tandai perawatan',
        ] as $statusValue => $statusLabel)
                                                    @if ($room->status !== $statusValue)
                                                        <form method="POST"
                                                            action="{{ route('owner.rooms.update-status', $room) }}"
                                                            data-confirm-modal
                                                            data-confirm-title="Konfirmasi perubahan status"
                                                            data-confirm-message="Kamu akan mengubah status kamar {{ $room->room_code }} menjadi {{ strtolower($statusLabel) }}. Pastikan kamar tidak memiliki booking maupun penyewaan. Lanjutkan?"
                                                            data-confirm-accept="Ya, ubah status">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status"
                                                                value="{{ $statusValue }}">
                                                            <button type="submit"
                                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                                                <i data-feather="power" class="h-4 w-4"></i>
                                                                {{ $statusLabel }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endforeach

                                                <div class="my-1 border-t border-slate-100"></div>
                                                @if (!$hasHistory)
                                                    <form method="POST"
                                                        action="{{ route('owner.rooms.destroy', $room) }}"
                                                        data-confirm-modal data-confirm-title="Hapus kamar"
                                                        data-confirm-message="Kamar {{ $room->room_code }} akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan. Lanjutkan?"
                                                        data-confirm-accept="Ya, hapus kamar">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                                            <i data-feather="trash-2" class="h-4 w-4"></i>
                                                            Hapus kamar
                                                        </button>
                                                    </form>
                                                @else
                                                    <div class="rounded-lg px-3 py-2 text-xs text-slate-400"
                                                        title="Kamar memiliki riwayat booking atau penyewaan.">
                                                        <span class="flex items-center gap-2">
                                                            <i data-feather="lock" class="h-4 w-4"></i>
                                                            Hapus tidak tersedia: ada riwayat transaksi
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center text-slate-500">Belum ada data kamar yang
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


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('details[data-room-action-dropdown]');

            function restoreMenu(dropdown) {
                const menu = dropdown._portalMenu;
                const placeholder = dropdown._portalPlaceholder;

                if (!menu || !placeholder) return;

                menu.classList.remove('fixed');
                menu.style.removeProperty('top');
                menu.style.removeProperty('left');
                menu.style.removeProperty('right');
                menu.style.removeProperty('z-index');
                menu.classList.add('hidden');

                placeholder.parentNode.insertBefore(menu, placeholder);
                placeholder.remove();

                dropdown._portalMenu = null;
                dropdown._portalPlaceholder = null;
            }

            function positionMenu(dropdown) {
                const summary = dropdown.querySelector('summary');
                const menu = dropdown._portalMenu;
                if (!summary || !menu || !dropdown.open) return;

                const rect = summary.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const gap = 8;
                const viewportPadding = 12;

                let top = rect.bottom + gap;
                if (top + menuRect.height > window.innerHeight - viewportPadding) {
                    top = rect.top - menuRect.height - gap;
                }
                top = Math.max(
                    viewportPadding,
                    Math.min(top, window.innerHeight - menuRect.height - viewportPadding)
                );

                let left = rect.right - menuRect.width;
                left = Math.max(
                    viewportPadding,
                    Math.min(left, window.innerWidth - menuRect.width - viewportPadding)
                );

                menu.style.top = top + 'px';
                menu.style.left = left + 'px';
                menu.style.right = 'auto';
            }

            function portalMenu(dropdown) {
                const menu = dropdown.querySelector('[data-dropdown-menu]');
                if (!menu || dropdown._portalMenu) return;

                const placeholder = document.createComment('room-dropdown-menu');
                menu.parentNode.insertBefore(placeholder, menu);

                dropdown._portalMenu = menu;
                dropdown._portalPlaceholder = placeholder;

                menu.classList.remove('hidden');
                menu.classList.add('fixed');
                menu.style.position = 'fixed';
                menu.style.zIndex = '9999';
                document.body.appendChild(menu);

                positionMenu(dropdown);
            }

            dropdowns.forEach(function(dropdown) {
                dropdown.addEventListener('toggle', function() {
                    if (dropdown.open) {
                        // Tutup dropdown kamar lain agar hanya satu menu yang terbuka.
                        dropdowns.forEach(function(otherDropdown) {
                            if (otherDropdown !== dropdown && otherDropdown.open) {
                                otherDropdown.open = false;
                                restoreMenu(otherDropdown);
                            }
                        });
                        portalMenu(dropdown);
                    } else {
                        restoreMenu(dropdown);
                    }
                });
            });

            // Klik di luar tombol dan menu akan menutup dropdown.
            document.addEventListener('click', function(event) {
                dropdowns.forEach(function(dropdown) {
                    const menu = dropdown._portalMenu;
                    const clickedTrigger = dropdown.contains(event.target);
                    const clickedMenu = menu && menu.contains(event.target);

                    if (!clickedTrigger && !clickedMenu) {
                        dropdown.open = false;
                        restoreMenu(dropdown);
                    }
                });
            });

            // Escape menutup semua dropdown yang sedang terbuka.
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    dropdowns.forEach(function(dropdown) {
                        dropdown.open = false;
                        restoreMenu(dropdown);
                    });
                }
            });

            window.addEventListener('resize', function() {
                dropdowns.forEach(positionMenu);
            });

            window.addEventListener('scroll', function() {
                dropdowns.forEach(positionMenu);
            }, true);
        });
    </script>

@endsection
