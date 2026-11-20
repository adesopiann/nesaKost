<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Rent;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
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
    private const RENEWAL_DEADLINE_DAYS = 7;

    /**
     * Menghasilkan rentang tanggal yang tidak tersedia untuk kalender.
     *
     * Rent dan booking yang masih mengalokasikan kamar dikembalikan sebagai
     * rentang inklusif. Frontend tetap harus mengirim pilihan ke checkPeriod()
     * dan backend wajib melakukan validasi ulang saat booking disimpan.
     */
    public function getUnavailableRanges(
        Room $room,
        string|CarbonInterface $from,
        string|CarbonInterface $to
    ): array {
        $fromDate = CarbonImmutable::parse($from)->startOfDay();
        $toDate = CarbonImmutable::parse($to)->startOfDay();

        if ($toDate->lessThan($fromDate)) {
            throw new InvalidArgumentException(
                'Tanggal akhir pencarian tidak boleh sebelum tanggal awal.'
            );
        }

        $ranges = [];

        $rents = Rent::query()
            ->where('room_id', $room->id)
            ->whereIn('status', self::BLOCKING_RENT_STATUSES)
            ->whereDate('start_date', '<=', $toDate->toDateString())
            ->whereDate('end_date', '>=', $fromDate->toDateString())
            ->get(['start_date', 'end_date']);

        foreach ($rents as $rent) {
            $ranges[] = [
                'start' => max($fromDate->toDateString(), $rent->start_date->toDateString()),
                'end' => min($toDate->toDateString(), $rent->end_date->toDateString()),
                'reason' => 'rent',
            ];
        }

        $now = now();
        $bookings = Booking::query()
            ->where('room_id', $room->id)
            ->where(function ($query) use ($now) {
                $query->where('status', 'confirmed')
                    ->orWhere(function ($pending) use ($now) {
                        $pending->where('status', 'pending_payment')
                            ->where(function ($expiry) use ($now) {
                                $expiry->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', $now);
                            });
                    });
            })
            ->whereDate('start_date', '<=', $toDate->toDateString())
            ->whereDate('end_date', '>=', $fromDate->toDateString())
            ->get(['start_date', 'end_date']);

        foreach ($bookings as $booking) {
            $ranges[] = [
                'start' => max($fromDate->toDateString(), $booking->start_date->toDateString()),
                'end' => min($toDate->toDateString(), $booking->end_date->toDateString()),
                'reason' => 'booking',
            ];
        }

        // Hak prioritas renewal juga menghalangi booking customer baru.
        $today = CarbonImmutable::today();
        $priorityRents = Rent::query()
            ->where('room_id', $room->id)
            ->whereIn('status', self::BLOCKING_RENT_STATUSES)
            ->whereDate('end_date', '<', $toDate->toDateString())
            // Begitu tenant pernah mengajukan renewal, hak hold prioritas
            // tidak dibuat lagi. Pending renewal memblokir lewat booking-nya;
            // jika expired, periode langsung dilepas.
            ->whereDoesntHave('renewalBookings')
            ->get(['id', 'end_date']);

        foreach ($priorityRents as $rent) {
            $rentEnd = CarbonImmutable::parse($rent->end_date);
            $priorityNoticeStart = $rentEnd->subDays(self::RENEWAL_NOTICE_DAYS);
            $priorityDeadline = $rentEnd->subDays(self::RENEWAL_DEADLINE_DAYS);

            // Hak prioritas hanya aktif pada jendela H-30 sampai H-7.
            // Sebelum H-30, periode setelah rent belum boleh di-hold.
            if (
                $today->lessThan($priorityNoticeStart)
                || $today->greaterThan($priorityDeadline)
            ) {
                continue;
            }

            $priorityStart = $rentEnd->addDay();
            $priorityEnd = $priorityStart->addMonthsNoOverflow(6)->subDay();

            if ($priorityStart->greaterThan($toDate) || $priorityEnd->lessThan($fromDate)) {
                continue;
            }

            $ranges[] = [
                'start' => max($fromDate->toDateString(), $priorityStart->toDateString()),
                'end' => min($toDate->toDateString(), $priorityEnd->toDateString()),
                'reason' => 'renewal_priority',
            ];
        }

        usort($ranges, fn ($a, $b) => strcmp($a['start'], $b['start']));

        return [
            'room_id' => $room->id,
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'available' => $room->status === 'active',
            'unavailable_ranges' => $ranges,
        ];
    }

    /**
     * Memeriksa ketersediaan kamar pada periode tertentu.
     */
    public function checkPeriod(
        Room $room,
        string|CarbonInterface $startDate,
        string|CarbonInterface $endDate,
        ?int $excludeRentId = null
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
        $rentQuery = Rent::query()
            ->where('room_id', $room->id)
            ->whereIn('status', self::BLOCKING_RENT_STATUSES)
            ->when($excludeRentId, fn ($query) => $query->where('id', '!=', $excludeRentId))
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->orderBy('end_date');

        // Di dalam transaksi booking/renewal, gunakan locking read (current read).
        // Ini memastikan hasil pemeriksaan melihat commit transaksi lain yang
        // mungkin selesai ketika request ini sedang menunggu lock baris kamar.
        if (DB::transactionLevel() > 0) {
            $rentQuery->lockForUpdate();
        }

        $conflictingRent = $rentQuery->first();

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

        $bookingQuery = Booking::query()
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
            ->whereDate('end_date', '>=', $start->toDateString());

        if (DB::transactionLevel() > 0) {
            $bookingQuery->lockForUpdate();
        }

        $conflictingBooking = $bookingQuery->first();

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
            $end,
            $excludeRentId
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
    CarbonImmutable $requestedEnd,
    ?int $excludeRentId = null
): ?array {
    $today = CarbonImmutable::today();

    $existingRents = Rent::query()
        ->where('room_id', $room->id)
        ->whereIn('status', self::BLOCKING_RENT_STATUSES)
        ->when($excludeRentId, fn ($query) => $query->where('id', '!=', $excludeRentId))
        ->whereDate('end_date', '<', $requestedStart->toDateString())
        // Renewal yang sudah pernah diajukan tidak lagi memakai hold umum.
        // Booking pending yang belum expired tetap memblokir melalui query booking.
        ->whereDoesntHave('renewalBookings')
        ->orderBy('end_date');

    if (DB::transactionLevel() > 0) {
        $existingRents->lockForUpdate();
    }

    $existingRents = $existingRents->get();

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

        // Hak prioritas hanya aktif pada jendela H-30 sampai H-7.
        // Sebelum H-30, periode setelah rent belum boleh di-hold;
        // setelah H-7, hak prioritas sudah berakhir.
        if (
            $today->lessThan($priorityStartsAt)
            || $today->greaterThan($priorityDeadline)
        ) {
            continue;
        }

        // Periksa benturan dengan periode perlindungan renewal.
        $overlapsRenewalPeriod =
            $requestedStart->lessThanOrEqualTo($renewalEnd)
            && $requestedEnd->greaterThanOrEqualTo($renewalStart);

        if (! $overlapsRenewalPeriod) {
            continue;
        }

        $message = 'Penghuni lama masih memiliki hak prioritas perpanjangan hingga '
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
