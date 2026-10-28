<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code', 30)->nullable()->unique()
                ->comment('Dibuat Laravel setelah ID terbentuk, format PAY-{id}');
            $table->foreignId('booking_id')->constrained('booking')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30)->nullable();
            $table->string('status', 20)->default('pending')
                ->comment('pending, paid, failed, expired');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status'], 'payment_booking_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
