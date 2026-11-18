<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_expiration_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rent_id')->constrained('rent')->cascadeOnDelete();
            $table->string('reminder_type', 10)->comment('H-30, H-7, atau H');
            $table->timestamp('sent_at');
            $table->timestamps();

            // Satu jenis pengingat hanya boleh tercatat sekali untuk setiap rent.
            $table->unique(['rent_id', 'reminder_type'], 'rent_reminder_unique');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_expiration_reminders');
    }
};
