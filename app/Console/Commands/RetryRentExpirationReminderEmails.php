<?php

namespace App\Console\Commands;

use App\Mail\RentExpirationReminderMail;
use App\Models\RentExpirationReminderEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RetryRentExpirationReminderEmails extends Command
{
    protected $signature = 'ekost:retry-rent-expiration-emails
        {--limit=50 : Jumlah maksimum email yang diproses}
        {--force : Abaikan jeda retry untuk pengujian manual}';

    protected $description = 'Mencoba ulang email pengingat masa sewa yang belum berhasil dikirim.';

    private const MAX_ATTEMPTS = 3;
    private const RETRY_DELAY_MINUTES = 5;
    private const STALE_PROCESS_MINUTES = 10;

    public function handle(): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);

        if ($limit === false || $limit < 1 || $limit > 500) {
            $this->error('Opsi --limit harus berupa angka antara 1 sampai 500.');

            return self::FAILURE;
        }

        $now = now(config('app.timezone', 'Asia/Jakarta'));
        $force = (bool) $this->option('force');
        $processed = 0;
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $query = RentExpirationReminderEmail::query()
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->where(function ($query) use ($now, $force) {
                $query->where(function ($failedQuery) use ($now, $force) {
                    $failedQuery->where('status', RentExpirationReminderEmail::STATUS_FAILED);

                    if (! $force) {
                        $failedQuery->whereNotNull('last_attempt_at')
                            ->where('last_attempt_at', '<=', $now->copy()->subMinutes(self::RETRY_DELAY_MINUTES));
                    }
                })->orWhere(function ($pendingQuery) use ($now, $force) {
                    $pendingQuery->where('status', RentExpirationReminderEmail::STATUS_PENDING);

                    if (! $force) {
                        $pendingQuery->where('created_at', '<=', $now->copy()->subMinutes(self::STALE_PROCESS_MINUTES));
                    }
                })->orWhere(function ($sendingQuery) use ($now) {
                    $sendingQuery->where('status', RentExpirationReminderEmail::STATUS_SENDING)
                        ->whereNotNull('last_attempt_at')
                        ->where('last_attempt_at', '<=', $now->copy()->subMinutes(self::STALE_PROCESS_MINUTES));
                });
            })
            ->orderBy('id')
            ->limit($limit);

        $ids = $query->pluck('id');

        foreach ($ids as $id) {
            $record = DB::transaction(function () use ($id, $now, $force) {
                $locked = RentExpirationReminderEmail::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (! $locked || $locked->attempts >= self::MAX_ATTEMPTS) {
                    return null;
                }

                $isRetryableFailure = $locked->status === RentExpirationReminderEmail::STATUS_FAILED
                    && ($force || (
                        $locked->last_attempt_at !== null
                        && $locked->last_attempt_at->lte($now->copy()->subMinutes(self::RETRY_DELAY_MINUTES))
                    ));

                $isStalePending = $locked->status === RentExpirationReminderEmail::STATUS_PENDING
                    && ($force || $locked->created_at->lte($now->copy()->subMinutes(self::STALE_PROCESS_MINUTES)));

                $isStaleSending = $locked->status === RentExpirationReminderEmail::STATUS_SENDING
                    && $locked->last_attempt_at !== null
                    && $locked->last_attempt_at->lte($now->copy()->subMinutes(self::STALE_PROCESS_MINUTES));

                if (! $isRetryableFailure && ! $isStalePending && ! $isStaleSending) {
                    return null;
                }

                // Claim record agar proses scheduler lain tidak mengambilnya bersamaan.
                // attempts baru bertambah tepat sebelum SMTP benar-benar dipanggil.
                $locked->forceFill([
                    'status' => RentExpirationReminderEmail::STATUS_SENDING,
                    'last_attempt_at' => $now,
                    'last_error' => null,
                ])->save();

                return $locked->fresh();
            });

            if (! $record) {
                $skipped++;
                continue;
            }

            $record->load(['rent.tenant.user', 'rent.room', 'user']);
            $user = $record->user;
            $rent = $record->rent;

            $currentEmail = trim((string) ($user?->email ?? ''));

            if (
                ! $user
                || ! $rent
                || ! $user->email_verified_at
                || $currentEmail === ''
                || filter_var($currentEmail, FILTER_VALIDATE_EMAIL) === false
            ) {
                $record->forceFill([
                    'status' => RentExpirationReminderEmail::STATUS_SKIPPED_UNVERIFIED,
                    'failed_at' => null,
                    'last_error' => 'Email tidak dikirim karena akun tidak memiliki alamat email yang valid dan terverifikasi.',
                ])->save();

                $skipped++;
                continue;
            }

            // Gunakan email akun terkini, bukan alamat lama yang mungkin tersimpan di tracking.
            $record->forceFill([
                'recipient_email' => $currentEmail,
                'attempts' => $record->attempts + 1,
                'last_attempt_at' => now(config('app.timezone')),
            ])->save();

            $record->refresh();

            try {
                Mail::to($currentEmail)
                    ->send(new RentExpirationReminderMail($rent, $record->reminder_type));

                $record->forceFill([
                    'status' => RentExpirationReminderEmail::STATUS_SENT,
                    'sent_at' => now(config('app.timezone')),
                    'failed_at' => null,
                    'last_error' => null,
                ])->save();

                $sent++;
            } catch (Throwable $exception) {
                report($exception);

                $record->forceFill([
                    'status' => RentExpirationReminderEmail::STATUS_FAILED,
                    'failed_at' => now(config('app.timezone')),
                    'last_error' => mb_substr($exception->getMessage(), 0, 60000),
                ])->save();

                $failed++;
                $this->warn("Retry email ID {$record->id} gagal: {$exception->getMessage()}");
            }

            $processed++;
        }

        $this->info(sprintf(
            'Retry selesai. Diproses=%d, berhasil=%d, gagal=%d, dilewati=%d. Maksimal percobaan=%d.',
            $processed,
            $sent,
            $failed,
            $skipped,
            self::MAX_ATTEMPTS,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
