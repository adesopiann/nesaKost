@extends('components.layouts.guest')
@section('title', 'Login')

@section('content')
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <main class="w-full max-w-md">
            <a href="{{ url('/') }}" class="mb-8 inline-flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#1D4E80] text-white"><i
                        data-feather="grid" class="h-5 w-5"></i></span>
                <span><span class="block text-lg font-bold text-slate-900">nesaKost</span><span
                        class="block text-xs text-slate-500">Platform Pencarian dan Manajemen Kost untuk Mahasiswa UNESA</span></span>
            </a>

            <section
                class="rounded-2xl border border-slate-100 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.07)] sm:p-8">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-[#315F96]"><i
                        data-feather="log-in" class="h-6 w-6"></i></div>
                <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Selamat datang kembali</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Login untuk melanjutkan ke akun nesaKost kamu.</p>

                @if (session('success'))
                    <div class="mt-5 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mt-5 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                    @csrf
                    <div><label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label><input
                            id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                            autocomplete="email"
                            class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                            placeholder="nama@email.com">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div><label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <div class="relative"><input id="password" name="password" type="password" required
                                autocomplete="current-password"
                                class="w-full rounded-lg border border-slate-200 px-3.5 py-3 pr-11 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                                placeholder="Masukkan password"><button type="button" data-password-toggle="password"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400"><i
                                    data-password-icon data-feather="eye" class="h-4 w-4"></i></button></div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember"
                            value="1" class="h-4 w-4 rounded border-slate-300 text-[#1D4E80] focus:ring-[#315F96]">
                        Ingat saya</label>
                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1D4E80] px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#173e66]">Login
                        <i data-feather="arrow-right" class="h-4 w-4"></i></button>
                </form>
                <p class="mt-6 text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('register.role') }}"
                        class="font-semibold text-[#315F96] hover:underline">Daftar sekarang</a></p>
            </section>
        </main>
    </div>
@endsection
