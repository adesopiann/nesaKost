<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('address')->nullable();
            $table->timestamps();

            $table->unique(['id', 'user_id'], 'owners_id_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
