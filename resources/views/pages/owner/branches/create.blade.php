@extends('components.layouts.owner.container')
@section('main')

<div class="space-y-6 text-slate-800">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500"><a href="{{ route('owner.branches.index') }}" class="hover:text-blue-800">Data Cabang Kos</a> / <span class="font-semibold text-blue-800">Tambah Cabang Kos</span></p>
        <h1 class="mt-3 text-3xl font-bold">Tambah Cabang Kos</h1>
        <p class="mt-2 text-sm text-slate-500">Kelola cabang kos, lokasi properti, dan informasi operasional setiap cabang.</p>
    </section>

    @include('pages.owner.branches.partials.flash')

    <form method="POST" action="{{ route('owner.branches.store') }}" class="space-y-6">
        @csrf
        <section class="space-y-6 rounded-xl bg-white p-6 shadow-sm md:p-8">
            <h2 class="border-l-4 border-blue-800 pl-3 text-lg font-bold">Formulir Registrasi Cabang</h2>
            @include('pages.owner.branches.partials.fields', ['branch' => null, 'selectedFacilities' => []])
        </section>
        <section class="flex justify-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <a href="{{ route('owner.branches.index') }}" class="rounded-lg bg-slate-100 px-5 py-3 text-sm font-semibold hover:bg-slate-200">Batal</a>
            <button type="submit" class="rounded-lg bg-blue-900 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-800">Simpan Cabang</button>
        </section>
    </form>
</div>

@endsection
