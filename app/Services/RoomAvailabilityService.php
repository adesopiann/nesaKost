<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Rent;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class RoomAvailabilityService
{
    /**
     * Status rent yang masih mengalokasikan kamar.
     */
    private const BLOCKING_RENT_STATUSES = [
        'scheduled',
        'active',
    ];

    /**
     * Status booking yang dapat mengalokasikan periode kamar.
     */
    private const BLOCKING_BOOKING_STATUSES = [
        'confirmed',
        'pending_payment',
    ];

    /**
     * Kebijakan prioritas renewal.
     */
    private const RENEWAL_NOTICE_DAYS = 30;
    private const RENEWAL_DEADLINE_DAYS = 15;

    /**
     * Memeriksa ketersediaan kamar pada periode tertentu.
     */
    public function checkPeriod(
        Room $room,
        string|CarbonInterface $startDate,
        string|CarbonInterface $endDate
    ): array {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        if ($end->lessThan($start)) {
            throw new InvalidArgumentException(
                'Tanggal selesai tidak boleh sebelum tanggal mulai.'
            );
        }

        // 1. Periksa status operasional kamar.
        if ($room->status !== 'active') {
            return $this->unavailable(
                'room_inactive',
                'Kamar sedang tidak menerima booking.'
            );
        }

        // 2. Periksa benturan dengan rent.
        $conflictingRent = Rent::query()
            ->where('room_id', $room->id)
            ->whereIn('status', self::BLOCKING_RENT_STATUSES)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->orderBy('end_date')
            ->first();

        if ($conflictingRent) {
            return $this->unavailable(
                'rent_conflict',
                'Periode yang dipilih bertabrakan dengan masa sewa penghuni lain.',
                [
                    'blocking_type' => 'rent',
                    'blocking_until' => $conflictingRent->end_date->toDateString(),
                ]
            );
        }

        // 3. Periksa benturan dengan booking yang masih berlaku.
        $now = now();

        $conflictingBooking = Booking::query()
            ->where('room_id', $room->id)
            ->where(function ($query) use ($now) {
                $query->where('status', 'confirmed')
                    ->orWhere(function ($pendingQuery) use ($now) {
                        $pendingQuery
                            ->where('status', 'pending_payment')
                            ->where(function ($expiryQuery) use ($now) {
                                $expiryQuery
                                    ->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', $now);
                            });
                    });
            })
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->first();

        if ($conflictingBooking) {
            return $this->unavailable(
                'booking_conflict',
                'Periode yang dipilih sudah dialokasikan kepada tenant lain.',
                [
                    'blocking_type' => 'booking',
                ]
            );
        }

        // 4. Periksa prioritas renewal penghuni lama.
        $renewalPriority = $this->checkRenewalPriority(
            $room,
            $start,
            $end
        );

        if ($renewalPriority !== null) {
    return $this->unavailable(
        'renewal_priority',
        $renewalPriority['message'],
        [
            'blocking_type' => 'renewal',
            'blocking_until' => $renewalPriority['blocking_until'],
            'priority_starts_at' => $renewalPriority['priority_starts_at'],
            'priority_deadline' => $renewalPriority['priority_deadline'],
        ]
    );
}

        return [
            'available' => true,
            'status' => 'available',
            'reason' => null,
            'message' => 'Kamar tersedia untuk periode yang dipilih.',
            'blocking_type' => null,
            'blocking_until' => null,
            'priority_starts_at' => null,
            'priority_deadline' => null,
        ];
    }

    /**
     * Memeriksa apakah terdapat masa prioritas renewal
     * yang melindungi periode setelah rent penghuni lama.
     */
        
    
private function checkRenewalPriority(
    Room $room,
    CarbonImmutable $requestedStart,
    CarbonImmutable $requestedEnd
): ?array {
    $today = CarbonImmutable::today();

    $existingRents = Rent::query()
        ->where('room_id', $room->id)
        ->whereIn('status', self::BLOCKING_RENT_STATUSES)
        ->whereDate('end_date', '<', $requestedStart->toDateString())
        ->orderBy('end_date')
        ->get();

    foreach ($existingRents as $rent) {
        $rentEnd = CarbonImmutable::parse($rent->end_date);

        // Periode renewal dimulai sehari setelah rent berakhir.
        $renewalStart = $rentEnd->addDay();

        // Perlindungan maksimal untuk renewal 6 bulan.
        $renewalEnd = $renewalStart
            ->addMonthsNoOverflow(6)
            ->subDay();

        // Periode hak prioritas penghuni lama.
        $priorityStartsAt = $rentEnd->subDays(
            self::RENEWAL_NOTICE_DAYS
        );

        $priorityDeadline = $rentEnd->subDays(
            self::RENEWAL_DEADLINE_DAYS
        );

        // Jika batas prioritas sudah lewat,
        // periode tidak lagi ditahan oleh hak prioritas ini.
        if ($today->greaterThan($priorityDeadline)) {
            continue;
        }

        // Periksa benturan dengan periode perlindungan renewal.
        $overlapsRenewalPeriod =
            $requestedStart->lessThanOrEqualTo($renewalEnd)
            && $requestedEnd->greaterThanOrEqualTo($renewalStart);

        if (! $overlapsRenewalPeriod) {
            continue;
        }

        $message = $today->lessThan($priorityStartsAt)
            ? 'Periode ini ditahan untuk prioritas perpanjangan penghuni lama. Kesempatan konfirmasi dimulai pada '
                . $priorityStartsAt->format('d F Y') . '.'
            : 'Penghuni lama masih memiliki hak prioritas perpanjangan hingga '
                . $priorityDeadline->format('d F Y') . '.';

        return [
            'starts_at' => $renewalStart->toDateString(),
            'deadline' => $priorityDeadline->toDateString(),
            'priority_starts_at' => $priorityStartsAt->toDateString(),
            'priority_deadline' => $priorityDeadline->toDateString(),
            'blocking_until' => $priorityDeadline->toDateString(),
            'message' => $message,
        ];
    }

    return null;
}



    /**
     * Menghasilkan informasi kamar yang tidak tersedia.
     */
    private function unavailable(
        string $reason,
        string $message,
        array $additionalData = []
    ): array {
        return array_merge([
            'available' => false,
            'status' => 'unavailable',
            'reason' => $reason,
            'message' => $message,
            'blocking_type' => null,
            'blocking_until' => null,
            'priority_starts_at' => null,
            'priority_deadline' => null,
        ], $additionalData);
    }

    /**
     * Mendapatkan kondisi ketersediaan kamar saat ini.
     */
    public function getCurrentStatus(Room $room): array
    {
        $result = $this->checkPeriod(
            $room,
            CarbonImmutable::today(),
            CarbonImmutable::today()
        );

        if ($result['available']) {
            return [
                ...$result,
                'status' => 'available',
            ];
        }

        if ($result['reason'] === 'rent_conflict') {
            $result['status'] = 'occupied';
        } elseif ($result['reason'] === 'booking_conflict') {
            $result['status'] = 'reserved';
        }

        return $result;
    }
}
