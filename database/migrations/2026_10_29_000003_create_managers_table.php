<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->timestamps();

            $table->index('owner_id', 'managers_owner_id_index');
            $table->unique(['id', 'owner_id'], 'managers_id_owner_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managers');
    }
};
