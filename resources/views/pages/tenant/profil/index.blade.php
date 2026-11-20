@extends('components.layouts.tenant.container')

@section('title', 'Profil Saya - nesaKost')

@section('content')
<section class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-7">
        <h1 class="text-2xl font-semibold text-slate-900">Profil Saya</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola informasi akun dan data pribadimu.</p>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Periksa kembali data yang dimasukkan.</p>
            <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $fullName = trim(($user->fname ?? '') . ' ' . ($user->lname ?? ''));
        $initials = collect(preg_split('/\s+/', $fullName))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    @endphp

    <div class="mb-6 flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-blue-900 text-xl font-semibold text-white">
            {{ $initials ?: 'P' }}
        </div>
        <div class="min-w-0">
            <h2 class="truncate text-lg font-semibold text-slate-900">{{ $fullName ?: 'Penyewa' }}</h2>
            <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
            <span class="mt-2 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-800">Penyewa</span>
        </div>
    </div>

    <form id="profile-form" action="{{ route('tenant.profil.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5">
                    <h3 class="font-semibold text-slate-900">Informasi Akun</h3>
                    <p class="mt-1 text-xs text-slate-500">Identitas dasar akun penyewa.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="fname" class="mb-1.5 block text-sm font-medium text-slate-700">Nama Depan <span class="text-red-500">*</span></label>
                        <input id="fname" name="fname" value="{{ old('fname', $user->fname) }}" required maxlength="100" disabled
                            class="profile-field w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 disabled:cursor-not-allowed disabled:text-slate-500">
                    </div>
                    <div>
                        <label for="lname" class="mb-1.5 block text-sm font-medium text-slate-700">Nama Belakang</label>
                        <input id="lname" name="lname" value="{{ old('lname', $user->lname) }}" maxlength="100" disabled
                            class="profile-field w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 disabled:cursor-not-allowed disabled:text-slate-500">
                    </div>
                </div>

                <div class="mt-5 border-t border-slate-100 pt-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-700">Alamat Email</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $user->email }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($user->email_verified_at)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Terverifikasi</span>
                            @else
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Belum terverifikasi</span>
                            @endif
                            @if (!($emailChange && $emailChange->expires_at->isFuture()))
                                <button id="edit-email" type="button" class="text-sm font-medium text-blue-900 hover:underline">Ubah alamat email</button>
                            @endif
                        </div>
                    </div>

                    @if ($emailChange && $emailChange->expires_at->isFuture())
                        <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <p class="text-sm font-medium text-blue-900">Verifikasi email baru</p>
                            <p class="mt-1 text-sm text-blue-800">Kode 6 digit telah dikirim ke <strong>{{ $emailChange->email }}</strong>. Kode berlaku 5 menit.</p>
                            <form id="email-verify-form" action="{{ route('tenant.profil.email.verify') }}" method="POST" class="mt-4 flex flex-col gap-3 sm:flex-row">
                                @csrf
                                <input name="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required placeholder="Masukkan 6 digit OTP"
                                    class="w-full rounded-lg border border-blue-200 bg-white px-3.5 py-2.5 text-sm tracking-widest sm:max-w-xs">
                                <button class="rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">Verifikasi Email</button>
                            </form>
                            <form id="email-cancel-form" action="{{ route('tenant.profil.email.cancel') }}" method="POST" class="mt-3">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-medium text-slate-600 underline">Batalkan perubahan email</button>
                            </form>
                        </div>
                    @else
                        <div id="email-edit-panel" class="mt-4 hidden rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <form id="email-send-form" action="{{ route('tenant.profil.email.send') }}" method="POST" class="space-y-3">
                                @csrf
                                <label for="new_email" class="block text-sm font-medium text-slate-700">Email Baru</label>
                                <input id="new_email" name="email" type="email" required maxlength="255" autocomplete="email" placeholder="nama@email.com"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm">
                                <p class="text-xs text-slate-500">Masukkan alamat email baru. Kami akan mengirim kode verifikasi ke alamat tersebut.</p>
                                <button class="rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">Kirim Kode Verifikasi</button>
                            </form>
                        </div>
                    @endif
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h3 class="mb-5 font-semibold text-slate-900">Informasi Pribadi</h3>
                <div class="space-y-4">
                    <div>
                        <label for="address" class="mb-1.5 block text-sm font-medium text-slate-700">Alamat</label>
                        <textarea id="address" name="address" rows="3" maxlength="1000" disabled
                            class="profile-field w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm disabled:cursor-not-allowed disabled:text-slate-500">{{ old('address', $user->address) }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="phone_number" class="mb-1.5 block text-sm font-medium text-slate-700">Nomor Telepon</label>
                            <input id="phone_number" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" maxlength="25" disabled
                                class="profile-field w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm disabled:cursor-not-allowed disabled:text-slate-500">
                        </div>
                        <div>
                            <label for="profession" class="mb-1.5 block text-sm font-medium text-slate-700">Pekerjaan</label>
                            <input id="profession" name="profession" value="{{ old('profession', $user->tenant?->profession) }}" maxlength="100" disabled
                                class="profile-field w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm disabled:cursor-not-allowed disabled:text-slate-500">
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h3 class="mb-5 font-semibold text-slate-900">Kontak Darurat</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="emergency_name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama Kontak Darurat</label>
                        <input id="emergency_name" name="emergency_name" value="{{ old('emergency_name', $user->tenant?->emergency_name) }}" maxlength="100" disabled
                            class="profile-field w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm disabled:cursor-not-allowed disabled:text-slate-500">
                    </div>
                    <div>
                        <label for="emergency_number" class="mb-1.5 block text-sm font-medium text-slate-700">Nomor Kontak Darurat</label>
                        <input id="emergency_number" name="emergency_number" value="{{ old('emergency_number', $user->tenant?->emergency_number) }}" maxlength="25" disabled
                            class="profile-field w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm disabled:cursor-not-allowed disabled:text-slate-500">
                    </div>
                </div>
            </section>
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <button id="cancel-edit" type="button" class="hidden rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
            <button id="save-profile" type="submit" class="hidden rounded-lg bg-blue-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">Simpan Perubahan</button>
            <button id="edit-profile" type="button" class="rounded-lg bg-blue-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">Edit Profil</button>
        </div>
    </form>


    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold text-slate-900">Keamanan Akun</h3>
                <p class="mt-1 text-sm text-slate-500">Kelola kata sandi untuk menjaga keamanan akunmu.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Password terlindungi
            </span>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <div>
                <p class="text-sm font-medium text-slate-800">Password</p>
                <p class="mt-1 text-xs text-slate-500">Perbarui password secara berkala untuk menjaga keamanan akun.</p>
            </div>
            <button id="toggle-password-form" type="button"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Ubah Password
            </button>
        </div>

        <form id="password-form" action="{{ route('tenant.profil.password.update') }}" method="POST"
            class="mt-5 hidden space-y-4 rounded-xl bg-slate-50 p-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700">Password Saat Ini</label>
                <div class="relative">
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 pr-12 text-sm">
                    <button type="button" data-password-toggle="current_password" aria-label="Tampilkan password saat ini" aria-pressed="false" class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-blue-900">
                        <i data-password-icon data-feather="eye" class="h-4 w-4"></i>
                    </button>
                </div>
                @error('current_password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password Baru</label>
                <div class="relative">
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 pr-12 text-sm">
                    <button type="button" data-password-toggle="password" aria-label="Tampilkan password baru" aria-pressed="false" class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-blue-900">
                        <i data-password-icon data-feather="eye" class="h-4 w-4"></i>
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-500">Minimal 8 karakter.</p>
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi Password Baru</label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required
                        class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 pr-12 text-sm">
                    <button type="button" data-password-toggle="password_confirmation" aria-label="Tampilkan konfirmasi password" aria-pressed="false" class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-blue-900">
                        <i data-password-icon data-feather="eye" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2 pt-1">
                <button id="cancel-password-form" type="button"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Batal</button>
                <button id="submit-password-form" type="submit"
                    class="rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">Simpan Password</button>
            </div>
        </form>
    </section>

</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fields = document.querySelectorAll('.profile-field');
    const editButton = document.getElementById('edit-profile');
    const saveButton = document.getElementById('save-profile');
    const cancelButton = document.getElementById('cancel-edit');
    const form = document.getElementById('profile-form');
    const emailButton = document.getElementById('edit-email');
    const emailPanel = document.getElementById('email-edit-panel');
    const originalValues = new Map();

    fields.forEach(field => originalValues.set(field.name, field.value));

    editButton?.addEventListener('click', () => {
        fields.forEach(field => field.disabled = false);
        editButton.classList.add('hidden');
        saveButton.classList.remove('hidden');
        cancelButton.classList.remove('hidden');
    });

    cancelButton?.addEventListener('click', () => {
        fields.forEach(field => {
            field.value = originalValues.get(field.name) ?? '';
            field.disabled = true;
        });
        saveButton.classList.add('hidden');
        cancelButton.classList.add('hidden');
        editButton.classList.remove('hidden');
    });

    form?.addEventListener('submit', () => {
        fields.forEach(field => field.disabled = false);
        saveButton.disabled = true;
        saveButton.textContent = 'Menyimpan...';
    });

    emailButton?.addEventListener('click', () => {
        emailPanel?.classList.toggle('hidden');
        document.getElementById('new_email')?.focus();
    });

    const togglePasswordButton = document.getElementById('toggle-password-form');
    const passwordForm = document.getElementById('password-form');
    const cancelPasswordButton = document.getElementById('cancel-password-form');
    const hasPasswordErrors = @json($errors->has('current_password') || $errors->has('password'));
    if (hasPasswordErrors) passwordForm?.classList.remove('hidden');
    const submitPasswordButton = document.getElementById('submit-password-form');

    togglePasswordButton?.addEventListener('click', () => {
        passwordForm?.classList.toggle('hidden');
        if (!passwordForm?.classList.contains('hidden')) {
            document.getElementById('current_password')?.focus();
        }
    });

    cancelPasswordButton?.addEventListener('click', () => {
        passwordForm?.reset();
        passwordForm?.classList.add('hidden');
    });

    passwordForm?.addEventListener('submit', () => {
        if (submitPasswordButton) {
            submitPasswordButton.disabled = true;
            submitPasswordButton.textContent = 'Menyimpan...';
        }
    });

    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';

            const icon = button.querySelector('[data-password-icon]');
            if (icon) {
                icon.setAttribute('data-feather', showing ? 'eye' : 'eye-off');
                if (window.feather) window.feather.replace({ width: 16, height: 16 });
            }

            button.setAttribute('aria-label', showing ? 'Tampilkan password' : 'Sembunyikan password');
            button.setAttribute('aria-pressed', String(!showing));
        });
    });
});
</script>
@endpush
