<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Aman dijalankan baik setelah migrasi refactor baru maupun jika Batch 1
        // sudah pernah diterapkan sebelum aturan cabang nonaktif ditambahkan.
        DB::table('housemaid_branch')
            ->whereIn('branch_dorm_id', DB::table('branch_dorm')->select('id')->where('status', '!=', 'active'))
            ->delete();

        DB::table('housemaid')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('housemaid_branch as hb')
                    ->join('branch_dorm as bd', 'bd.id', '=', 'hb.branch_dorm_id')
                    ->whereColumn('hb.housemaid_id', 'housemaid.id')
                    ->where('bd.status', 'active');
            })
            ->where('status', 'active')
            ->update(['status' => 'inactive', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Penempatan cabang lama tidak dapat dipulihkan secara aman.
    }
};
