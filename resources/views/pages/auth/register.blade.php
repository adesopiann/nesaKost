@extends('components.layouts.guest')
@section('title', $role === 'owner' ? 'Daftar Pemilik Kos' : 'Daftar Penyewa')

@section('content')
    @php
        $isOwner = $role === 'owner';
        $roleLabel = $isOwner ? 'Pemilik Kos' : 'Penyewa';
    @endphp
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <main class="w-full max-w-2xl">
            <a href="{{ url('/') }}" class="mb-7 inline-flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#1D4E80] text-white"><i
                        data-feather="grid" class="h-5 w-5"></i></span>
                <span><span class="block text-lg font-bold text-slate-900">eKost</span><span
                        class="block text-xs text-slate-500">Integrated Kos Management</span></span>
            </a>

            <section
                class="rounded-2xl border border-slate-100 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.07)] sm:p-9">
                <a href="{{ route('register.role') }}"
                    class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-[#315F96]"><i
                        data-feather="arrow-left" class="h-4 w-4"></i> Ganti jenis akun</a>
                <div class="mt-5 flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-[#315F96]"><i
                            data-feather="{{ $isOwner ? 'building' : 'user' }}" class="h-6 w-6"></i></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[#315F96]">Pendaftaran
                            {{ $roleLabel }}</p>
                        <h1 class="mt-1 text-2xl font-bold text-slate-900">Buat akun baru</h1>
                        <p class="mt-1 text-sm text-slate-500">Lengkapi informasi berikut untuk mulai menggunakan eKost.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-lg border border-red-100 bg-red-50 p-4 text-sm text-red-700">
                        <p class="font-semibold">Periksa kembali data yang kamu masukkan.</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="mt-7 space-y-5">
                    @csrf
                    <input type="hidden" name="role" value="{{ $role }}">

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="fname" class="mb-1.5 block text-sm font-medium text-slate-700">Nama
                                depan</label><input id="fname" name="fname" value="{{ old('fname') }}" required
                                autocomplete="given-name"
                                class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                                placeholder="Nama depan">
                            @error('fname')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div><label for="lname" class="mb-1.5 block text-sm font-medium text-slate-700">Nama
                                belakang</label><input id="lname" name="lname" value="{{ old('lname') }}" required
                                autocomplete="family-name"
                                class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                                placeholder="Nama belakang">
                            @error('lname')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div><label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label><input
                            id="email" name="email" type="email" value="{{ old('email') }}" required
                            autocomplete="email"
                            class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                            placeholder="nama@email.com">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div><label for="phone_number" class="mb-1.5 block text-sm font-medium text-slate-700">Nomor
                            telepon</label><input id="phone_number" name="phone_number" type="tel"
                            value="{{ old('phone_number') }}" required autocomplete="tel"
                            class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                            placeholder="08xxxxxxxxxx">
                        @error('phone_number')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div><label for="address" class="mb-1.5 block text-sm font-medium text-slate-700">Alamat</label>
                        <textarea id="address" name="address" rows="2" required autocomplete="street-address"
                            class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                            placeholder="Alamat lengkap">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @unless ($isOwner)
                        <div class="border-t border-slate-100 pt-5">
                            <h2 class="text-sm font-semibold text-slate-800">Informasi penyewa</h2>
                            <p class="mt-1 text-xs text-slate-500">Lengkapi pekerjaan/status dan kontak darurat.</p>
                        </div>
                        <div><label for="profession" class="mb-1.5 block text-sm font-medium text-slate-700">Pekerjaan /
                                status</label><input id="profession" name="profession" value="{{ old('profession') }}" required
                                class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                                placeholder="Contoh: Mahasiswa">
                            @error('profession')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label for="emergency_name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama kontak
                                    darurat</label><input id="emergency_name" name="emergency_name"
                                    value="{{ old('emergency_name') }}" required
                                    class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                                    placeholder="Nama keluarga / kerabat">
                                @error('emergency_name')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div><label for="emergency_number" class="mb-1.5 block text-sm font-medium text-slate-700">Nomor
                                    kontak darurat</label><input id="emergency_number" name="emergency_number" type="tel"
                                    value="{{ old('emergency_number') }}" required
                                    class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                                    placeholder="08xxxxxxxxxx">
                                @error('emergency_number')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endunless

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="password"
                                class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                            <div class="relative"><input id="password" name="password" type="password" required
                                    minlength="8" autocomplete="new-password"
                                    class="w-full rounded-lg border border-slate-200 px-3.5 py-3 pr-11 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                                    placeholder="Minimal 8 karakter"><button type="button"
                                    data-password-toggle="password"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400"><i
                                        data-password-icon data-feather="eye" class="h-4 w-4"></i></button></div>
                            @error('password')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div><label for="password_confirmation"
                                class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi password</label><input
                                id="password_confirmation" name="password_confirmation" type="password" required
                                minlength="8" autocomplete="new-password"
                                class="w-full rounded-lg border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-[#315F96] focus:ring-2 focus:ring-blue-100"
                                placeholder="Ulangi password"></div>
                    </div>

                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#1D4E80] px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#173e66]">Buat
                        Akun <i data-feather="arrow-right" class="h-4 w-4"></i></button>
                </form>
                <p class="mt-6 text-center text-sm text-slate-500">Sudah punya akun? <a href="{{ route('login') }}"
                        class="font-semibold text-[#315F96] hover:underline">Login</a></p>
            </section>
        </main>
    </div>
@endsection
