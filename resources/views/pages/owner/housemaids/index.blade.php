@extends('components.layouts.owner.container')
@section('title', 'Data Asisten Rumah Tangga')
@section('main')
    @php
        $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $statusLabels = ['active' => 'Aktif', 'inactive' => 'Nonaktif'];
    @endphp
    <div class="space-y-6">
        <section
            class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Data Asisten Rumah Tangga</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">Kelola data, gaji bulanan, dan penempatan OB di seluruh
                    cabang.</p>
            </div>
            <a href="{{ route('owner.housemaids.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">+
                Tambah OB
            </a>
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
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <a href="{{ route('owner.housemaids.show', $item) }}"
                                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Detail
                                        </a>

                                        <details class="relative text-left">
                                            <summary
                                                class="inline-flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                                Lainnya
                                                <i data-feather="chevron-down" class="h-3.5 w-3.5"></i>
                                            </summary>
                                            <div data-dropdown-menu
                                                class="z-[9999] hidden w-56 rounded-xl border border-slate-200 bg-white p-2 text-left shadow-xl">
                                                <a href="{{ route('owner.housemaids.edit', $item) }}"
                                                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                                    <i data-feather="edit-2" class="h-4 w-4"></i>
                                                    Edit data OB
                                                </a>

                                                <div class="my-1 border-t border-slate-100"></div>
                                                <p
                                                    class="px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                    Ubah status
                                                </p>
                                                <form method="POST" action="{{ route('owner.housemaids.status', $item) }}"
                                                    data-confirm-modal
                                                    data-confirm-variant="warning"
                                                    data-confirm-title="{{ $item->status === 'active' ? 'Nonaktifkan OB' : 'Aktifkan OB' }}"
                                                    data-confirm-message="{{ $item->status === 'active' ? 'OB ini akan dinonaktifkan dan seluruh penempatan cabangnya akan dilepaskan. Lanjutkan?' : 'OB ini akan diaktifkan. Pastikan penempatan cabangnya sudah tersedia. Lanjutkan?' }}"
                                                    data-confirm-accept="{{ $item->status === 'active' ? 'Ya, nonaktifkan' : 'Ya, aktifkan' }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status"
                                                        value="{{ $item->status === 'active' ? 'inactive' : 'active' }}">
                                                    <button type="submit"
                                                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                                        <i data-feather="power" class="h-4 w-4"></i>
                                                        {{ $item->status === 'active' ? 'Nonaktifkan OB' : 'Aktifkan OB' }}
                                                    </button>
                                                </form>

                                                @if ($item->status === 'inactive')
                                                    <div class="my-1 border-t border-slate-100"></div>
                                                    <form method="POST"
                                                        action="{{ route('owner.housemaids.destroy', $item) }}"
                                                        data-confirm-modal
                                                        data-confirm-variant="danger"
                                                        data-confirm-title="Hapus data OB"
                                                        data-confirm-message="Data OB ini akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan. Lanjutkan?"
                                                        data-confirm-accept="Ya, hapus permanen">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                                            <i data-feather="trash-2" class="h-4 w-4"></i>
                                                            Hapus OB
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </details>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('details.relative');

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
                if (!summary || !menu) return;

                const rect = summary.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const gap = 8;
                const viewportPadding = 12;

                let top = rect.bottom + gap;
                if (top + menuRect.height > window.innerHeight - viewportPadding) {
                    top = rect.top - menuRect.height - gap;
                }
                top = Math.max(viewportPadding, Math.min(top, window.innerHeight - menuRect.height -
                    viewportPadding));

                let left = rect.right - menuRect.width;
                left = Math.max(viewportPadding, Math.min(left, window.innerWidth - menuRect.width -
                    viewportPadding));

                menu.style.top = top + 'px';
                menu.style.left = left + 'px';
                menu.style.right = 'auto';
            }

            function portalMenu(dropdown) {
                const menu = dropdown.querySelector('[data-dropdown-menu]');
                if (!menu || dropdown._portalMenu) return;

                const placeholder = document.createComment('housemaid-dropdown-menu');
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
                        portalMenu(dropdown);
                    } else {
                        restoreMenu(dropdown);
                    }
                });
            });

            document.addEventListener('click', function(event) {
                dropdowns.forEach(function(dropdown) {
                    const menu = dropdown._portalMenu;
                    if (!dropdown.contains(event.target) && !(menu && menu.contains(event
                            .target))) {
                        dropdown.open = false;
                        restoreMenu(dropdown);
                    }
                });
            });

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
