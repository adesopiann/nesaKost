<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 20)->unique()->comment('Diisi manual');
            $table->foreignId('branch_dorm_id')->constrained('branch_dorm')->cascadeOnDelete();
            $table->string('type', 50);
            $table->decimal('monthly_price', 12, 2);
            $table->string('image_path', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active')->comment('active, inactive, maintenance');
            $table->timestamps();

            $table->index('branch_dorm_id', 'room_branch_dorm_id_index');
            $table->index(['branch_dorm_id', 'status'], 'room_branch_status_index');
            $table->index('monthly_price', 'room_monthly_price_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room');
    }
};
