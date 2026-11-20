<?php

use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2026-11-20 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function createTenantBookingHttpContext(): array
{
    [$user, $tenant] = createTestingTenant('booking-tenant');
    $room = createTestingRoom('BOOKING');

    return [$user, $tenant, $room];
}

it('rejects a regular booking when the tenant has an overlapping active rent', function () {
    [$user, $tenant, $room] = createTenantBookingHttpContext();
    createTestingRent($tenant, $room, '2026-12-01', '2026-12-31', 'active');

    $response = $this->actingAs($user)->from('/tenant/bookings')
        ->post(route('tenant.bookings.store'), [
            'room_id' => $room->id,
            'start_date' => '2026-12-15',
            'duration_month' => 1,
        ]);

    $response->assertSessionHasErrors('room_id');
    expect(Booking::query()->where('tenant_id', $tenant->id)->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(0);
});

it('rejects a regular booking when the tenant has an unexpired pending payment booking', function () {
    [$user, $tenant, $room] = createTenantBookingHttpContext();
    createTestingBooking($tenant, $room, '2026-12-01', '2026-12-31', 'pending_payment', now()->addMinutes(30));

    $response = $this->actingAs($user)->from('/tenant/bookings')
        ->post(route('tenant.bookings.store'), [
            'room_id' => $room->id,
            'start_date' => '2026-12-15',
            'duration_month' => 1,
        ]);

    $response->assertSessionHasErrors('room_id');
    expect(Booking::query()->where('tenant_id', $tenant->id)->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(0);
});

it('allows a regular booking when the previous pending payment booking has expired', function () {
    [$user, $tenant, $room] = createTenantBookingHttpContext();
    createTestingBooking($tenant, $room, '2026-12-01', '2026-12-31', 'pending_payment', now()->subSecond());

    $response = $this->actingAs($user)->from('/tenant/bookings')
        ->post(route('tenant.bookings.store'), [
            'room_id' => $room->id,
            'start_date' => '2026-12-15',
            'duration_month' => 1,
        ]);

    $response->assertRedirect();
    expect(Booking::query()->where('tenant_id', $tenant->id)->count())->toBe(2)
        ->and(Payment::query()->count())->toBe(1);
});

it('creates a booking and payment when the tenant rental periods do not overlap', function () {
    [$user, $tenant, $room] = createTenantBookingHttpContext();
    createTestingRent($tenant, $room, '2026-12-01', '2026-12-31', 'active');

    $response = $this->actingAs($user)->from('/tenant/bookings')
        ->post(route('tenant.bookings.store'), [
            'room_id' => $room->id,
            'start_date' => '2027-01-01',
            'duration_month' => 1,
        ]);

    $response->assertRedirect();
    expect(Booking::query()->where('tenant_id', $tenant->id)->count())->toBe(2)
        ->and(Payment::query()->count())->toBe(1);
});
