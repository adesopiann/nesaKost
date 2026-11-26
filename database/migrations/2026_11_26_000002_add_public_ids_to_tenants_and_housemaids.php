<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique();
        });

        Schema::table('housemaid', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique();
        });

        DB::table('tenants')->whereNull('public_id')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('tenants')->where('id', $row->id)->update(['public_id' => (string) Str::uuid()]);
            }
        });

        DB::table('housemaid')->whereNull('public_id')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('housemaid')->where('id', $row->id)->update(['public_id' => (string) Str::uuid()]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });

        Schema::table('housemaid', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
