<?php

use App\Mail\RentExpirationReminderMail;
use App\Models\RentExpirationReminderEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

beforeEach(function () {
    Schema::dropIfExists('rent_expiration_reminder_emails');
    Schema::dropIfExists('rent');
    Schema::dropIfExists('room');
    Schema::dropIfExists('tenants');
    Schema::dropIfExists('users');

    // Bentuk tabel users mengikuti migration utama aplikasi eKost.
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('fname', 100)->nullable();
        $table->string('lname', 100)->nullable();
        $table->string('phone_number', 20)->nullable();
        $table->string('address', 250);
        $table->string('password');
        $table->string('role', 20)->index();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('tenants', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->timestamps();
    });

    Schema::create('room', function (Blueprint $table) {
        $table->id();
        $table->string('room_code')->nullable();
        $table->timestamps();
    });

    Schema::create('rent', function (Blueprint $table) {
        $table->id();
        $table->string('rent_code')->nullable();
        $table->unsignedBigInteger('booking_id')->nullable();
        $table->unsignedBigInteger('tenant_id');
        $table->unsignedBigInteger('room_id');
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->string('status')->default('active');
        $table->decimal('total_price', 12, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('rent_expiration_reminder_emails', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('rent_id');
        $table->unsignedBigInteger('user_id');
        $table->string('reminder_type', 10);
        $table->string('recipient_email');
        $table->string('status', 20)->default('pending');
        $table->unsignedSmallInteger('attempts')->default(0);
        $table->timestamp('last_attempt_at')->nullable();
        $table->timestamp('sent_at')->nullable();
        $table->timestamp('failed_at')->nullable();
        $table->text('last_error')->nullable();
        $table->timestamps();
        $table->unique(['rent_id', 'reminder_type']);
    });

    Mail::fake();
});

afterEach(function () {
    Schema::dropIfExists('rent_expiration_reminder_emails');
    Schema::dropIfExists('rent');
    Schema::dropIfExists('room');
    Schema::dropIfExists('tenants');
    Schema::dropIfExists('users');
});

function createReminderEmailRecord(array $trackingOverrides = []): RentExpirationReminderEmail
{
    $user = \App\Models\User::query()->create([
        'fname' => 'Sopian',
        'lname' => 'Ade',
        'email' => 'sopian@example.test',
        'password' => 'password',
        'role' => 'tenant',
        'address' => '-',
    ]);

    // email_verified_at tidak termasuk fillable User, jadi isi secara eksplisit.
    $user->forceFill(['email_verified_at' => now()])->save();

    $tenant = \App\Models\Tenant::query()->create([
        'user_id' => $user->id,
    ]);

    $room = \App\Models\Room::query()->create([
        'room_code' => 'TEST-101',
    ]);

    $rent = \App\Models\Rent::query()->create([
        'rent_code' => 'RENT-TEST-1',
        'booking_id' => 1,
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

    // created_at/updated_at bukan fillable, atur melalui forceFill agar
    // skenario pending stale benar-benar memiliki umur lebih dari 10 menit.
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
        ->and($record->recipient_email)->toBe('sopian@example.test')
        ->and($record->sent_at)->not->toBeNull()
        ->and($record->last_error)->toBeNull();

    Mail::assertSent(RentExpirationReminderMail::class, function (RentExpirationReminderMail $mail) {
        return $mail->hasTo('sopian@example.test')
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

    $this->artisan('ekost:retry-rent-expiration-emails')
        ->assertExitCode(0);

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

    $this->artisan('ekost:retry-rent-expiration-emails')
        ->assertExitCode(0);

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

    $this->artisan('ekost:retry-rent-expiration-emails --force')
        ->assertExitCode(0);

    $record->refresh();

    expect($record->status)->toBe(RentExpirationReminderEmail::STATUS_SENT)
        ->and($record->attempts)->toBe(2);

    Mail::assertNothingSent();
});
