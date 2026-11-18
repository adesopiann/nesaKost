<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Rent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncRentalLifecycle extends Command
{
    protected $signature = 'ekost:sync-rental-lifecycle';

    protected $description = 'Sinkronisasi kedaluwarsa booking dan status lifecycle rent.';

    public function handle(): int
    {
        $today = today(config('app.timezone'))->toDateString();
        $now = now(config('app.timezone'));

        $expiredBookingIds = DB::transaction(function () use ($now) {
            $bookings = Booking::query()
                ->where('status', 'pending_payment')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', $now)
                ->lockForUpdate()
                ->get(['id']);

            if ($bookings->isEmpty()) {
                return [];
            }

            $ids = $bookings->pluck('id')->all();

            Booking::query()
                ->whereIn('id', $ids)
                ->where('status', 'pending_payment')
                ->update(['status' => 'expired', 'updated_at' => $now]);

            Payment::query()
                ->whereIn('booking_id', $ids)
                ->where('status', 'pending')
                ->update([
                    'status' => 'expired',
                    'updated_at' => $now,
                ]);

            return $ids;
        });

        // Jika scheduler sempat tidak berjalan selama beberapa hari,
        // rent yang seluruh periodenya sudah lewat langsung diselesaikan.
        $completedBeforeActivation = Rent::query()
            ->where('status', 'scheduled')
            ->whereDate('end_date', '<', $today)
            ->update([
                'status' => 'completed',
                'updated_at' => $now,
            ]);

        // Masa sewa dimulai tepat pada pukul 00.00 tanggal start_date.
        $activated = Rent::query()
            ->where('status', 'scheduled')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->update([
                'status' => 'active',
                'updated_at' => $now,
            ]);

        // end_date adalah hari terakhir masa sewa; selesai pada 00.00
        // hari berikutnya, sehingga end_date harus lebih kecil dari hari ini.
        $completed = Rent::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<', $today)
            ->update([
                'status' => 'completed',
                'updated_at' => $now,
            ]);

        $this->info(sprintf(
            'Lifecycle tersinkronisasi. Booking kedaluwarsa: %d; rent aktif: %d; rent selesai: %d.',
            count($expiredBookingIds),
            $activated,
            $completed + $completedBeforeActivation,
        ));

        return self::SUCCESS;
    }
}
