<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Services\RoomAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(
        Request $request,
        RoomAvailabilityService $availabilityService
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

        // Satu tenant hanya boleh memiliki satu rent aktif/scheduled.
        $hasActiveRent = $tenant->rents()
            ->whereIn('status', ['scheduled', 'active'])
            ->exists();

        if ($hasActiveRent) {
            throw ValidationException::withMessages([
                'room_id' => 'Kamu masih memiliki masa sewa yang aktif. Selesaikan masa sewa tersebut sebelum melakukan booking baru.',
            ]);
        }

        $startDate = CarbonImmutable::parse($validated['start_date']);

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
            $availabilityService
        ) {
            // Kunci baris kamar selama transaksi untuk mengurangi risiko
            // dua booking bersamaan pada kamar yang sama.
            $room = Room::query()
                ->whereKey($validated['room_id'])
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

        return redirect()
            ->route('tenant.bookings.show', $booking->id)
            ->with('success', 'Booking berhasil dibuat. Silakan lanjutkan pembayaran.');
    }

    public function cancel(Request $request, int $id)
    {
        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403);

        $result = DB::transaction(function () use ($tenant, $id, $validated) {
            $booking = Booking::query()
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->findOrFail($id);

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

        return match ($result) {
            'cancelled' => redirect()
                ->route('tenant.bookings.show', $id)
                ->with('success', 'Booking berhasil dibatalkan. Kamar sekarang tersedia kembali.'),

            'already_paid' => redirect()
                ->route('tenant.bookings.show', $id)
                ->with('error', 'Booking yang sudah dibayar tidak dapat dibatalkan.'),

            'expired' => redirect()
                ->route('tenant.bookings.show', $id)
                ->with('error', 'Masa pembayaran telah berakhir. Booking berstatus kedaluwarsa.'),

            default => redirect()
                ->route('tenant.bookings.show', $id)
                ->with('error', 'Booking ini tidak dapat dibatalkan.'),
        };
    }

    public function show(Request $request, int $id)
    {
        $tenant = $request->user()->tenant;

        abort_if(!$tenant, 403);

        $booking = Booking::query()
            ->with(['room', 'payments'])
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        return view('pages.tenant.bookings.show', compact('booking'));
    }
}
