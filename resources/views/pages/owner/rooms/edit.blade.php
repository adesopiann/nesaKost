@extends('components.layouts.owner.container')
@section('main')
<div class="space-y-6 text-slate-800">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500"><a href="{{ route('owner.rooms.index') }}" class="hover:text-blue-800">Data Kamar</a> / <a href="{{ route('owner.rooms.show', $room->id) }}" class="hover:text-blue-800">{{ $room->room_code }}</a> / <span class="font-semibold text-blue-800">Edit Kamar</span></p>
        <h1 class="mt-3 text-3xl font-bold">Edit Kamar</h1>
        <p class="mt-2 text-sm text-slate-500">Perbarui informasi kamar tanpa mengubah struktur data cabang.</p>
    </section>
    @include('pages.owner.rooms.partials.flash')
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold">{{ $room->room_code }}</h2>
                <p class="mt-1 text-sm text-slate-500">Cabang: {{ $room->branch->name }}</p>
            </div>
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-blue-900">Entitas target: room</span>
        </div>
    </section>
    <form method="POST" enctype="multipart/form-data" action="{{ route('owner.rooms.update', $room->id) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <section class="space-y-6 rounded-xl bg-white p-6 shadow-sm md:p-8">
            @include('pages.owner.rooms.partials.fields', ['room' => $room])
        </section>
        <section class="flex justify-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <a href="{{ route('owner.rooms.show', $room->id) }}" class="rounded-lg bg-slate-100 px-5 py-3 text-sm font-semibold hover:bg-slate-200">Batal</a>
            <button type="submit" class="rounded-lg bg-blue-900 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-800">Simpan Perubahan</button>
        </section>
    </form>
</div>
@endsection
