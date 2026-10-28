@extends('components.layouts.guest')
@section('title', 'Pilih Jenis Akun')

@section('content')
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-blue-100/70 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -right-20 h-72 w-72 rounded-full bg-indigo-100/70 blur-3xl">
        </div>

        <main class="relative w-full max-w-3xl">
            <a href="{{ url('/') }}" class="mb-8 inline-flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#1D4E80] text-white"><i
                        data-feather="grid" class="h-5 w-5"></i></span>
                <span><span class="block text-lg font-bold text-slate-900">eKost</span><span
                        class="block text-xs text-slate-500">Integrated Kos Management</span></span>
            </a>

            <section
                class="rounded-2xl border border-slate-100 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.07)] sm:p-10">
                <div class="mx-auto max-w-xl text-center">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-[#315F96]"><i
                            data-feather="user-plus" class="h-6 w-6"></i></span>
                    <p class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-[#315F96]">Buat akun eKost</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Kamu ingin bergabung
                        sebagai apa?</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-500">Pilih jenis akun. Pilihan ini akan menentukan role
                        akunmu di eKost.</p>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <a href="{{ route('register.create', ['role' => 'tenant']) }}"
                        class="group rounded-2xl border border-slate-200 p-5 transition hover:-translate-y-0.5 hover:border-[#315F96] hover:bg-blue-50/50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-300 sm:p-6">
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-[#315F96] group-hover:bg-[#315F96] group-hover:text-white"><i
                                data-feather="user" class="h-6 w-6"></i></span>
                        <h2 class="mt-5 text-lg font-bold text-slate-900">Saya Penyewa</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Cari kos, ajukan booking, dan pantau informasi sewa
                            serta pembayaran.</p>
                        <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-[#315F96]">Daftar
                            sebagai penyewa <i data-feather="arrow-right" class="h-4 w-4"></i></span>
                    </a>

                    <a href="{{ route('register.create', ['role' => 'owner']) }}"
                        class="group rounded-2xl border border-slate-200 p-5 transition hover:-translate-y-0.5 hover:border-[#315F96] hover:bg-blue-50/50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-300 sm:p-6">
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-[#315F96] group-hover:bg-[#315F96] group-hover:text-white"><i
                                data-feather="building" class="h-6 w-6"></i></span>
                        <h2 class="mt-5 text-lg font-bold text-slate-900">Saya Pemilik Kos</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Daftarkan properti dan kelola cabang, kamar, serta
                            operasional sewa.</p>
                        <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-[#315F96]">Daftar
                            sebagai pemilik <i data-feather="arrow-right" class="h-4 w-4"></i></span>
                    </a>
                </div>

                <p class="mt-8 text-center text-sm text-slate-500">Sudah punya akun? <a href="{{ route('login') }}"
                        class="font-semibold text-[#315F96] hover:underline">Login</a></p>
                <p class="mt-5 text-center text-xs text-slate-400">Akun manager dibuat oleh owner atau administrator, bukan
                    melalui pendaftaran publik.</p>
            </section>
        </main>
    </div>
@endsection
