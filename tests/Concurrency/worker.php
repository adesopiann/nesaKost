<?php

use App\Models\Booking;
use App\Models\Room;
use App\Services\RoomAvailabilityService;
use App\Services\TenantRentalPeriodGuard;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$script, $tenantId, $roomId, $start, $end, $readyFile, $resultFile, $holdMs, $startedFile] = $argv;

try {
    $result = DB::transaction(function () use (
        $tenantId, $roomId, $start, $end, $readyFile, $holdMs, $startedFile
    ) {
        $guard = app(TenantRentalPeriodGuard::class);
        $availability = app(RoomAvailabilityService::class);

        file_put_contents($startedFile, 'started');
        $tenant = $guard->lockTenant((int) $tenantId);

        if ($guard->hasConflict(
            $tenant,
            CarbonImmutable::parse($start),
            CarbonImmutable::parse($end)
        )) {
            return 'tenant_period_conflict';
        }

        $room = Room::query()
            ->whereKey((int) $roomId)
            ->lockForUpdate()
            ->firstOrFail();

        $check = $availability->checkPeriod(
            $room,
            CarbonImmutable::parse($start),
            CarbonImmutable::parse($end)
        );

        if (!$check['available']) {
            return 'room_unavailable:' . $check['reason'];
        }

        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'start_date' => $start,
            'end_date' => $end,
            'duration_month' => 1,
            'price_snapshot' => $room->monthly_price,
            'status' => 'pending_payment',
            'expires_at' => now()->addMinutes(30),
        ]);

        $booking->update(['booking_code' => 'CONCURRENCY-' . $booking->id]);

        // Beri proses kedua kesempatan masuk dan menunggu lock yang sama.
        file_put_contents($readyFile, (string) $booking->id);
        usleep((int) $holdMs * 1000);

        return 'created:' . $booking->id;
    }, 3);

    file_put_contents($resultFile, $result);
    exit(0);
} catch (Throwable $exception) {
    file_put_contents($resultFile, 'error:' . get_class($exception) . ':' . $exception->getMessage());
    exit(1);
}
