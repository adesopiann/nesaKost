<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BranchDorm;
use App\Models\Booking;
use App\Models\Rent;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    private function ownedBranchIds(Request $request)
    {
        return BranchDorm::query()
            ->whereHas('owner', fn (Builder $query) => $query->where('user_id', $request->user()->id))
            ->select('id');
    }

    private function bookingQuery(Request $request): Builder
    {
        return Booking::query()->whereHas('room', fn (Builder $room) =>
            $room->whereIn('branch_dorm_id', $this->ownedBranchIds($request))
        );
    }

    private function rentQuery(Request $request): Builder
    {
        return Rent::query()->whereHas('room', fn (Builder $room) =>
            $room->whereIn('branch_dorm_id', $this->ownedBranchIds($request))
        );
    }

    private function tenantStatus(Tenant $tenant): string
    {
        $today = today();
        $rents = $tenant->rents;
        $bookings = $tenant->bookings;

        if ($rents->contains(fn ($rent) => $rent->status === 'active'
            && $rent->start_date?->toDateString() <= $today->toDateString()
            && $rent->end_date?->toDateString() >= $today->toDateString())) {
            return 'active';
        }

        if ($rents->contains(fn ($rent) => $rent->status === 'scheduled'
            && $rent->end_date?->toDateString() >= $today->toDateString())) {
            return 'scheduled';
        }

        if ($bookings->contains(fn ($booking) => $booking->status === 'pending_payment'
            && (!$booking->expires_at || $booking->expires_at->isFuture())
            && $booking->end_date?->toDateString() >= $today->toDateString())) {
            return 'booking_pending';
        }

        return 'historical';
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $branchId = $request->query('branch_id');
        $status = (string) $request->query('status', 'all');
        $branches = BranchDorm::query()->whereIn('id', $this->ownedBranchIds($request))->orderBy('name')->get();

        abort_if($branchId && !$branches->contains('id', (int) $branchId), 404);
        $ownedIds = $this->ownedBranchIds($request);

        $base = Tenant::query()->where(function (Builder $query) use ($ownedIds) {
            $query->whereHas('bookings.room', fn (Builder $room) => $room->whereIn('branch_dorm_id', $ownedIds))
                ->orWhereHas('rents.room', fn (Builder $room) => $room->whereIn('branch_dorm_id', $ownedIds));
        });

        $activeRent = fn (Builder $rent) => $rent->where('status', 'active')->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())->whereHas('room', fn (Builder $room) => $room->whereIn('branch_dorm_id', $ownedIds));
        $scheduledRent = fn (Builder $rent) => $rent->where('status', 'scheduled')->whereDate('end_date', '>=', today())
            ->whereHas('room', fn (Builder $room) => $room->whereIn('branch_dorm_id', $ownedIds));
        $pendingBooking = fn (Builder $booking) => $booking->where('status', 'pending_payment')
            ->where(fn (Builder $expiry) => $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereDate('end_date', '>=', today())->whereHas('room', fn (Builder $room) => $room->whereIn('branch_dorm_id', $ownedIds));
        $summary = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->whereHas('rents', $activeRent)->count(),
            'booking_pending' => (clone $base)->whereDoesntHave('rents', $activeRent)->whereDoesntHave('rents', $scheduledRent)
                ->whereHas('bookings', $pendingBooking)->count(),
        ];
        $summary['historical'] = (clone $base)->whereDoesntHave('rents', $activeRent)->whereDoesntHave('rents', $scheduledRent)
            ->whereDoesntHave('bookings', $pendingBooking)->count();

        $query = clone $base;
        if ($status === 'active') {
            $query->whereHas('rents', $activeRent);
        } elseif ($status === 'scheduled') {
            $query->whereDoesntHave('rents', $activeRent)->whereHas('rents', $scheduledRent);
        } elseif ($status === 'booking_pending') {
            $query->whereDoesntHave('rents', $activeRent)->whereDoesntHave('rents', $scheduledRent)->whereHas('bookings', $pendingBooking);
        } elseif ($status === 'historical') {
            $query->whereDoesntHave('rents', $activeRent)->whereDoesntHave('rents', $scheduledRent)->whereDoesntHave('bookings', $pendingBooking);
        } elseif ($status !== 'all') {
            abort(404);
        }
        if ($branchId) {
            $query->where(function (Builder $tenantQuery) use ($branchId) {
                $tenantQuery->whereHas('bookings.room', fn (Builder $room) => $room->where('branch_dorm_id', $branchId))
                    ->orWhereHas('rents.room', fn (Builder $room) => $room->where('branch_dorm_id', $branchId));
            });
        }
        if ($search !== '') {
            $query->whereHas('user', fn (Builder $user) => $user->where('fname', 'like', "%{$search}%")
                ->orWhere('lname', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone_number', 'like', "%{$search}%"));
        }

        $tenants = $query->with([
            'user',
            'bookings' => fn ($booking) => $booking->whereHas('room', fn (Builder $room) => $room->whereIn('branch_dorm_id', $ownedIds))
                ->with(['room.branchDorm'])->latest(),
            'rents' => fn ($rent) => $rent->whereHas('room', fn (Builder $room) => $room->whereIn('branch_dorm_id', $ownedIds))
                ->with(['room.branchDorm'])->latest(),
        ])->latest()->paginate(10)->withQueryString();

        foreach ($tenants as $tenant) {
            $tenant->setAttribute('derived_status', $this->tenantStatus($tenant));
            $latestRent = $tenant->rents->first();
            $latestBooking = $tenant->bookings->first();
            $tenant->setAttribute('latest_transaction', !$latestRent ? $latestBooking : (!$latestBooking || $latestRent->created_at >= $latestBooking->created_at ? $latestRent : $latestBooking));
        }

        return view('pages.owner.tenants.index', compact('tenants', 'branches', 'search', 'branchId', 'status', 'summary'));
    }

    public function show(Request $request, string $publicId): View
    {
        $tenant = Tenant::query()->with('user')->where('public_id', $publicId)->firstOrFail();
        $tenantId = $tenant->id;
        $hasOwnedTransaction = Tenant::query()->whereKey($tenantId)->where(function (Builder $query) use ($request) {
            $query->whereHas('bookings.room', fn (Builder $room) => $room->whereIn('branch_dorm_id', $this->ownedBranchIds($request)))
                ->orWhereHas('rents.room', fn (Builder $room) => $room->whereIn('branch_dorm_id', $this->ownedBranchIds($request)));
        })->exists();
        abort_unless($hasOwnedTransaction, 404);

        $bookings = $this->bookingQuery($request)->where('tenant_id', $tenantId)
            ->with(['room.branchDorm', 'payments', 'rent'])->latest()->get();
        $rents = $this->rentQuery($request)->where('tenant_id', $tenantId)
            ->with(['room.branchDorm', 'booking'])->latest()->get();

        $tenant->setRelation('bookings', $bookings);
        $tenant->setRelation('rents', $rents);
        $tenant->setAttribute('derived_status', $this->tenantStatus($tenant));

        // Sewa yang sedang berlangsung menjadi sumber informasi kamar saat ini.
        $currentRent = $rents
            ->filter(fn ($rent) => $rent->status === 'active'
                && $rent->start_date?->toDateString() <= today()->toDateString()
                && $rent->end_date?->toDateString() >= today()->toDateString())
            ->sortByDesc('start_date')
            ->first();

        $events = collect();
        foreach ($bookings as $booking) {
            $events->push([
                'date' => $booking->created_at,
                'type' => 'booking',
                'title' => 'Booking dibuat',
                'code' => $booking->booking_code ?: 'Booking #' . $booking->id,
                'status' => $booking->status,
                'amount' => $booking->price_snapshot,
                'description' => ($booking->room?->room_code ?? 'Kamar') . ' · ' . ($booking->room?->branchDorm?->name ?? 'Cabang'),
                'period' => $booking->start_date?->format('d M Y') . ' – ' . $booking->end_date?->format('d M Y'),
            ]);
            foreach ($booking->payments as $payment) {
                $events->push([
                    'date' => $payment->paid_at ?: $payment->created_at,
                    'type' => 'payment',
                    'title' => 'Pembayaran ' . ($payment->status === 'paid' ? 'berhasil' : 'tercatat'),
                    'code' => $payment->payment_code ?: 'Payment #' . $payment->id,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'description' => ($booking->booking_code ?: 'Booking #' . $booking->id) . ' · ' . ($booking->room?->branchDorm?->name ?? 'Cabang'),
                    'period' => null,
                ]);
            }
        }
        foreach ($rents as $rent) {
            $events->push([
                'date' => $rent->created_at,
                'type' => 'rent',
                'title' => 'Data sewa dibuat',
                'code' => $rent->rent_code ?: 'Rent #' . $rent->id,
                'status' => $rent->status,
                'amount' => $rent->total_price,
                'description' => ($rent->room?->room_code ?? 'Kamar') . ' · ' . ($rent->room?->branchDorm?->name ?? 'Cabang'),
                'period' => $rent->start_date?->format('d M Y') . ' – ' . $rent->end_date?->format('d M Y'),
            ]);
        }
        $events = $events->sortByDesc(fn (array $event) => $event['date']?->timestamp ?? 0)->values();

        $transactionSummary = [
            'bookings' => $bookings->count(),
            'rents' => $rents->count(),
            'payments' => $bookings->sum(fn ($booking) => $booking->payments->count()),
            'paid' => $bookings->sum(fn ($booking) => $booking->payments->where('status', 'paid')->sum('amount')),
        ];

        return view('pages.owner.tenants.show', compact('tenant', 'events', 'transactionSummary', 'currentRent'));
    }
}
