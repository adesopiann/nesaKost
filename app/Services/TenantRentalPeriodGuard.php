<?php

namespace App\Services;

use App\Models\Tenant;
use Carbon\CarbonInterface;

class TenantRentalPeriodGuard
{
    /**
     * Memeriksa apakah periode baru bertabrakan dengan rent tenant yang
     * masih berjalan/terjadwal atau booking yang masih menunggu pembayaran.
     *
     * Kedua batas tanggal bersifat inklusif.
     */
    public function hasConflict(
        Tenant $tenant,
        CarbonInterface $startDate,
        CarbonInterface $endDate
    ): bool {
        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

        $hasOverlappingRent = $tenant->rents()
            ->whereIn('status', ['scheduled', 'active'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($hasOverlappingRent) {
            return true;
        }

        return $tenant->bookings()
            ->where('status', 'pending_payment')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();
    }

    /**
     * Mengunci baris tenant sebagai mutex agar proses booking reguler dan
     * renewal untuk tenant yang sama berjalan secara serial.
     */
    public function lockTenant(int $tenantId): Tenant
    {
        return Tenant::query()
            ->whereKey($tenantId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
