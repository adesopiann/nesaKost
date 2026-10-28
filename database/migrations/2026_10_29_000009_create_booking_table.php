<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 30)->nullable()->unique()
                ->comment('Dibuat Laravel setelah ID terbentuk, format BOOKING-{id}');
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('room')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_month');
            $table->decimal('price_snapshot', 12, 2);
            $table->string('status', 30)->default('pending_payment')
                ->comment('pending_payment, confirmed, expired, cancelled');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'booking_tenant_status_index');
            $table->index(['room_id', 'status'], 'booking_room_status_index');
            $table->index(['start_date', 'end_date'], 'booking_dates_index');
            $table->unique(['id', 'tenant_id', 'room_id'], 'booking_id_tenant_room_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking');
    }
};
