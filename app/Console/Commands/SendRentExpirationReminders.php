<?php

namespace App\Console\Commands;

use App\Models\Rent;
use App\Notifications\RentExpirationNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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

        $sent = ['H-30' => 0, 'H-7' => 0, 'H' => 0];
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
                ->chunkById(100, function ($rents) use ($reminderType, &$sent, &$skipped, &$failed) {
                    foreach ($rents as $rent) {
                        try {
                            $wasSent = DB::transaction(function () use ($rent, $reminderType) {
                                // Lock rent agar dua proses bersamaan tidak mengirim reminder ganda.
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

                                return true;
                            });

                            if ($wasSent) {
                                $sent[$reminderType]++;
                            } else {
                                $skipped++;
                            }
                        } catch (QueryException $exception) {
                            // Unique index is the final guard if concurrent workers race.
                            if ($this->isUniqueConstraintViolation($exception)) {
                                $skipped++;
                                continue;
                            }

                            report($exception);
                            $this->error("Gagal memproses rent ID {$rent->id}: {$exception->getMessage()}");
                            $failed = true;

                            return false;
                        } catch (Throwable $exception) {
                            report($exception);
                            $this->error("Gagal memproses rent ID {$rent->id}: {$exception->getMessage()}");
                            $failed = true;

                            return false;
                        }
                    }

                    return true;
                });
        }

        $this->info(sprintf(
            'Pengingat diproses untuk %s. Terkirim: H-30=%d, H-7=%d, H=%d. Dilewati: %d.',
            $today->toDateString(),
            $sent['H-30'],
            $sent['H-7'],
            $sent['H'],
            $skipped,
        ));

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return $sqlState === '23000' || in_array($driverCode, [19, 1062, 1555, 2067], true);
    }
}
