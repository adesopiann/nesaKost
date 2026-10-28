<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rent', function (Blueprint $table) {
            $table->id();
            $table->string('rent_code', 30)->nullable()->unique()
                ->comment('Dibuat Laravel setelah ID terbentuk, format RENT-{id}');
            $table->foreignId('booking_id')->unique()->constrained('booking')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('room_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('scheduled')
                ->comment('scheduled, active, completed, cancelled');
            $table->decimal('total_price', 12, 2);
            $table->timestamps();

            $table->index(['tenant_id', 'status'], 'rent_tenant_status_index');
            $table->index(['room_id', 'status'], 'rent_room_status_index');
            $table->index(['start_date', 'end_date'], 'rent_dates_index');
            $table->index(['booking_id', 'tenant_id', 'room_id'], 'rent_booking_tenant_room_index');

            $table->foreign(['booking_id', 'tenant_id', 'room_id'], 'rent_booking_tenant_room_fk')
                ->references(['id', 'tenant_id', 'room_id'])->on('booking')
                ->cascadeOnDelete();

            $table->foreign('tenant_id', 'rent_tenant_id_fk')
                ->references('id')->on('tenants')->restrictOnDelete();

            $table->foreign('room_id', 'rent_room_id_fk')
                ->references('id')->on('room')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent');
    }
};
