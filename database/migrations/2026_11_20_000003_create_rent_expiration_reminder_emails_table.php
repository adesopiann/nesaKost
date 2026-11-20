<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_expiration_reminder_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_id')->constrained('rent')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('reminder_type', 10)->comment('H-30, H-7, atau H');
            $table->string('recipient_email');
            $table->string('status', 20)->default('pending')
                ->comment('pending, sent, failed, atau skipped_unverified');

            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            // Satu catatan pengiriman email untuk setiap rent dan jenis pengingat.
            $table->unique(
                ['rent_id', 'reminder_type'],
                'rent_expiration_email_unique'
            );

            $table->index(['status', 'created_at'], 'rent_expiration_email_status_idx');
            $table->index('last_attempt_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_expiration_reminder_emails');
    }
};
