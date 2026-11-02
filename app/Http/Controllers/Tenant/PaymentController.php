<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Rent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function simulate(
        Request $request,
        int $bookingId,
        int $paymentId
    ) {
        $validated = $request->validate([
            'outcome' => ['required', 'in:success,failed'],
        ]);

        $tenant = $request->user()->tenant;

        abort_if(!$tenant, 403);

        $result = DB::transaction(function () use (
            $tenant,
            $bookingId,
            $paymentId,
            $validated
        ) {
            $booking = Booking::query()
                ->where('tenant_id', $tenant->id)
                ->lockForUpdate()
                ->findOrFail($bookingId);

            $payment = Payment::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->findOrFail($paymentId);

            // Pembayaran yang sudah berhasil tidak boleh diproses ulang.
            if ($payment->status === 'paid') {
                return 'already_paid';
            }

            if ($booking->status !== 'pending_payment') {
                return 'booking_not_pending';
            }

            // Periksa batas waktu pembayaran.
            if (
                $booking->expires_at &&
                $booking->expires_at->isPast()
            ) {
                $booking->update(['status' => 'expired']);

                $booking->payments()
                    ->where('status', 'pending')
                    ->update(['status' => 'expired']);

                return 'expired';
            }

            if ($payment->status !== 'pending') {
                return 'payment_not_pending';
            }

            // SIMULASI PEMBAYARAN GAGAL
            if ($validated['outcome'] === 'failed') {
                $payment->update([
                    'status' => 'failed',
                    'payment_method' => 'simulation',
                ]);

                return 'failed';
            }

            // SIMULASI PEMBAYARAN BERHASIL
            $payment->update([
                'status' => 'paid',
                'payment_method' => 'simulation',
                'paid_at' => now(),
            ]);

            $booking->update([
                'status' => 'confirmed',
            ]);

            // Buat rent hanya jika belum ada.
            $rent = Rent::firstOrCreate(
                ['booking_id' => $booking->id],
                [
                    'tenant_id' => $booking->tenant_id,
                    'room_id' => $booking->room_id,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'status' => 'scheduled',
                    'total_price' => $payment->amount,
                ]
            );

            // Generate kode rent setelah ID terbentuk.
            if (!$rent->rent_code) {
                $rent->update([
                    'rent_code' => 'RENT-' . $rent->id,
                ]);
            }

            return 'success';
        });

        return match ($result) {
            'success' => redirect()
                ->route('tenant.bookings.show', $bookingId)
                ->with('success', 'Pembayaran berhasil. Masa sewa berhasil dibuat.'),

            'failed' => redirect()
                ->route('tenant.bookings.show', $bookingId)
                ->with('error', 'Simulasi pembayaran gagal. Kamu dapat mencoba metode pembayaran lain.'),

            'expired' => redirect()
                ->route('tenant.bookings.show', $bookingId)
                ->with('error', 'Batas waktu pembayaran telah berakhir. Booking sudah kedaluwarsa.'),

            'already_paid' => redirect()
                ->route('tenant.bookings.show', $bookingId)
                ->with('success', 'Pembayaran ini sudah berhasil sebelumnya.'),

            default => redirect()
                ->route('tenant.bookings.show', $bookingId)
                ->with('error', 'Pembayaran atau booking tidak lagi dapat diproses.'),
        };
    }
}
