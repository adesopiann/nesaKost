@extends('components.layouts.owner.container')
@section('main')
    <div class="space-y-6 text-slate-800">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}
            </div>
        @endif
        <section
            class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Data Fasilitas</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">Kelola fasilitas cabang dan fasilitas kamar secara terpisah.
                </p>
            </div>
        </section>
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold">Tambah Fasilitas</h2>
            <form method="POST" action="{{ route('owner.facilities.store') }}"
                class="mt-4 grid gap-4 md:grid-cols-[1fr_1fr_2fr_auto]">@csrf
                <div><label class="mb-1 block text-sm font-semibold" for="facility_name">Nama fasilitas</label><input
                        id="facility_name" name="facility_name" value="{{ old('facility_name') }}" required maxlength="100"
                        placeholder="Contoh: Wi-Fi" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div><label class="mb-1 block text-sm font-semibold" for="facility_type">Jenis fasilitas</label><select
                        id="facility_type" name="facility_type" required
                        class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        <option value="branch" @selected(old('facility_type', 'branch') === 'branch')>Fasilitas Cabang</option>
                        <option value="room" @selected(old('facility_type') === 'room')>Fasilitas Kamar</option>
                    </select></div>
                <div><label class="mb-1 block text-sm font-semibold" for="description">Deskripsi</label><input
                        id="description" name="description" value="{{ old('description') }}" maxlength="2000"
                        placeholder="Keterangan singkat fasilitas (opsional)"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm"></div>
                <div class="flex items-end"><button
                        class="w-full rounded-lg bg-blue-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Tambah</button>
                </div>
            </form>
        </section>
        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <form method="GET" action="{{ route('owner.facilities.index') }}" class="flex flex-wrap gap-2"><select
                        name="type" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Semua jenis</option>
                        <option value="branch" @selected(request('type') === 'branch')>Fasilitas Cabang</option>
                        <option value="room" @selected(request('type') === 'room')>Fasilitas Kamar</option>
                    </select><input name="q" value="{{ $search }}" placeholder="Cari fasilitas..."
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm"><button
                        class="rounded-lg bg-blue-900 hover:bg-blue-800 px-4 py-2 text-sm font-semibold text-white">Cari</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="bg-indigo-50 text-xs uppercase text-slate-600">
                        <tr>
                            <th class="px-5 py-4">Nama fasilitas</th>
                            <th class="px-5 py-4">Jenis</th>
                            <th class="px-5 py-4">Deskripsi</th>
                            <th class="px-5 py-4 text-center">Digunakan di cabang</th>
                            <th class="px-5 py-4 text-center">Digunakan di kamar</th>
                            <th class="px-5 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($facilities as $facility)
                            <tr>
                                <td class="px-5 py-4 font-semibold">{{ $facility->facility_name }}</td>
                                <td class="px-5 py-4"><span
                                        class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $facility->facility_type === 'branch' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $facility->facility_type === 'branch' ? 'Cabang' : 'Kamar' }}</span>
                                </td>
                                <td class="px-5 py-4 text-slate-500">{{ $facility->description ?: '—' }}</td>
                                <td class="px-5 py-4 text-center">{{ $facility->branches_count }}</td>
                                <td class="px-5 py-4 text-center">{{ $facility->rooms_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <details class="relative">
                                            <summary
                                                class="cursor-pointer rounded-md bg-slate-100 px-3 py-2 text-xs font-semibold">
                                                Edit</summary>
                                            <div
                                                class="absolute right-0 z-20 mt-2 w-72 rounded-lg border border-slate-200 bg-white p-3 shadow-xl">
                                                <form method="POST"
                                                    action="{{ route('owner.facilities.update', $facility) }}"
                                                    class="space-y-2">@csrf @method('PUT')<select name="facility_type"
                                                        class="w-full rounded border border-slate-200 px-2 py-2 text-sm">
                                                        <option value="branch" @selected($facility->facility_type === 'branch')>Fasilitas Cabang
                                                        </option>
                                                        <option value="room" @selected($facility->facility_type === 'room')>Fasilitas Kamar
                                                        </option>
                                                    </select><input name="facility_name"
                                                        value="{{ $facility->facility_name }}" required maxlength="100"
                                                        class="w-full rounded border border-slate-200 px-2 py-2 text-sm">
                                                    <textarea name="description" rows="2" class="w-full rounded border border-slate-200 px-2 py-2 text-sm"
                                                        placeholder="Deskripsi">{{ $facility->description }}</textarea><button
                                                        class="w-full rounded bg-blue-900 px-3 py-2 text-xs font-semibold text-white">Simpan
                                                        perubahan</button>
                                                </form>
                                            </div>
                                        </details>
                                        <form method="POST" action="{{ route('owner.facilities.destroy', $facility) }}"
                                            data-confirm-modal
                                            data-confirm-variant="danger"
                                            data-confirm-title="Hapus fasilitas"
                                            data-confirm-message="Fasilitas {{ $facility->facility_name }} akan dihapus dari seluruh relasi cabang dan kamar. Data cabang dan kamar tidak akan dihapus. Lanjutkan?"
                                            data-confirm-accept="Ya, hapus fasilitas">
                                            @csrf @method('DELETE')<button
                                                class="rounded-md bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty<tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada fasilitas.
                                    Tambahkan fasilitas melalui formulir di atas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $facilities->links() }}</div>
        </section>
    </div>

    <script>
        document.addEventListener('click', function(event) {
            document.querySelectorAll('details[open]').forEach(function(dropdown) {
                if (!dropdown.contains(event.target)) {
                    dropdown.removeAttribute('open');
                }
            });
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('details[open]').forEach(function(dropdown) {
                    dropdown.removeAttribute('open');
                });
            }
        });
    </script>
@endsection
