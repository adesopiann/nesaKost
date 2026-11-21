@extends('components.layouts.owner.container')
@section('title', 'Detail ART')
@section('main')
    @php $money = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.'); @endphp
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
        @endif
        <section
            class="flex flex-col gap-4 rounded-xl border border-slate-100 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
            <div><a href="{{ route('owner.housemaids.index') }}" class="text-sm text-blue-800 hover:underline">Data ART /
                    Detail ART</a>
                <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $housemaid->full_name }}</h1><span
                    class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $housemaid->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $housemaid->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('owner.housemaids.edit', $housemaid) }}"
                    class="rounded-lg bg-[#1D4E80] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Edit </a>
            </div>
        </section>
        <section class="grid gap-5 lg:grid-cols-[1.5fr_1fr]">
            <article class="rounded-xl border border-slate-100 bg-white p-5">
                <h2 class="text-lg font-bold text-slate-900">Informasi ART</h2>
                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-slate-500">Nama lengkap</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $housemaid->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Nomor telepon</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $housemaid->phone_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Gaji bulanan</dt>
                        <dd class="mt-1 text-xl font-bold text-[#1D4E80]">{{ $money($housemaid->salary) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Terdaftar</dt>
                        <dd class="mt-1 font-medium text-slate-800">
                            {{ $housemaid->created_at?->format('d M Y, H:i') ?? '-' }}</dd>
                    </div>
                </dl>
            </article>
            <article class="rounded-xl border border-slate-100 bg-white p-5">
                <h2 class="text-lg font-bold text-slate-900">Penempatan Cabang</h2>
                <div class="mt-4 space-y-2">
                    @forelse($housemaid->branches as $branch)
                        <div class="rounded-lg bg-indigo-50 px-3 py-3 text-sm font-medium text-slate-700">
                        {{ $branch->name }}</div>@empty<p class="rounded-lg bg-slate-50 p-3 text-sm text-slate-500">
                            Belum ada penempatan cabang.</p>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
@endsection
