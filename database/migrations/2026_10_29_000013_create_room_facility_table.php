<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_facility', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('facility_id');

            $table->primary(['room_id', 'facility_id'], 'room_facility_primary');
            $table->index('facility_id', 'room_facility_facility_id_index');

            $table->foreign('room_id')->references('id')->on('room')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facility')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_facility');
    }
};
