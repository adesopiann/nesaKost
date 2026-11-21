@extends('components.layouts.owner.container')
@section('title', 'Edit ART')
@section('main')
    <div class="mx-auto max-w-3xl space-y-5">
        <div><a href="{{ route('owner.housemaids.show', $housemaid) }}"
                class="text-sm font-medium text-blue-800 hover:underline">← Kembali ke Detail ART</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">Edit Data ART</h1>
            <p class="mt-1 text-sm text-slate-500">Perbarui identitas, gaji, dan seluruh penempatan cabang.</p>
        </div>
        <form method="POST" action="{{ route('owner.housemaids.update', $housemaid) }}"
            class="space-y-6 rounded-xl border border-slate-100 bg-white p-5 sm:p-6">@csrf @method('PUT')
            @include('pages.owner.housemaids._form')<div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><a
                    href="{{ route('owner.housemaids.show', $housemaid) }}"
                    class="rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-600">Batal</a><button
                    @disabled($branches->isEmpty())
                    class="rounded-lg bg-[#1D4E80] px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Simpan
                    Perubahan</button></div>
        </form>
    </div>
@endsection
