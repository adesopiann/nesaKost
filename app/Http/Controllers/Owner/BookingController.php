<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    private function ownerId(Request $request): int
    {
        $ownerId = \DB::table('owners')->where('user_id', $request->user()->id)->value('id');
        abort_unless($ownerId, 403, 'Data owner tidak ditemukan.');
        return (int) $ownerId;
    }

    public function index(Request $request): View
    {
        $ownerId = $this->ownerId($request);
        $branches = \DB::table('branch_dorm')->where('owner_id', $ownerId)->select('id', 'name')->orderBy('name')->get();

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:30'],
            'type' => ['nullable', 'string', 'in:all,new,renewal'],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $branchId = isset($validated['branch_id']) && $validated['branch_id'] !== '' ? (int) $validated['branch_id'] : null;
        if ($branchId !== null) {
            abort_unless($branches->contains(fn ($branch) => (int) $branch->id === $branchId), 404);
        }
        $status = $validated['status'] ?? 'all';
        abort_unless($status === 'all' || in_array($status, ['pending_payment', 'confirmed', 'expired', 'cancelled'], true), 404);
        $type = $validated['type'] ?? 'all';
        $search = trim($validated['q'] ?? '');
        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;

        $query = Booking::query()
            ->with(['tenant.user', 'room.branchDorm', 'payments', 'rent'])
            ->whereHas('room.branchDorm', fn ($q) => $q->where('owner_id', $ownerId))
            ->when($branchId, fn ($q) => $q->whereHas('room.branchDorm', fn ($b) => $b->where('id', $branchId)))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($type !== 'all', fn ($q) => $q->where('booking_type', $type))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('booking_code', 'like', "%{$search}%")
                        ->orWhereHas('room', fn ($room) => $room->where('room_code', 'like', "%{$search}%"))
                        ->orWhereHas('room.branchDorm', fn ($branch) => $branch->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('tenant.user', fn ($user) => $user->where('fname', 'like', "%{$search}%")->orWhere('lname', 'like', "%{$search}%"));
                });
            });

        $summaryQuery = Booking::query()->whereHas('room.branchDorm', fn ($q) => $q->where('owner_id', $ownerId))
            ->when($branchId, fn ($q) => $q->whereHas('room.branchDorm', fn ($b) => $b->where('id', $branchId)))
            ->when($type !== 'all', fn ($q) => $q->where('booking_type', $type))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'pending_payment' => (clone $summaryQuery)->where('status', 'pending_payment')->count(),
            'confirmed' => (clone $summaryQuery)->where('status', 'confirmed')->count(),
            'expired' => (clone $summaryQuery)->where('status', 'expired')->count(),
            'cancelled' => (clone $summaryQuery)->where('status', 'cancelled')->count(),
        ];

        $bookings = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('pages.owner.bookings.index', compact('bookings', 'summary', 'branches', 'branchId', 'status', 'type', 'search', 'from', 'to'));
    }

    public function show(Request $request, string $bookingCode): View
    {
        $ownerId = $this->ownerId($request);
        $booking = Booking::query()
            ->with(['tenant.user', 'room.branchDorm', 'payments', 'rent', 'previousRent'])
            ->where('booking_code', $bookingCode)
            ->whereHas('room.branchDorm', fn ($q) => $q->where('owner_id', $ownerId))
            ->firstOrFail();

        return view('pages.owner.bookings.show', compact('booking'));
    }
}
