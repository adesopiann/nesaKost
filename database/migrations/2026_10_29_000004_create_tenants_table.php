<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('profession', 100)->nullable();
            $table->string('emergency_name', 150)->nullable();
            $table->string('emergency_number', 20)->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id'], 'tenants_id_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
