<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            // 'new' untuk booking penghuni baru, 'renewal' untuk perpanjangan.
            $table->string('booking_type', 20)
                ->default('new')
                ->after('room_id');

            // Diisi hanya jika booking merupakan renewal.
            $table->unsignedBigInteger('previous_rent_id')
                ->nullable()
                ->after('booking_type');

            $table->foreign('previous_rent_id', 'booking_previous_rent_fk')
                ->references('id')
                ->on('rent')
                ->restrictOnDelete();

            $table->index(
                ['previous_rent_id', 'status'],
                'booking_previous_rent_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            $table->dropForeign('booking_previous_rent_fk');
            $table->dropIndex('booking_previous_rent_status_index');
            $table->dropColumn(['previous_rent_id', 'booking_type']);
        });
    }
};
