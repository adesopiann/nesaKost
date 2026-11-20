@extends('components.layouts.guest')
@section('title', 'Verifikasi Email')
@section('content')
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <main class="w-full max-w-md">
            <a href="{{ url('/') }}" class="mb-8 inline-flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#1D4E80] text-white"><i
                        data-feather="grid" class="h-5 w-5"></i></span>
                <span><span class="block text-lg font-bold text-slate-900">nesaKost</span><span
                        class="block text-xs text-slate-500">Platform Pencarian dan Manajemen Kost untuk Mahasiswa
                        UNESA</span></span>
            </a>
            <section
                class="rounded-2xl border border-slate-100 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.07)] sm:p-8">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-[#315F96]"><i
                        data-feather="mail" class="h-6 w-6"></i></div>
                <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Verifikasi alamat email</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Kami mengirimkan kode verifikasi 6 digit ke <strong
                        class="break-all text-slate-700">{{ $email }}</strong>. Masukkan kode tersebut untuk
                    mengaktifkan akun kamu. Periksa Inbox Gmail. Jika tidak ada, periksa folder Spam.</p>
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
                <form method="POST" action="{{ route('email.verification.verify') }}" class="mt-6 space-y-5">
                    @csrf
                    <div><label for="otp" class="mb-1.5 block text-sm font-medium text-slate-700">Kode
                            verifikasi</label><input id="otp" name="otp" type="text" inputmode="numeric"
                            pattern="[0-9]{6}" maxlength="6" minlength="6" autocomplete="one-time-code" required autofocus
                            value="{{ old('otp') }}"
                            class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-center text-xl font-semibold tracking-[0.5em] outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                            placeholder="000000"></div>
                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1D4E80] px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#173e66]">Verifikasi
                        Email <i data-feather="arrow-right" class="h-4 w-4"></i></button>
                </form>
                <form method="POST" action="{{ route('email.verification.send') }}" class="mt-4">@csrf<button
                        type="submit"
                        class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Kirim
                        ulang kode</button></form>
                <p class="mt-6 text-center text-sm text-slate-500">Sudah punya akun terverifikasi? <a
                        href="{{ route('login') }}" class="font-semibold text-[#315F96] hover:underline">Kembali ke
                        login</a></p>
            </section>
        </main>
    </div>
@endsection
