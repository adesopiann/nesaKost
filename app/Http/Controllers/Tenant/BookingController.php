<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Services\RoomAvailabilityService;
use App\Services\TenantRentalPeriodGuard;
use App\Services\OwnerNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(
        Request $request,
        RoomAvailabilityService $availabilityService,
        TenantRentalPeriodGuard $periodGuard
    ) {
        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:room,id'],
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'duration_month' => [
                'required',
                'integer',
                'in:1,3,6',
            ],
        ]);

        $tenant = $request->user()->tenant;

        if (!$tenant) {
            abort(403, 'Data tenant tidak ditemukan.');
        }

        $startDate = CarbonImmutable::parse($validated['start_date'])->startOfDay();

        $durationMonth = (int) $validated['duration_month'];

        $endDate = $startDate
            ->addMonthsNoOverflow($durationMonth)
            ->subDay();

        $booking = DB::transaction(function () use (
            $validated,
            $tenant,
            $startDate,
            $endDate,
            $durationMonth,
            $availabilityService,
            $periodGuard
        ) {
            // Kunci baris tenant terlebih dahulu agar booking reguler dan
            // renewal untuk tenant yang sama tidak lolos secara bersamaan.
            $lockedTenant = $periodGuard->lockTenant($tenant->id);

            if ($periodGuard->hasConflict($lockedTenant, $startDate, $endDate)) {
                throw ValidationException::withMessages([
                    'room_id' => 'Periode yang kamu pilih bertabrakan dengan masa sewa atau booking lain yang masih menunggu pembayaran. Pilih periode tanggal yang berbeda.',
                ]);
            }

            // Kunci baris kamar selama transaksi untuk mengurangi risiko
            // dua booking bersamaan pada kamar yang sama.
            $room = Room::query()
                ->whereKey($validated['room_id'])
                ->where('status', config('ekost.room_active_status', 'active'))
                ->whereHas('branchDorm', fn ($query) => $query->where(
                    'status',
                    config('ekost.property_active_status', 'active')
                ))
                ->lockForUpdate()
                ->firstOrFail();

            $availability = $availabilityService->checkPeriod(
                $room,
                $startDate,
                $endDate
            );

            if (!$availability['available']) {
                throw ValidationException::withMessages([
                    'room_id' => $availability['message'],
                ]);
            }

            $monthlyPrice = (float) $room->monthly_price;
            $totalPrice = $monthlyPrice * $durationMonth;

            // Batas pembayaran sementara: 30 menit.
            $expiresAt = now()->addMinutes(30);

            $booking = Booking::create([
                'tenant_id' => $tenant->id,
                'room_id' => $room->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'duration_month' => $durationMonth,
                'price_snapshot' => $monthlyPrice,
                'status' => 'pending_payment',
                'expires_at' => $expiresAt,
            ]);

            // Generate kode booking setelah ID terbentuk.
            $booking->update([
                'booking_code' => 'BOOKING-' . $booking->id,
            ]);

            // Buat percobaan pembayaran pertama.
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $totalPrice,
                'status' => 'pending',
                'expires_at' => $expiresAt,
            ]);

            // Generate kode pembayaran setelah ID terbentuk.
            $payment->update([
                'payment_code' => 'PAY-' . $payment->id,
            ]);

            return $booking;
        });

        app(OwnerNotificationService::class)->bookingCreated($booking);

        return redirect()
            ->route('tenant.bookings.show', $booking->booking_code)
            ->with('success', 'Booking berhasil dibuat. Silakan lanjutkan pembayaran.');
    }

    public function cancel(Request $request, string $bookingCode)
    {
        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403);

        $result = DB::transaction(function () use ($tenant, $bookingCode, $validated) {
            $booking = Booking::query()
                ->where('tenant_id', $tenant->id)
                ->where('booking_code', $bookingCode)
                ->lockForUpdate()
                ->firstOrFail();

            // Booking yang sudah dibayar tidak boleh dibatalkan tenant.
            $payments = $booking->payments()
                ->lockForUpdate()
                ->get();

            if (
                $booking->status === 'confirmed' ||
                $payments->contains(fn ($payment) => $payment->status === 'paid')
            ) {
                return 'already_paid';
            }

            if ($booking->status !== 'pending_payment') {
                return $booking->status === 'expired' ? 'expired' : 'not_cancellable';
            }

            // Jika batas waktu sudah lewat, pertahankan status expired,
            // bukan mengubahnya menjadi cancelled.
            if ($booking->expires_at && $booking->expires_at->isPast()) {
                $booking->update(['status' => 'expired']);

                $booking->payments()
                    ->where('status', 'pending')
                    ->update(['status' => 'expired']);

                return 'expired';
            }

            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['cancellation_reason'] ?? null,
            ]);

            // Hanya payment yang masih pending yang dibatalkan.
            $booking->payments()
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            return 'cancelled';
        });

        if ($result === 'cancelled') {
            $cancelledBooking = Booking::with(['room.branchDorm', 'tenant.user'])->where('booking_code', $bookingCode)->first();
            if ($cancelledBooking) app(OwnerNotificationService::class)->bookingCancelled($cancelledBooking);
        }

        return match ($result) {
            'cancelled' => redirect()
                ->route('tenant.bookings.show', $bookingCode)
                ->with('success', 'Booking berhasil dibatalkan. Kamar sekarang tersedia kembali.'),

            'already_paid' => redirect()
                ->route('tenant.bookings.show', $bookingCode)
                ->with('error', 'Booking yang sudah dibayar tidak dapat dibatalkan.'),

            'expired' => redirect()
                ->route('tenant.bookings.show', $bookingCode)
                ->with('error', 'Masa pembayaran telah berakhir. Booking berstatus kedaluwarsa.'),

            default => redirect()
                ->route('tenant.bookings.show', $bookingCode)
                ->with('error', 'Booking ini tidak dapat dibatalkan.'),
        };
    }

    public function show(Request $request, string $bookingCode)
    {
        $tenant = $request->user()->tenant;

        abort_if(!$tenant, 403);

        $booking = Booking::query()
            ->with(['room', 'payments'])
            ->where('tenant_id', $tenant->id)
            ->where('booking_code', $bookingCode)
            ->firstOrFail();

        return view('pages.tenant.bookings.show', compact('booking'));
    }
}
