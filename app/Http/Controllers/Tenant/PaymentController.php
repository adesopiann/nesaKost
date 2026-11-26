<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Rent;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\OwnerNotificationService;

class PaymentController extends Controller
{
    private const PAYMENT_METHODS = [
        'va_bca' => ['label' => 'BCA Virtual Account', 'group' => 'Virtual Account', 'bank' => 'BCA'],
        'va_mandiri' => ['label' => 'Mandiri Virtual Account', 'group' => 'Virtual Account', 'bank' => 'Mandiri'],
        'va_bni' => ['label' => 'BNI Virtual Account', 'group' => 'Virtual Account', 'bank' => 'BNI'],
        'va_bri' => ['label' => 'BRI Virtual Account', 'group' => 'Virtual Account', 'bank' => 'BRI'],
        'va_permata' => ['label' => 'Permata Virtual Account', 'group' => 'Virtual Account', 'bank' => 'Permata'],
        'qris' => ['label' => 'QRIS', 'group' => 'QRIS', 'bank' => null],
        'gopay' => ['label' => 'GoPay', 'group' => 'E-wallet', 'bank' => null],
        'ovo' => ['label' => 'OVO', 'group' => 'E-wallet', 'bank' => null],
        'dana' => ['label' => 'DANA', 'group' => 'E-wallet', 'bank' => null],
        'shopeepay' => ['label' => 'ShopeePay', 'group' => 'E-wallet', 'bank' => null],
    ];

    /** Show the payment-method selection page. */
    public function create(Request $request, string $bookingCode)
    {
        $booking = $this->ownedBooking($request, $bookingCode);

        if ($this->expireBookingIfNeeded($booking)) {
            return redirect()->route('tenant.bookings.show', $booking->booking_code)
                ->with('error', 'Batas waktu pembayaran telah berakhir. Booking sudah kedaluwarsa.');
        }

        abort_unless($booking->status === 'pending_payment', 403, 'Booking ini tidak menunggu pembayaran.');

        $latestPayment = $booking->payments()->latest('id')->first();
        abort_if($latestPayment?->status === 'paid', 403, 'Pembayaran booking ini sudah berhasil.');

        return view('pages.tenant.bookings.payment-method', [
            'booking' => $booking->load('room'),
            'latestPayment' => $latestPayment,
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }

    /** Save the selected channel and create/reuse a payment attempt. */
    public function selectMethod(Request $request, string $bookingCode)
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', Rule::in(array_keys(self::PAYMENT_METHODS))],
        ]);

        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403);

        $result = DB::transaction(function () use ($tenant, $bookingCode, $validated) {
            $booking = Booking::query()
                ->where('tenant_id', $tenant->id)
                ->where('booking_code', $bookingCode)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== 'pending_payment') {
                return ['state' => 'not_pending'];
            }

            if ($booking->expires_at && $booking->expires_at->isPast()) {
                $booking->update(['status' => 'expired']);
                $booking->payments()->where('status', 'pending')->update(['status' => 'expired']);
                return ['state' => 'expired'];
            }

            $payment = $booking->payments()->lockForUpdate()->latest('id')->first();

            if ($payment?->status === 'paid') {
                return ['state' => 'already_paid'];
            }

            // Reuse the initial unassigned attempt or an existing pending attempt
            // when the tenant changes method. A failed attempt remains in history.
            if (!$payment || in_array($payment->status, ['failed', 'expired', 'cancelled'], true)) {
                $payment = Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $payment?->amount ?? ((float) $booking->price_snapshot * (int) $booking->duration_month),
                    'status' => 'pending',
                    'expires_at' => $booking->expires_at,
                ]);
                $payment->update(['payment_code' => 'PAY-' . $payment->id]);
            } elseif ($payment->status !== 'pending') {
                return ['state' => 'not_pending'];
            }

            $method = self::PAYMENT_METHODS[$validated['payment_method']];
            $reference = $this->makePaymentReference($validated['payment_method'], $booking->id, $payment->id);
            $instruction = $this->makePaymentInstruction($validated['payment_method'], $reference, (float) $payment->amount);

            $payment->update([
                'payment_method' => $validated['payment_method'],
                'provider' => 'simulation',
                'provider_reference' => $reference,
                'payment_reference' => $reference,
                'payment_instruction' => $instruction,
                'status' => 'pending',
                'expires_at' => $booking->expires_at,
            ]);

            return ['state' => 'ready', 'payment_code' => $payment->payment_code];
        });

        if ($result['state'] === 'expired') {
            return redirect()->route('tenant.bookings.show', $bookingCode)
                ->with('error', 'Batas waktu pembayaran telah berakhir. Booking sudah kedaluwarsa.');
        }

        if ($result['state'] !== 'ready') {
            return redirect()->route('tenant.bookings.show', $bookingCode)
                ->with('error', 'Booking ini tidak lagi dapat menerima pembayaran baru.');
        }

        return redirect()->route('tenant.bookings.payments.show', [
            'bookingCode' => $bookingCode,
            'paymentCode' => $result['payment_code'],
        ]);
    }

    /** Show payment instructions for one attempt. */
    public function show(Request $request, string $bookingCode, string $paymentCode)
    {
        $booking = $this->ownedBooking($request, $bookingCode);

        if ($this->expireBookingIfNeeded($booking)) {
            return redirect()->route('tenant.bookings.show', $booking->booking_code)
                ->with('error', 'Batas waktu pembayaran telah berakhir. Booking sudah kedaluwarsa.');
        }

        abort_unless($booking->status === 'pending_payment', 403, 'Booking ini tidak menunggu pembayaran.');

        $payment = $booking->payments()->where('payment_code', $paymentCode)->firstOrFail();
        abort_unless($payment->status === 'pending' && $payment->payment_method, 403, 'Instruksi pembayaran ini tidak lagi aktif.');

        return view('pages.tenant.bookings.payment-instruction', [
            'booking' => $booking->load('room'),
            'payment' => $payment,
            'method' => self::PAYMENT_METHODS[$payment->payment_method] ?? ['label' => strtoupper($payment->payment_method), 'group' => 'Pembayaran'],
        ]);
    }

    /** Simulate the payment provider callback/result. */
    public function simulate(Request $request, string $bookingCode, string $paymentCode)
    {
        $validated = $request->validate([
            'outcome' => ['required', Rule::in(['success', 'failed'])],
        ]);

        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403);

        $result = DB::transaction(function () use ($tenant, $bookingCode, $paymentCode, $validated) {
            $booking = Booking::query()
                ->where('tenant_id', $tenant->id)
                ->where('booking_code', $bookingCode)
                ->lockForUpdate()
                ->firstOrFail();

            $payment = $booking->payments()->lockForUpdate()->where('payment_code', $paymentCode)->firstOrFail();

            if ($payment->status === 'paid') {
                return 'already_paid';
            }

            if ($booking->status !== 'pending_payment' || $payment->status !== 'pending') {
                return 'not_pending';
            }

            if ($booking->expires_at && $booking->expires_at->isPast()) {
                $booking->update(['status' => 'expired']);
                $booking->payments()->where('status', 'pending')->update(['status' => 'expired']);
                return 'expired';
            }

            if ($validated['outcome'] === 'failed') {
                $payment->update(['status' => 'failed']);
                return 'failed';
            }

            $payment->update(['status' => 'paid', 'paid_at' => now()]);
            $booking->update(['status' => 'confirmed']);

            $startDate = CarbonImmutable::parse($booking->start_date)->startOfDay();
            $rentStatus = $startDate->greaterThan(CarbonImmutable::today(config('app.timezone')))
                ? 'scheduled'
                : 'active';

            $rent = Rent::firstOrCreate(
                ['booking_id' => $booking->id],
                [
                    'tenant_id' => $booking->tenant_id,
                    'room_id' => $booking->room_id,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'status' => $rentStatus,
                    'total_price' => $payment->amount,
                ]
            );

            if (!$rent->rent_code) {
                $rent->update(['rent_code' => 'RENT-' . $rent->id]);
            }

            return 'success';
        });

        if ($result === 'success') {
            $paidBooking = Booking::with(['room.branchDorm.owner.user', 'tenant.user', 'payments'])->where('booking_code', $bookingCode)->first();
            if ($paidBooking) {
                $paidPayment = $paidBooking->payments->firstWhere('payment_code', $paymentCode);
                app(OwnerNotificationService::class)->paymentPaid($paidBooking, $paidPayment?->id);
                if ($paidBooking->rent) app(OwnerNotificationService::class)->rentCreated($paidBooking->rent);
            }
        }

        if ($result === 'success' || $result === 'already_paid') {
            $bookingType = Booking::where('booking_code', $bookingCode)->value('booking_type');
            $message = $bookingType === 'renewal'
                ? 'Pembayaran perpanjangan berhasil. Masa sewa baru telah dijadwalkan.'
                : 'Simulasi pembayaran berhasil. Masa sewa telah dibuat.';

            return redirect()->route('tenant.bookings.show', $bookingCode)
                ->with('success', $message);
        }

        if ($result === 'failed') {
            return redirect()->route('tenant.bookings.show', $bookingCode)
                ->with('error', 'Simulasi pembayaran gagal. Kamu dapat mencoba metode lain selama booking masih berlaku.');
        }

        return redirect()->route('tenant.bookings.show', $bookingCode)
            ->with('error', $result === 'expired'
                ? 'Batas waktu pembayaran telah berakhir. Booking sudah kedaluwarsa.'
                : 'Pembayaran atau booking ini tidak lagi dapat diproses.');
    }

    private function ownedBooking(Request $request, string $bookingCode): Booking
    {
        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403);

        return Booking::query()
            ->where('tenant_id', $tenant->id)
            ->where('booking_code', $bookingCode)
            ->with('payments')
            ->firstOrFail();
    }

    private function expireBookingIfNeeded(Booking $booking): bool
    {
        if ($booking->status !== 'pending_payment' || !$booking->expires_at || !$booking->expires_at->isPast()) {
            return false;
        }

        DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            if ($lockedBooking->status === 'pending_payment' && $lockedBooking->expires_at?->isPast()) {
                $lockedBooking->update(['status' => 'expired']);
                $lockedBooking->payments()->where('status', 'pending')->update(['status' => 'expired']);
            }
        });

        return true;
    }

    private function makePaymentReference(string $channel, int $bookingId, int $paymentId): string
    {
        if (str_starts_with($channel, 'va_')) {
            $prefixes = [
                'va_bca' => '8808',
                'va_mandiri' => '70012',
                'va_bni' => '988',
                'va_bri' => '26215',
                'va_permata' => '8528',
            ];

            return ($prefixes[$channel] ?? '8808') . str_pad((string) (($bookingId * 100000) + $paymentId), 10, '0', STR_PAD_LEFT);
        }

        return strtoupper($channel) . '-SIM-' . $bookingId . '-' . $paymentId;
    }

    private function makePaymentInstruction(string $channel, string $reference, float $amount): string
    {
        $formattedAmount = 'Rp' . number_format($amount, 0, ',', '.');

        if (str_starts_with($channel, 'va_')) {
            $bank = self::PAYMENT_METHODS[$channel]['bank'];
            return "SIMULASI {$bank} Virtual Account. Nomor VA: {$reference}. Transfer tepat {$formattedAmount}. Nomor ini hanya contoh dan tidak dapat digunakan untuk transfer sungguhan.";
        }

        if ($channel === 'qris') {
            return "QRIS SIMULASI {$reference}. Nominal pembayaran: {$formattedAmount}. Kode ini bukan QRIS aktif dan tidak dapat dipindai untuk pembayaran sungguhan.";
        }

        $wallet = self::PAYMENT_METHODS[$channel]['label'];
        return "SIMULASI {$wallet}. Nominal pembayaran: {$formattedAmount}. Pada integrasi gateway nanti, pengguna akan diarahkan ke aplikasi {$wallet}. Saat ini tidak ada transaksi sungguhan.";
    }
}
