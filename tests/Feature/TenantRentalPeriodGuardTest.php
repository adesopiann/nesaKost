<?php

use App\Services\TenantRentalPeriodGuard;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

beforeEach(function () {
    Carbon::setTestNow('2026-11-20 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('detects an overlap with an active rent', function () {
    [, $tenant] = createTestingTenant();
    $room = createTestingRoom();
    createTestingRent($tenant, $room, '2026-12-01', '2026-12-31', 'active');

    expect(app(TenantRentalPeriodGuard::class)->hasConflict(
        $tenant,
        CarbonImmutable::parse('2026-12-10'),
        CarbonImmutable::parse('2027-01-09')
    ))->toBeTrue();
});

it('detects an overlap with a scheduled rent', function () {
    [, $tenant] = createTestingTenant();
    $room = createTestingRoom();
    createTestingRent($tenant, $room, '2026-12-01', '2026-12-31', 'scheduled');

    expect(app(TenantRentalPeriodGuard::class)->hasConflict(
        $tenant,
        CarbonImmutable::parse('2026-11-25'),
        CarbonImmutable::parse('2026-12-24')
    ))->toBeTrue();
});

it('detects an overlap with a pending payment booking that has not expired', function () {
    [, $tenant] = createTestingTenant();
    $room = createTestingRoom();
    createTestingBooking($tenant, $room, '2026-12-01', '2026-12-31', 'pending_payment', now()->addMinutes(30));

    expect(app(TenantRentalPeriodGuard::class)->hasConflict(
        $tenant,
        CarbonImmutable::parse('2026-12-15'),
        CarbonImmutable::parse('2027-01-14')
    ))->toBeTrue();
});

it('ignores a pending payment booking whose payment deadline has passed', function () {
    [, $tenant] = createTestingTenant();
    $room = createTestingRoom();
    createTestingBooking($tenant, $room, '2026-12-01', '2026-12-31', 'pending_payment', now()->subSecond());

    expect(app(TenantRentalPeriodGuard::class)->hasConflict(
        $tenant,
        CarbonImmutable::parse('2026-12-15'),
        CarbonImmutable::parse('2027-01-14')
    ))->toBeFalse();
});

it('detects an overlap when the new booking starts before an existing rent', function () {
    [, $tenant] = createTestingTenant();
    $room = createTestingRoom();
    createTestingRent($tenant, $room, '2026-12-01', '2026-12-31', 'active');

    expect(app(TenantRentalPeriodGuard::class)->hasConflict(
        $tenant,
        CarbonImmutable::parse('2026-11-25'),
        CarbonImmutable::parse('2026-12-24')
    ))->toBeTrue();
});

it('detects a tenant period conflict even when the room is different', function () {
    [, $tenant] = createTestingTenant();
    $occupiedRoom = createTestingRoom('OCCUPIED');
    $otherRoom = createTestingRoom('OTHER');
    createTestingRent($tenant, $occupiedRoom, '2026-12-01', '2026-12-31', 'active');
    createTestingBooking($tenant, $otherRoom, '2026-12-01', '2026-12-31', 'pending_payment', now()->addMinutes(30));

    expect(app(TenantRentalPeriodGuard::class)->hasConflict(
        $tenant,
        CarbonImmutable::parse('2026-12-10'),
        CarbonImmutable::parse('2027-01-09')
    ))->toBeTrue();
});

it('allows a new period beginning the day after the previous rent ends', function () {
    [, $tenant] = createTestingTenant();
    $room = createTestingRoom();
    createTestingRent($tenant, $room, '2026-12-01', '2026-12-31', 'active');

    expect(app(TenantRentalPeriodGuard::class)->hasConflict(
        $tenant,
        CarbonImmutable::parse('2027-01-01'),
        CarbonImmutable::parse('2027-01-31')
    ))->toBeFalse();
});

it('treats shared boundary dates as an overlap because rent dates are inclusive', function () {
    [, $tenant] = createTestingTenant();
    $room = createTestingRoom();
    createTestingRent($tenant, $room, '2026-12-01', '2026-12-31', 'active');

    expect(app(TenantRentalPeriodGuard::class)->hasConflict(
        $tenant,
        CarbonImmutable::parse('2026-12-31'),
        CarbonImmutable::parse('2027-01-30')
    ))->toBeTrue();
});
