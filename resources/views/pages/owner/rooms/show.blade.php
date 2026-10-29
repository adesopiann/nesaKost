@extends('components.layouts.owner.container')
@section('main')
<div class="space-y-6 text-slate-800">
    @include('pages.owner.rooms.partials.flash')
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500"><a href="{{ route('owner.rooms.index') }}" class="hover:text-blue-800">Data Kamar</a> / <span class="font-semibold text-blue-800">Detail Kamar</span></p>
        <div class="mt-3 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-3xl font-bold">{{ $room->room_code }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <span class="font-semibold text-blue-800">{{ $room->type }}</span><span>•</span>
                    <span>{{ $room->branch?->name ?? 'Cabang tidak ditemukan' }}</span><span>•</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">{{ ucfirst($room->status) }}</span>
                </div>
            </div>
            <a href="{{ route('owner.rooms.edit', $room->id) }}" class="rounded-lg bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">Edit Kamar</a>
        </div>
    </section>
    <section class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-bold">Informasi Kamar</h2>
            @if($room->images->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach($room->images as $image)
                        <div class="relative">
                            <img src="{{ asset('storage/'.$image->image_path) }}" alt="Foto kamar {{ $room->room_code }}" class="h-44 w-full rounded-xl object-cover">
                            @if($image->is_primary)
                                <span class="absolute left-2 top-2 rounded-full bg-blue-900 px-2 py-1 text-xs font-semibold text-white">Foto utama</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @elseif($room->image_path)
                <img src="{{ asset('storage/'.$room->image_path) }}" alt="Foto kamar {{ $room->room_code }}" class="max-h-[420px] w-full rounded-xl object-cover">
                <p class="text-xs text-slate-500">Foto ini berasal dari data lama. Unggah foto baru melalui menu Edit untuk mulai menggunakan galeri.</p>
            @else
                <div class="flex h-56 items-center justify-center rounded-xl bg-slate-100 text-sm text-slate-400">Foto kamar belum tersedia</div>
            @endif
            <div class="grid gap-5 sm:grid-cols-2">
                <div><p class="text-sm font-semibold text-slate-500">Kode kamar</p><p class="mt-2 font-medium">{{ $room->room_code }}</p></div>
                <div><p class="text-sm font-semibold text-slate-500">Cabang</p><p class="mt-2 font-medium">{{ $room->branch?->name ?? '—' }}</p></div>
                <div><p class="text-sm font-semibold text-slate-500">Tipe kamar</p><p class="mt-2 font-medium">{{ $room->type }}</p></div>
                <div><p class="text-sm font-semibold text-slate-500">Harga sewa per bulan</p><p class="mt-2 text-xl font-bold">Rp {{ number_format((float) $room->monthly_price, 0, ',', '.') }}</p></div>
                <div class="sm:col-span-2"><p class="text-sm font-semibold text-slate-500">Deskripsi</p><p class="mt-2 whitespace-pre-line leading-relaxed">{{ $room->description ?: 'Belum ada deskripsi.' }}</p></div>
            </div>
        </div>
        <aside class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold">Ringkasan</h2>
            <dl class="mt-4 space-y-4 text-sm">
                <div><dt class="text-slate-500">ID kamar</dt><dd class="mt-1 font-semibold">{{ $room->id }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="mt-1 font-semibold">{{ ucfirst($room->status) }}</dd></div>
                <div><dt class="text-slate-500">Dibuat</dt><dd class="mt-1 font-semibold">{{ $room->created_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Terakhir diperbarui</dt><dd class="mt-1 font-semibold">{{ $room->updated_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
            </dl>
            <a href="{{ route('owner.rooms.index') }}" class="mt-6 inline-flex w-full justify-center rounded-lg bg-slate-100 px-4 py-3 text-sm font-semibold hover:bg-slate-200">Kembali ke daftar</a>
        </aside>
    </section>
</div>
@endsection
