<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_dorm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('address');
            $table->string('image_path', 255)->nullable();
            $table->string('status', 20)->default('active')->comment('active, inactive');
            $table->timestamps();

            $table->index('owner_id', 'branch_dorm_owner_id_index');
            $table->unique(['id', 'owner_id'], 'branch_dorm_id_owner_id_unique');
            $table->index('status', 'branch_dorm_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_dorm');
    }
};
