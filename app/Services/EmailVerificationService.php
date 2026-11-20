<?php

namespace App\Services;

use App\Mail\EmailVerificationOtpMail;
use App\Models\EmailVerificationOtp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public const OTP_LENGTH = 6;
    public const OTP_TTL_MINUTES = 5;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;

    /** Create and email a new OTP. The plain code is never stored in the database. */
    public function send(User $user): void
    {
        if ($user->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'email' => 'Alamat email akun ini sudah terverifikasi.',
            ]);
        }

        $plainOtp = null;

        DB::transaction(function () use ($user, &$plainOtp): void {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->email_verified_at !== null) {
                throw ValidationException::withMessages([
                    'email' => 'Alamat email akun ini sudah terverifikasi.',
                ]);
            }

            $otpRecord = EmailVerificationOtp::query()
                ->where('user_id', $lockedUser->id)
                ->lockForUpdate()
                ->first();

            if (
                $otpRecord !== null
                && $otpRecord->verified_at === null
                && $otpRecord->last_sent_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))
            ) {
                // Hitung waktu yang sudah berlalu dari waktu pengiriman terakhir.
                // Urutan diff penting: last_sent_at adalah waktu lampau, now() adalah waktu sekarang.
                $elapsedSeconds = (int) $otpRecord->last_sent_at->diffInSeconds(now());
                $remaining = max(1, self::RESEND_COOLDOWN_SECONDS - $elapsedSeconds);

                throw ValidationException::withMessages([
                    'otp' => "Tunggu {$remaining} detik lagi sebelum meminta kode baru.",
                ]);
            }

            $plainOtp = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

            EmailVerificationOtp::query()->updateOrCreate(
                ['user_id' => $lockedUser->id],
                [
                    'otp_hash' => Hash::make($plainOtp),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                    'last_sent_at' => now(),
                    'verified_at' => null,
                ]
            );
        });

        // Kirim setelah transaksi commit. Jika SMTP gagal, akun dan OTP tetap
        // tersimpan sehingga pengguna dapat meminta pengiriman ulang.
        Mail::to($user->email)->send(new EmailVerificationOtpMail($plainOtp));
    }

    /** Verify the code and mark the user's email as verified. */
    public function verify(User $user, string $plainOtp): void
    {
        $errorMessage = DB::transaction(function () use ($user, $plainOtp): ?string {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedUser->email_verified_at !== null) return 'Alamat email akun ini sudah terverifikasi.';
            $otpRecord = EmailVerificationOtp::query()->where('user_id', $lockedUser->id)->lockForUpdate()->first();
            if ($otpRecord === null || $otpRecord->verified_at !== null) return 'Kode verifikasi tidak ditemukan. Silakan minta kode baru.';
            if ($otpRecord->expires_at->isPast()) return 'Kode verifikasi sudah kedaluwarsa. Silakan minta kode baru.';
            if ($otpRecord->attempts >= self::MAX_ATTEMPTS) return 'Batas percobaan kode telah tercapai. Silakan minta kode baru.';
            if (! Hash::check($plainOtp, $otpRecord->otp_hash)) {
                $otpRecord->increment('attempts');

                $remainingAttempts = self::MAX_ATTEMPTS - (int) $otpRecord->fresh()->attempts;

                if ($remainingAttempts <= 0) {
                    return 'Kode verifikasi salah. Batas 5 percobaan telah tercapai. Silakan minta kode baru.';
                }

                return "Kode verifikasi salah. Sisa {$remainingAttempts} percobaan.";
            }
            $lockedUser->forceFill(['email_verified_at' => now()])->save();
            $otpRecord->forceFill(['verified_at' => now()])->save();
            return null;
        });
        if ($errorMessage !== null) {
            throw ValidationException::withMessages(['otp' => $errorMessage]);
        }
    }
}
