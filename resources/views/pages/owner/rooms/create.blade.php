@extends('components.layouts.owner.container')
@section('main')
<div class="space-y-6 text-slate-800">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500"><a href="{{ route('owner.rooms.index') }}" class="hover:text-blue-800">Data Kamar</a> / <span class="font-semibold text-blue-800">Tambah Kamar</span></p>
        <h1 class="mt-3 text-3xl font-bold">Tambah Kamar</h1>
        <p class="mt-2 text-sm text-slate-500">Daftarkan kamar dan hubungkan dengan cabang kos yang sesuai.</p>
    </section>
    @include('pages.owner.rooms.partials.flash')
    <form method="POST" enctype="multipart/form-data" action="{{ route('owner.rooms.store') }}" class="space-y-6">
        @csrf
        <section class="space-y-6 rounded-xl bg-white p-6 shadow-sm md:p-8">
            <h2 class="border-l-4 border-blue-800 pl-3 text-lg font-bold">Formulir Registrasi Kamar</h2>
            @include('pages.owner.rooms.partials.fields', ['room' => null])
        </section>
        <section class="flex justify-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <a href="{{ route('owner.rooms.index') }}" class="rounded-lg bg-slate-100 px-5 py-3 text-sm font-semibold hover:bg-slate-200">Batal</a>
            <button type="submit" class="rounded-lg bg-blue-900 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-800" @disabled($branches->isEmpty())>Simpan Kamar</button>
        </section>
    </form>
</div>
@endsection
