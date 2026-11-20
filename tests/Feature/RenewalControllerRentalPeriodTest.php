<?php

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Rent;
use App\Models\Room;
use App\Models\Tenant;
use Carbon\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-11-20 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function createRenewalHttpContext(): array
{
    [$user, $tenant] = createTestingTenant('renewal-tenant');
    $room = createTestingRoom('RENEWAL');

    $currentRent = createTestingRent(
        $tenant,
        $room,
        '2026-11-01',
        '2026-12-10',
        'active'
    );

    return [$user, $tenant, $room, $currentRent];
}

function createConflictingRenewalRent(Tenant $tenant, Room $room): Rent
{
    return createTestingRent($tenant, $room, '2026-12-20', '2027-01-20', 'scheduled');
}

function createConflictingRenewalPendingBooking(Tenant $tenant, Room $room, Carbon $expiresAt): Booking
{
    return createTestingBooking(
        $tenant,
        $room,
        '2026-12-20',
        '2027-01-20',
        'pending_payment',
        $expiresAt
    );
}

it('rejects a renewal when the tenant has another overlapping scheduled rent', function () {
    [$user, $tenant, $room, $currentRent] = createRenewalHttpContext();
    $otherRoom = createTestingRoom('RENEWAL-OTHER');
    createConflictingRenewalRent($tenant, $otherRoom);

    $response = $this->actingAs($user)->from('/tenant/bookings')
        ->post(route('tenant.rents.renewal.store', $currentRent->id), [
            'duration_month' => 1,
        ]);

    $response->assertSessionHasErrors('duration_month');
    expect(Booking::query()->where('booking_type', 'renewal')->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
});

it('rejects a renewal when the tenant has an unexpired pending payment booking on another room', function () {
    [$user, $tenant, $room, $currentRent] = createRenewalHttpContext();
    $otherRoom = createTestingRoom('RENEWAL-OTHER');
    createConflictingRenewalPendingBooking($tenant, $otherRoom, now()->addMinutes(30));

    $response = $this->actingAs($user)->from('/tenant/bookings')
        ->post(route('tenant.rents.renewal.store', $currentRent->id), [
            'duration_month' => 1,
        ]);

    $response->assertSessionHasErrors('duration_month');
    expect(Booking::query()->where('booking_type', 'renewal')->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
});

it('allows a renewal when an overlapping pending payment booking has expired', function () {
    [$user, $tenant, $room, $currentRent] = createRenewalHttpContext();
    $otherRoom = createTestingRoom('RENEWAL-OTHER');
    createConflictingRenewalPendingBooking($tenant, $otherRoom, now()->subSecond());

    $response = $this->actingAs($user)->from('/tenant/bookings')
        ->post(route('tenant.rents.renewal.store', $currentRent->id), [
            'duration_month' => 1,
        ]);

    $renewal = Booking::query()->where('booking_type', 'renewal')->first();

    $response->assertRedirect(route('tenant.bookings.show', $renewal->id));
    expect($renewal)->not->toBeNull()
        ->and($renewal->previous_rent_id)->toBe($currentRent->id)
        ->and($renewal->start_date->toDateString())->toBe('2026-12-11')
        ->and($renewal->end_date->toDateString())->toBe('2027-01-10');

    expect(Payment::query()->count())->toBe(1);
});

it('creates a renewal booking and payment when the requested period has no tenant conflict', function () {
    [$user, $tenant, $room, $currentRent] = createRenewalHttpContext();

    $response = $this->actingAs($user)->from('/tenant/bookings')
        ->post(route('tenant.rents.renewal.store', $currentRent->id), [
            'duration_month' => 1,
        ]);

    $renewal = Booking::query()->where('booking_type', 'renewal')->first();

    $response->assertRedirect(route('tenant.bookings.show', $renewal->id));

    expect($renewal)->not->toBeNull()
        ->and($renewal->tenant_id)->toBe($tenant->id)
        ->and($renewal->room_id)->toBe($room->id)
        ->and($renewal->previous_rent_id)->toBe($currentRent->id)
        ->and($renewal->start_date->toDateString())->toBe('2026-12-11')
        ->and($renewal->end_date->toDateString())->toBe('2027-01-10')
        ->and($renewal->status)->toBe('pending_payment');

    $payment = Payment::query()->first();

    expect($payment)->not->toBeNull()
        ->and((int) $payment->booking_id)->toBe($renewal->id)
        ->and((float) $payment->amount)->toBe(900000.0);
});
