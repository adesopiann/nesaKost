<?php

use App\Mail\RentExpirationReminderMail;
use App\Models\RentExpirationReminderEmail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

function createReminderEmailRecord(array $trackingOverrides = []): RentExpirationReminderEmail
{
    $user = User::query()->create([
        'fname' => 'Sopian',
        'lname' => 'Ade',
        'email' => 'sopian-' . \Illuminate\Support\Str::uuid() . '@example.test',
        'password' => 'password',
        'role' => 'tenant',
        'address' => '-',
    ]);

    $user->forceFill(['email_verified_at' => now()])->save();

    $tenant = Tenant::query()->create(['user_id' => $user->id]);
    $room = createTestingRoom('REMINDER');
    $booking = createTestingBooking(
        $tenant,
        $room,
        now()->toDateString(),
        now()->addDays(30)->toDateString(),
        'confirmed'
    );

    $rent = \App\Models\Rent::query()->create([
        'rent_code' => 'RENT-' . \Illuminate\Support\Str::random(8),
        'booking_id' => $booking->id,
        'tenant_id' => $tenant->id,
        'room_id' => $room->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
        'status' => 'active',
        'total_price' => 900000,
    ]);

    $createdAt = $trackingOverrides['created_at'] ?? now()->subMinutes(20);
    $updatedAt = $trackingOverrides['updated_at'] ?? now()->subMinutes(6);
    unset($trackingOverrides['created_at'], $trackingOverrides['updated_at']);

    $record = RentExpirationReminderEmail::query()->create(array_merge([
        'rent_id' => $rent->id,
        'user_id' => $user->id,
        'reminder_type' => 'H-30',
        'recipient_email' => 'old-address@example.test',
        'status' => RentExpirationReminderEmail::STATUS_FAILED,
        'attempts' => 1,
        'last_attempt_at' => now()->subMinutes(6),
    ], $trackingOverrides));

    $record->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $updatedAt,
    ])->saveQuietly();

    return $record->fresh();
}

it('retries a failed reminder after the retry delay and uses the current verified email', function () {
    $record = createReminderEmailRecord();

    $this->artisan('ekost:retry-rent-expiration-emails')
        ->assertExitCode(0);

    $record->refresh();

    expect($record->status)->toBe(RentExpirationReminderEmail::STATUS_SENT)
        ->and($record->attempts)->toBe(2)
        ->and($record->recipient_email)->toBe($record->user->email)
        ->and($record->sent_at)->not->toBeNull()
        ->and($record->last_error)->toBeNull();

    Mail::assertSent(RentExpirationReminderMail::class, function (RentExpirationReminderMail $mail) use ($record) {
        return $mail->hasTo($record->user->email)
            && $mail->reminderType === 'H-30';
    });
});

it('recovers a pending reminder that has been stuck for more than ten minutes', function () {
    $record = createReminderEmailRecord([
        'status' => RentExpirationReminderEmail::STATUS_PENDING,
        'attempts' => 0,
        'last_attempt_at' => null,
        'created_at' => now()->subMinutes(11),
        'updated_at' => now()->subMinutes(11),
    ]);

    $this->artisan('ekost:retry-rent-expiration-emails')->assertExitCode(0);
    $record->refresh();

    expect($record->status)->toBe(RentExpirationReminderEmail::STATUS_SENT)
        ->and($record->attempts)->toBe(1)
        ->and($record->sent_at)->not->toBeNull();

    Mail::assertSent(RentExpirationReminderMail::class);
});

it('does not retry a pending reminder that is younger than ten minutes', function () {
    $record = createReminderEmailRecord([
        'status' => RentExpirationReminderEmail::STATUS_PENDING,
        'attempts' => 0,
        'last_attempt_at' => null,
        'created_at' => now()->subMinutes(5),
        'updated_at' => now()->subMinutes(5),
    ]);

    $this->artisan('ekost:retry-rent-expiration-emails')->assertExitCode(0);
    $record->refresh();

    expect($record->status)->toBe(RentExpirationReminderEmail::STATUS_PENDING)
        ->and($record->attempts)->toBe(0);

    Mail::assertNothingSent();
});

it('does not resend an email that is already marked as sent', function () {
    $record = createReminderEmailRecord([
        'status' => RentExpirationReminderEmail::STATUS_SENT,
        'attempts' => 2,
        'sent_at' => now()->subMinute(),
        'last_attempt_at' => now()->subMinute(),
        'failed_at' => null,
        'last_error' => null,
    ]);

    $this->artisan('ekost:retry-rent-expiration-emails --force')->assertExitCode(0);
    $record->refresh();

    expect($record->status)->toBe(RentExpirationReminderEmail::STATUS_SENT)
        ->and($record->attempts)->toBe(2);

    Mail::assertNothingSent();
});
