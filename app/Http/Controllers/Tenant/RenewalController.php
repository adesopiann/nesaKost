<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Rent;
use App\Models\Room;
use App\Services\RoomAvailabilityService;
use App\Services\TenantRentalPeriodGuard;
use App\Services\OwnerNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RenewalController extends Controller
{
    private const NOTICE_DAYS = 30;
    private const DEADLINE_DAYS = 7;
    private const PAYMENT_HOURS = 72;

    public function create(Request $request, string $rentCode)
    {
        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403);

        $rent = Rent::query()
            ->with('room.branchDorm')
            ->where('tenant_id', $tenant->id)
            ->where('rent_code', $rentCode)
            ->firstOrFail();

        $eligibility = $this->eligibility($rent);
        // Satu kali percobaan renewal saja untuk setiap rent.
        // Status expired/failed/cancelled tetap dianggap sebagai percobaan
        // yang sudah digunakan dan tidak dapat diajukan ulang.
        $existingRenewal = $rent->renewalBookings()
            ->latest('id')
            ->first();

        return view('pages.tenant.renewals.create', compact(
            'rent', 'eligibility', 'existingRenewal'
        ));
    }

    public function store(
        Request $request,
        string $rentCode,
        RoomAvailabilityService $availabilityService,
        TenantRentalPeriodGuard $periodGuard
    ) {
        $validated = $request->validate([
            'duration_month' => ['required', 'integer', 'in:1,3,6'],
        ]);

        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403);

        try {
            $booking = DB::transaction(function () use (
                $tenant, $rentCode, $validated, $availabilityService, $periodGuard
            ) {
                // Gunakan baris tenant sebagai mutex bersama dengan proses
                // booking reguler agar kedua alur tidak membuat periode ganda.
                $lockedTenant = $periodGuard->lockTenant($tenant->id);

                $rent = Rent::query()
                    ->where('tenant_id', $tenant->id)
                    ->lockForUpdate()
                    ->where('rent_code', $rentCode)
                    ->firstOrFail();

                $eligibility = $this->eligibility($rent);
                if (!$eligibility['eligible']) {
                    throw ValidationException::withMessages([
                        'duration_month' => $eligibility['message'],
                    ]);
                }

                // Renewal hanya boleh diajukan satu kali untuk satu rent,
                // termasuk jika pengajuan sebelumnya sudah expired/cancelled.
                $existingRenewal = Booking::query()
                    ->where('previous_rent_id', $rent->id)
                    ->lockForUpdate()
                    ->exists();

                if ($existingRenewal) {
                    throw ValidationException::withMessages([
                        'duration_month' => 'Kamu sudah memiliki pengajuan perpanjangan yang masih diproses atau sudah berhasil.',
                    ]);
                }

                $room = Room::query()->whereKey($rent->room_id)->lockForUpdate()->firstOrFail();
                $startDate = CarbonImmutable::parse($rent->end_date)->addDay();
                $duration = (int) $validated['duration_month'];
                $endDate = $startDate->addMonthsNoOverflow($duration)->subDay();

                if ($periodGuard->hasConflict($lockedTenant, $startDate, $endDate)) {
                    throw ValidationException::withMessages([
                        'duration_month' => 'Periode perpanjangan bertabrakan dengan masa sewa atau booking lain yang masih menunggu pembayaran.',
                    ]);
                }

                $availability = $availabilityService->checkPeriod(
                    $room,
                    $startDate,
                    $endDate,
                    $rent->id
                );

                if (!$availability['available']) {
                    throw ValidationException::withMessages([
                        'duration_month' => $availability['message'],
                    ]);
                }

                $monthlyPrice = (float) $room->monthly_price;
                $expiresAt = now()->addHours(self::PAYMENT_HOURS);

                $booking = Booking::create([
                    'tenant_id' => $tenant->id,
                    'room_id' => $room->id,
                    'booking_type' => 'renewal',
                    'previous_rent_id' => $rent->id,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'duration_month' => $duration,
                    'price_snapshot' => $monthlyPrice,
                    'status' => 'pending_payment',
                    'expires_at' => $expiresAt,
                ]);

                $booking->update(['booking_code' => 'BOOKING-' . $booking->id]);

                $payment = Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $monthlyPrice * $duration,
                    'status' => 'pending',
                    'expires_at' => $expiresAt,
                ]);
                $payment->update(['payment_code' => 'PAY-' . $payment->id]);

                return $booking;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        }

        app(OwnerNotificationService::class)->bookingCreated($booking);

        return redirect()
            ->route('tenant.bookings.show', $booking)
            ->with('success', 'Pengajuan perpanjangan berhasil dibuat. Selesaikan pembayaran dalam 72 jam agar kamar tetap dialokasikan untukmu.');
    }

    private function eligibility(Rent $rent): array
    {
        $today = CarbonImmutable::today(config('app.timezone'));
        $rentEnd = CarbonImmutable::parse($rent->end_date)->startOfDay();
        $opensAt = $rentEnd->subDays(self::NOTICE_DAYS);
        $closesAt = $rentEnd->subDays(self::DEADLINE_DAYS);

        $eligibleStatus = in_array($rent->status, ['active', 'scheduled'], true);
        $eligibleWindow = $today->betweenIncluded($opensAt, $closesAt);

        if (!$eligibleStatus) {
            return ['eligible' => false, 'message' => 'Hanya masa sewa aktif atau terjadwal yang dapat diperpanjang.'];
        }

        if (!$eligibleWindow) {
            return [
                'eligible' => false,
                'message' => 'Pengajuan perpanjangan hanya dapat dilakukan mulai H-30 sampai H-7 sebelum masa sewa berakhir.',
            ];
        }

        return [
            'eligible' => true,
            'message' => 'Pengajuan perpanjangan tersedia.',
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'rent_end' => $rentEnd,
            'days_left' => $today->diffInDays($rentEnd),
        ];
    }
}
