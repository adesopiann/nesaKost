<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_facility', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_dorm_id');
            $table->unsignedBigInteger('facility_id');

            $table->primary(['branch_dorm_id', 'facility_id'], 'branch_facility_primary');
            $table->index('facility_id', 'branch_facility_facility_id_index');

            $table->foreign('branch_dorm_id')->references('id')->on('branch_dorm')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facility')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_facility');
    }
};
