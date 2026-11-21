<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facility', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable()->after('id');
            $table->dropUnique('facility_facility_name_unique');
        });

        // Tentukan pemilik berdasarkan cabang yang menggunakan fasilitas.
        $facilityIds = DB::table('facility')->pluck('id');
        foreach ($facilityIds as $facilityId) {
            $ownerIds = DB::table('branch_facility as bf')
                ->join('branch_dorm as bd', 'bd.id', '=', 'bf.branch_dorm_id')
                ->where('bf.facility_id', $facilityId)
                ->pluck('bd.owner_id')
                ->merge(DB::table('room_facility as rf')
                    ->join('room as r', 'r.id', '=', 'rf.room_id')
                    ->join('branch_dorm as bd', 'bd.id', '=', 'r.branch_dorm_id')
                    ->where('rf.facility_id', $facilityId)
                    ->pluck('bd.owner_id'))
                ->filter()->unique()->values();

            if ($ownerIds->isEmpty()) {
                $owners = DB::table('owners')->orderBy('id')->pluck('id');
                if ($owners->count() !== 1) {
                    throw new RuntimeException("Fasilitas ID {$facilityId} belum terhubung ke cabang/kamar dan pemiliknya tidak dapat ditentukan. Tetapkan owner_id secara manual sebelum menjalankan migrasi.");
                }
                $ownerIds = collect([$owners->first()]);
            }

            $original = DB::table('facility')->where('id', $facilityId)->first();
            foreach ($ownerIds as $index => $ownerId) {
                if ($index === 0) {
                    DB::table('facility')->where('id', $facilityId)->update(['owner_id' => $ownerId]);
                    continue;
                }

                $newId = DB::table('facility')->insertGetId([
                    'owner_id' => $ownerId,
                    'facility_name' => $original->facility_name,
                    'description' => $original->description,
                    'created_at' => $original->created_at,
                    'updated_at' => $original->updated_at,
                ]);

                $branchIds = DB::table('branch_facility as bf')
                    ->join('branch_dorm as bd', 'bd.id', '=', 'bf.branch_dorm_id')
                    ->where('bf.facility_id', $facilityId)->where('bd.owner_id', $ownerId)
                    ->pluck('bf.branch_dorm_id');
                foreach ($branchIds as $branchId) {
                    DB::table('branch_facility')->where('branch_dorm_id', $branchId)->where('facility_id', $facilityId)->update(['facility_id' => $newId]);
                }

                $roomIds = DB::table('room_facility as rf')
                    ->join('room as r', 'r.id', '=', 'rf.room_id')
                    ->join('branch_dorm as bd', 'bd.id', '=', 'r.branch_dorm_id')
                    ->where('rf.facility_id', $facilityId)->where('bd.owner_id', $ownerId)
                    ->pluck('rf.room_id');
                foreach ($roomIds as $roomId) {
                    DB::table('room_facility')->where('room_id', $roomId)->where('facility_id', $facilityId)->update(['facility_id' => $newId]);
                }
            }
        }

        Schema::table('facility', function (Blueprint $table) {
            $table->foreign('owner_id')->references('id')->on('owners')->cascadeOnDelete();
            $table->unique(['owner_id', 'facility_name'], 'facility_owner_name_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('facility')->select('facility_name')->groupBy('facility_name')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Rollback tidak dapat dilakukan otomatis karena terdapat nama fasilitas yang sama pada pemilik berbeda.');
        }
        Schema::table('facility', function (Blueprint $table) {
            $table->dropUnique('facility_owner_name_unique');
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
            $table->unique('facility_name');
        });
    }
};
