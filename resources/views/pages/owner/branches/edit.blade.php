@extends('components.layouts.owner.container')
@section('main')

<div class="space-y-6 text-slate-800">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500"><a href="{{ route('owner.branches.index') }}" class="hover:text-blue-800">Data Cabang Kos</a> / <a href="{{ route('owner.branches.show', $branch) }}" class="hover:text-blue-800">{{ $branch->name }}</a> / <span class="font-semibold text-blue-800">Edit Cabang Kos</span></p>
        <h1 class="mt-3 text-3xl font-bold">Edit Cabang Kos</h1>
        <p class="mt-2 text-sm text-slate-500">Perbarui lokasi properti dan informasi operasional cabang kos yang dipilih.</p>
    </section>

    @include('pages.owner.branches.partials.flash')

    <section class="grid gap-4 rounded-xl bg-white p-6 shadow-sm md:grid-cols-[1fr_auto] md:items-center">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-bold">{{ $branch->name }}</h2>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">{{ $branch->status === 'active' ? 'Aktif Beroperasi' : 'Nonaktif' }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-500"><span class="font-semibold text-blue-800">{{ $branch->branch_code }}</span> · Terdaftar sejak {{ $branch->created_at?->format('d M Y') ?? '—' }}</p>
            <p class="mt-1 text-sm text-slate-600">Pemilik: {{ trim(($branch->owner->user->fname ?? '') . ' ' . ($branch->owner->user->lname ?? '')) }}</p>
        </div>
        <div class="grid grid-cols-2 gap-3 rounded-xl bg-indigo-50 p-4">
            <div><p class="text-xs uppercase tracking-wide text-slate-500">Kapasitas kamar</p><p class="mt-1 text-xl font-bold">{{ $totalRooms }} <span class="text-sm font-medium">kamar</span></p></div>
            <div><p class="text-xs uppercase tracking-wide text-slate-500">Tingkat okupansi</p><p class="mt-1 text-xl font-bold">{{ $totalRooms ? round($occupiedRooms / $totalRooms * 100) : 0 }}%</p></div>
        </div>
    </section>

    <form method="POST" action="{{ route('owner.branches.update', $branch) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <section class="space-y-6 rounded-xl bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold">Spesifikasi Formulir Entitas</h2>
                <span class="rounded-md bg-indigo-50 px-3 py-2 text-xs font-semibold text-blue-900">Entitas target: branch_dorm</span>
            </div>
            <div class="rounded-xl bg-blue-50 p-4 text-sm text-slate-600">
                <p class="font-semibold text-slate-800">Informasi integritas relasi basis data</p>
                <p class="mt-1">Kapasitas dihitung dari data kamar yang terdaftar. Mengubah informasi cabang tidak menghapus kamar atau transaksi yang sudah terhubung.</p>
            </div>
            @include('pages.owner.branches.partials.fields', ['owner' => $branch->owner, 'selectedFacilities' => $selectedFacilities])
            <div class="rounded-lg bg-slate-50 px-4 py-3 text-xs text-slate-500">Terakhir diperbarui {{ $branch->updated_at?->format('d M Y, H:i') ?? '—' }}</div>
        </section>
        <section class="flex justify-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <a href="{{ route('owner.branches.show', $branch) }}" class="rounded-lg bg-slate-100 px-5 py-3 text-sm font-semibold hover:bg-slate-200">Batal</a>
            <button type="submit" class="rounded-lg bg-blue-900 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-800">Simpan Perubahan</button>
        </section>
    </form>
</div>

@endsection
