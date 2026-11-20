<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Rent;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class RentInformationController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403, 'Data tenant tidak ditemukan.');

        $rents = $tenant->rents()
            ->with([
                'room.branchDorm',
                'booking.payments',
                'renewalBookings.payments',
                'renewalBookings.rent',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $currentRents = $rents
            ->whereIn('status', ['active', 'scheduled'])
            ->sortBy(fn ($rent) => $rent->status === 'active' ? 0 : 1)
            ->values();

        $historicalRents = $rents
            ->whereIn('status', ['completed', 'cancelled'])
            ->sortByDesc('end_date')
            ->values();

        $today = CarbonImmutable::today(config('app.timezone'));

        foreach ($rents as $rent) {
            $endDate = CarbonImmutable::parse($rent->end_date)->startOfDay();
            $rent->days_remaining = (int) $today->diffInDays($endDate, false);
            $rent->latest_renewal = $rent->renewalBookings->sortByDesc('id')->first();
            $rent->renewal_eligibility = $this->renewalEligibility($rent, $today);
        }

        return view('pages.tenant.informasi-sewa.index', compact(
            'currentRents',
            'historicalRents'
        ));
    }

    private function renewalEligibility(Rent $rent, CarbonImmutable $today): array
    {
        $endDate = CarbonImmutable::parse($rent->end_date)->startOfDay();
        $opensAt = $endDate->subDays(30);
        $closesAt = $endDate->subDays(7);
        $existingRenewal = $rent->renewalBookings->isNotEmpty();
        $statusAllowed = in_array($rent->status, ['active', 'scheduled'], true);
        $windowOpen = $today->betweenIncluded($opensAt, $closesAt);

        if ($existingRenewal) {
            return [
                'eligible' => false,
                'state' => 'used',
                'message' => 'Kesempatan perpanjangan untuk masa sewa ini sudah digunakan.',
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
            ];
        }

        if (!$statusAllowed) {
            return [
                'eligible' => false,
                'state' => 'unavailable',
                'message' => 'Masa sewa ini tidak dapat diperpanjang.',
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
            ];
        }

        if ($today->lt($opensAt)) {
            return [
                'eligible' => false,
                'state' => 'not_open',
                'message' => 'Pengajuan perpanjangan akan tersedia mulai ' . $opensAt->format('d M Y') . '.',
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
            ];
        }

        if ($today->gt($closesAt)) {
            return [
                'eligible' => false,
                'state' => 'closed',
                'message' => 'Periode pengajuan perpanjangan telah ditutup.',
                'opens_at' => $opensAt,
                'closes_at' => $closesAt,
            ];
        }

        return [
            'eligible' => true,
            'state' => 'open',
            'message' => 'Kamu dapat mengajukan perpanjangan sampai ' . $closesAt->format('d M Y') . '.',
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
        ];
    }
}
