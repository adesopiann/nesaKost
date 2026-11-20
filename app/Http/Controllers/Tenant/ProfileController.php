<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\EmailChangeOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('tenant');
        $emailChange = EmailChangeOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->first();

        return view('pages.tenant.profil.index', compact('user', 'emailChange'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'fname' => ['required', 'string', 'max:100'],
            'lname' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone_number' => ['nullable', 'string', 'max:25'],
            'profession' => ['nullable', 'string', 'max:100'],
            'emergency_name' => ['nullable', 'string', 'max:100'],
            'emergency_number' => ['nullable', 'string', 'max:25'],
        ], [
            'fname.required' => 'Nama depan wajib diisi.',
        ]);

        DB::transaction(function () use ($user, $data): void {
            $user->forceFill([
                'fname' => $data['fname'],
                'lname' => $data['lname'] ?? null,
                'address' => $data['address'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
            ])->save();

            $user->tenant()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'profession' => $data['profession'] ?? null,
                    'emergency_name' => $data['emergency_name'] ?? null,
                    'emergency_number' => $data['emergency_number'] ?? null,
                ]
            );
        });

        return redirect()->route('tenant.profil.index')->with('success', 'Profil berhasil diperbarui.');
    }


    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Masukkan password saat ini.',
            'password.required' => 'Masukkan password baru.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return redirect()->route('tenant.profil.index')
            ->with('success', 'Password berhasil diperbarui.');
    }

    public function sendEmailOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
        ], [
            'email.unique' => 'Alamat email tersebut sudah digunakan akun lain.',
            'email.email' => 'Masukkan alamat email yang valid.',
        ]);

        $user = $request->user();
        if (mb_strtolower($data['email']) === mb_strtolower($user->email)) {
            throw ValidationException::withMessages([
                'email' => 'Email baru harus berbeda dari email yang sedang digunakan.',
            ]);
        }

        $plainOtp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $data, $plainOtp): void {
            $record = EmailChangeOtp::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if ($record && $record->verified_at === null && $record->last_sent_at->gt(now()->subSeconds(60))) {
                $remaining = max(1, 60 - (int) $record->last_sent_at->diffInSeconds(now()));
                throw ValidationException::withMessages([
                    'email' => "Tunggu {$remaining} detik sebelum meminta kode baru.",
                ]);
            }

            EmailChangeOtp::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'email' => $data['email'],
                    'otp_hash' => Hash::make($plainOtp),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(5),
                    'last_sent_at' => now(),
                    'verified_at' => null,
                ]
            );
        });

        Mail::raw(
            "Kode verifikasi perubahan email eKost kamu adalah: {$plainOtp}\n\nKode berlaku selama 5 menit. Jangan bagikan kode ini kepada siapa pun.",
            function ($message) use ($data): void {
                $message->to($data['email'])->subject('Verifikasi Perubahan Email eKost');
            }
        );

        return redirect()->route('tenant.profil.index')
            ->with('success', 'Kode verifikasi telah dikirim ke email baru.');
    }

    public function verifyEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Masukkan kode verifikasi.',
            'otp.digits' => 'Kode verifikasi harus terdiri dari 6 digit.',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $data): void {
            $record = EmailChangeOtp::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $record || $record->verified_at || $record->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'otp' => 'Kode tidak ditemukan atau sudah kedaluwarsa. Silakan minta kode baru.',
                ]);
            }

            if ($record->attempts >= 5) {
                throw ValidationException::withMessages([
                    'otp' => 'Batas 5 percobaan telah tercapai. Silakan minta kode baru.',
                ]);
            }

            if (! Hash::check($data['otp'], $record->otp_hash)) {
                $record->increment('attempts');
                throw ValidationException::withMessages([
                    'otp' => 'Kode verifikasi salah.',
                ]);
            }

            // Cek ulang keunikan di dalam transaksi sebelum mengganti email.
            if (\App\Models\User::query()->where('email', $record->email)->whereKeyNot($user->id)->exists()) {
                throw ValidationException::withMessages([
                    'otp' => 'Email tersebut sudah digunakan akun lain. Silakan mulai ulang dengan email berbeda.',
                ]);
            }

            $user->forceFill([
                'email' => $record->email,
                'email_verified_at' => now(),
            ])->save();

            $record->forceFill(['verified_at' => now()])->save();
        });

        return redirect()->route('tenant.profil.index')
            ->with('success', 'Alamat email berhasil diperbarui dan diverifikasi.');
    }

    public function cancelEmailChange(Request $request): RedirectResponse
    {
        EmailChangeOtp::query()->where('user_id', $request->user()->id)->delete();

        return redirect()->route('tenant.profil.index')
            ->with('success', 'Permintaan perubahan email dibatalkan. Email lama tetap digunakan.');
    }
}
