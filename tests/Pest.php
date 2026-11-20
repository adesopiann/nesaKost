<?php

use App\Models\Booking;
use App\Models\BranchDorm;
use App\Models\Owner;
use App\Models\Rent;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function createTestingTenant(string $prefix = 'tenant'): array
{
    $user = User::query()->create([
        'email' => $prefix . '-' . Str::uuid() . '@example.test',
        'fname' => 'Test',
        'lname' => 'Tenant',
        'address' => 'Surabaya',
        'password' => bcrypt('password'),
        'role' => 'tenant',
    ]);

    return [$user, Tenant::query()->create(['user_id' => $user->id])];
}

function createTestingRoom(string $code = 'TEST-ROOM'): Room
{
    $ownerUser = User::query()->create([
        'email' => 'owner-' . Str::uuid() . '@example.test',
        'fname' => 'Test',
        'lname' => 'Owner',
        'address' => 'Surabaya',
        'password' => bcrypt('password'),
        'role' => 'owner',
    ]);

    $owner = Owner::query()->create(['user_id' => $ownerUser->id, 'address' => 'Surabaya']);
    $branch = BranchDorm::query()->create([
        'owner_id' => $owner->id,
        'name' => 'Test Kost',
        'address' => 'Surabaya',
        'status' => 'active',
    ]);

    return Room::query()->create([
        'room_code' => $code . '-' . Str::random(6),
        'branch_dorm_id' => $branch->id,
        'type' => 'standard',
        'monthly_price' => 900000,
        'status' => 'active',
    ]);
}

function createTestingBooking(
    Tenant $tenant,
    Room $room,
    string $start,
    string $end,
    string $status = 'confirmed',
    ?\Illuminate\Support\Carbon $expiresAt = null,
    ?int $previousRentId = null,
    string $bookingType = 'new'
): Booking {
    return Booking::query()->create([
        'tenant_id' => $tenant->id,
        'room_id' => $room->id,
        'booking_type' => $bookingType,
        'previous_rent_id' => $previousRentId,
        'start_date' => $start,
        'end_date' => $end,
        'duration_month' => 1,
        'price_snapshot' => 900000,
        'status' => $status,
        'expires_at' => $expiresAt,
    ]);
}

function createTestingRent(
    Tenant $tenant,
    Room $room,
    string $start,
    string $end,
    string $status = 'active'
): Rent {
    $booking = createTestingBooking($tenant, $room, $start, $end, 'confirmed');

    return Rent::query()->create([
        'booking_id' => $booking->id,
        'tenant_id' => $tenant->id,
        'room_id' => $room->id,
        'start_date' => $start,
        'end_date' => $end,
        'status' => $status,
        'total_price' => 900000,
    ]);
}

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function something()
{
    // ..
}
