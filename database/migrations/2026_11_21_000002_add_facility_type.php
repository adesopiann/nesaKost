<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facility', function (Blueprint $table) {
            $table->string('facility_type', 20)->default('branch')->after('facility_name');
            $table->dropUnique('facility_owner_name_unique');
        });

        // Pisahkan fasilitas lama yang sebelumnya dapat dipakai sekaligus oleh cabang dan kamar.
        $facilities = DB::table('facility')->orderBy('id')->get();
        foreach ($facilities as $facility) {
            $hasBranch = DB::table('branch_facility')->where('facility_id', $facility->id)->exists();
            $roomIds = DB::table('room_facility')->where('facility_id', $facility->id)->pluck('room_id');
            $hasRoom = $roomIds->isNotEmpty();

            if ($hasBranch && $hasRoom) {
                $roomFacilityId = DB::table('facility')->insertGetId([
                    'owner_id' => $facility->owner_id,
                    'facility_name' => $facility->facility_name,
                    'facility_type' => 'room',
                    'description' => $facility->description,
                    'created_at' => $facility->created_at,
                    'updated_at' => $facility->updated_at,
                ]);
                foreach ($roomIds as $roomId) {
                    DB::table('room_facility')->where('room_id', $roomId)->where('facility_id', $facility->id)
                        ->update(['facility_id' => $roomFacilityId]);
                }
                DB::table('facility')->where('id', $facility->id)->update(['facility_type' => 'branch']);
            } elseif ($hasRoom) {
                DB::table('facility')->where('id', $facility->id)->update(['facility_type' => 'room']);
            } else {
                DB::table('facility')->where('id', $facility->id)->update(['facility_type' => 'branch']);
            }
        }

        Schema::table('facility', function (Blueprint $table) {
            $table->unique(['owner_id', 'facility_name', 'facility_type'], 'facility_owner_name_type_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('facility')->select('owner_id', 'facility_name')->groupBy('owner_id', 'facility_name')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Rollback tidak dapat dilakukan otomatis karena fasilitas dengan nama sama sudah dipisahkan berdasarkan jenis. Amankan dan gabungkan relasi secara manual terlebih dahulu.');
        }

        Schema::table('facility', function (Blueprint $table) {
            $table->dropUnique('facility_owner_name_type_unique');
            $table->dropColumn('facility_type');
            $table->unique(['owner_id', 'facility_name'], 'facility_owner_name_unique');
        });
    }
};
