<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('housemaid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_dorm_id')->constrained('branch_dorm')->cascadeOnDelete();
            $table->string('name', 150)->nullable();
            $table->string('fname', 100);
            $table->string('lname', 100)->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();

            $table->index('branch_dorm_id', 'housemaid_branch_dorm_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housemaid');
    }
};
