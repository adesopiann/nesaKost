<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->string('provider', 40)->nullable()->after('payment_method');
            $table->string('provider_reference', 100)->nullable()->after('provider');
            $table->string('channel_code', 40)->nullable()->after('provider_reference');
            $table->text('payment_instruction')->nullable()->after('channel_code');
            $table->string('qr_code_url', 500)->nullable()->after('payment_instruction');
            $table->string('checkout_url', 500)->nullable()->after('qr_code_url');
            $table->string('payment_reference', 100)->nullable()->after('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->dropColumn([
                'provider', 'provider_reference', 'channel_code',
                'payment_instruction', 'qr_code_url', 'checkout_url',
            ]);
        });
    }
};
