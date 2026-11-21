<?php

namespace App\Console\Commands;

use App\Mail\RentExpirationReminderMail;
use App\Models\Rent;
use App\Models\RentExpirationReminderEmail;
use App\Notifications\RentExpirationNotification;
use App\Services\OwnerNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendRentExpirationReminders extends Command
{
    protected $signature = 'ekost:send-rent-expiration-reminders {--date= : Tanggal simulasi dengan format YYYY-MM-DD}';

    protected $description = 'Mengirim pengingat masa sewa H-30, H-7, dan H kepada penyewa.';

    public function handle(): int
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $dateOption = $this->option('date');

        try {
            $today = $dateOption
                ? CarbonImmutable::parse($dateOption, $timezone)
                : CarbonImmutable::now($timezone)->startOfDay();
        } catch (Throwable) {
            $this->error('Format --date tidak valid. Gunakan YYYY-MM-DD.');

            return self::FAILURE;
        }

        if ($dateOption && $today->toDateString() !== $dateOption) {
            $this->error('Format --date tidak valid. Gunakan YYYY-MM-DD.');

            return self::FAILURE;
        }

        $databaseSent = ['H-30' => 0, 'H-7' => 0, 'H' => 0];
        $emailSent = 0;
        $emailSkipped = 0;
        $emailFailed = 0;
        $skipped = 0;
        $failed = false;

        $reminderTargets = [
            'H-30' => $today->addDays(30)->toDateString(),
            'H-7' => $today->addDays(7)->toDateString(),
            'H' => $today->toDateString(),
        ];

        foreach ($reminderTargets as $reminderType => $endDate) {
            Rent::query()
                ->with(['tenant.user', 'room'])
                ->whereDate('end_date', $endDate)
                ->whereIn('status', ['scheduled', 'active'])
                ->chunkById(100, function ($rents) use (
                    $reminderType,
                    &$databaseSent,
                    &$emailSent,
                    &$emailSkipped,
                    &$emailFailed,
                    &$skipped,
                    &$failed
                ) {
                    foreach ($rents as $rent) {
                        try {
                            // Simpan notifikasi in-app dan marker reminder dalam transaksi.
                            // Pengiriman email sengaja dilakukan setelah transaksi commit.
                            $wasSent = DB::transaction(function () use ($rent, $reminderType) {
                                $lockedRent = Rent::query()
                                    ->whereKey($rent->id)
                                    ->lockForUpdate()
                                    ->first();

                                if (! $lockedRent || ! in_array($lockedRent->status, ['scheduled', 'active'], true)) {
                                    return false;
                                }

                                $alreadySent = DB::table('rent_expiration_reminders')
                                    ->where('rent_id', $lockedRent->id)
                                    ->where('reminder_type', $reminderType)
                                    ->exists();

                                if ($alreadySent) {
                                    return false;
                                }

                                $user = $rent->tenant?->user;

                                if (! $user) {
                                    return false;
                                }

                                DB::table('rent_expiration_reminders')->insert([
                                    'rent_id' => $lockedRent->id,
                                    'reminder_type' => $reminderType,
                                    'sent_at' => now(config('app.timezone')),
                                    'created_at' => now(config('app.timezone')),
                                    'updated_at' => now(config('app.timezone')),
                                ]);

                                $user->notify(new RentExpirationNotification($rent, $reminderType));
                                app(OwnerNotificationService::class)->rentExpiration($rent, $reminderType);

                                return true;
                            });

                            if (! $wasSent) {
                                $skipped++;
                                continue;
                            }

                            $databaseSent[$reminderType]++;

                            $user = $rent->tenant?->user;

                            if (! $user || ! $user->email_verified_at || blank($user->email)) {
                                $this->recordSkippedUnverifiedEmail($rent, $user?->id, $reminderType, $user?->email);
                                $emailSkipped++;
                                continue;
                            }

                            $emailRecord = RentExpirationReminderEmail::query()->firstOrCreate(
                                [
                                    'rent_id' => $rent->id,
                                    'reminder_type' => $reminderType,
                                ],
                                [
                                    'user_id' => $user->id,
                                    'recipient_email' => $user->email,
                                    'status' => RentExpirationReminderEmail::STATUS_PENDING,
                                    'attempts' => 0,
                                ]
                            );

                            // Catatan unik mungkin sudah ada akibat proses sebelumnya.
                            // Jangan mengirim ulang email yang sudah berhasil atau dilewati.
                            if (! $emailRecord->wasRecentlyCreated) {
                                if (in_array($emailRecord->status, [
                                    RentExpirationReminderEmail::STATUS_SENT,
                                    RentExpirationReminderEmail::STATUS_SKIPPED_UNVERIFIED,
                                ], true)) {
                                    $emailSkipped++;
                                }
                                continue;
                            }

                            // Tandai sedang dikirim sebelum menghubungi SMTP.
                            // Jika proses berhenti mendadak, retry dapat memulihkan status sending yang kedaluwarsa.
                            $emailRecord->forceFill([
                                'status' => RentExpirationReminderEmail::STATUS_SENDING,
                                'attempts' => $emailRecord->attempts + 1,
                                'last_attempt_at' => now(config('app.timezone')),
                                'last_error' => null,
                            ])->save();

                            try {
                                Mail::to($emailRecord->recipient_email)
                                    ->send(new RentExpirationReminderMail($rent, $reminderType));

                                $emailRecord->forceFill([
                                    'status' => RentExpirationReminderEmail::STATUS_SENT,
                                    'sent_at' => now(config('app.timezone')),
                                    'failed_at' => null,
                                    'last_error' => null,
                                ])->save();

                                $emailSent++;
                            } catch (Throwable $mailException) {
                                report($mailException);

                                $emailRecord->forceFill([
                                    'status' => RentExpirationReminderEmail::STATUS_FAILED,
                                    'failed_at' => now(config('app.timezone')),
                                    'last_error' => mb_substr($mailException->getMessage(), 0, 60000),
                                ])->save();

                                $emailFailed++;
                                $this->warn("Email pengingat gagal untuk rent ID {$rent->id}: {$mailException->getMessage()}");
                            }
                        } catch (QueryException $exception) {
                            if ($this->isUniqueConstraintViolation($exception)) {
                                $skipped++;
                                continue;
                            }

                            report($exception);
                            $this->error("Gagal memproses rent ID {$rent->id}: {$exception->getMessage()}");
                            $failed = true;
                        } catch (Throwable $exception) {
                            report($exception);
                            $this->error("Gagal memproses rent ID {$rent->id}: {$exception->getMessage()}");
                            $failed = true;
                        }
                    }

                    return true;
                });
        }

        $this->info(sprintf(
            'Pengingat %s. Database: H-30=%d, H-7=%d, H=%d. Email berhasil=%d, dilewati=%d, gagal=%d. Data dilewati=%d.',
            $today->toDateString(),
            $databaseSent['H-30'],
            $databaseSent['H-7'],
            $databaseSent['H'],
            $emailSent,
            $emailSkipped,
            $emailFailed,
            $skipped,
        ));

        return ($failed || $emailFailed > 0) ? self::FAILURE : self::SUCCESS;
    }

    private function recordSkippedUnverifiedEmail(
        Rent $rent,
        ?int $userId,
        string $reminderType,
        ?string $email
    ): void {
        if (! $userId || blank($email)) {
            // Tidak ada alamat email valid untuk dicatat sebagai penerima.
            return;
        }

        RentExpirationReminderEmail::query()->firstOrCreate(
            [
                'rent_id' => $rent->id,
                'reminder_type' => $reminderType,
            ],
            [
                'user_id' => $userId,
                'recipient_email' => $email,
                'status' => RentExpirationReminderEmail::STATUS_SKIPPED_UNVERIFIED,
                'attempts' => 0,
            ]
        );
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $sqlState === '23000' || in_array($driverCode, [19, 1062, 1555, 2067], true);
    }
}
