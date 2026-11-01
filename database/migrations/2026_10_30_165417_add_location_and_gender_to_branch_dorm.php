<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
    {
        Schema::table('branch_dorm', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('gender_type', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('branch_dorm', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'gender_type']);
        });
    }
};
