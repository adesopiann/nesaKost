<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branch_dorm', function (Blueprint $table) { $table->string('slug', 180)->nullable()->after('name'); });
        DB::table('branch_dorm')->orderBy('id')->each(function ($branch) {
            DB::table('branch_dorm')->where('id', $branch->id)->update(['slug' => (Str::slug($branch->name) ?: 'kost') . '-' . $branch->id]);
        });
        Schema::table('branch_dorm', function (Blueprint $table) { $table->unique('slug'); });
    }
    public function down(): void
    {
        Schema::table('branch_dorm', function (Blueprint $table) { $table->dropUnique(['slug']); $table->dropColumn('slug'); });
    }
};
