<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /*
         * Migration ini mungkin dijalankan pada database yang kolom
         * facility_type-nya sudah terlanjur dibuat sebelumnya.
         */
        if (!Schema::hasColumn('facility', 'facility_type')) {
            Schema::table('facility', function (Blueprint $table) {
                $table->string('facility_type', 20)
                    ->default('branch')
                    ->after('facility_name');
            });
        }

        /*
         * Pastikan FK facility.owner_id tetap memiliki index pendukung
         * sebelum unique index lama dihapus.
         */
        $ownerIndexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'facility')
            ->where('index_name', 'facility_owner_id_index')
            ->exists();

        if (!$ownerIndexExists) {
            Schema::table('facility', function (Blueprint $table) {
                $table->index('owner_id', 'facility_owner_id_index');
            });
        }

        /*
         * Unique index lama harus dilepas sebelum membuat fasilitas
         * dengan nama sama tetapi jenis berbeda.
         */
        $oldIndexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'facility')
            ->where('index_name', 'facility_owner_name_unique')
            ->exists();

        if ($oldIndexExists) {
            Schema::table('facility', function (Blueprint $table) {
                $table->dropUnique('facility_owner_name_unique');
            });
        }

        /*
         * Pisahkan fasilitas lama yang sebelumnya dapat digunakan
         * sekaligus oleh cabang dan kamar.
         *
         * Setiap fasilitas diproses dalam transaksi agar insert fasilitas
         * room dan pemindahan pivot tidak terpisah jika terjadi kegagalan.
         */
        $facilityIds = DB::table('facility')->orderBy('id')->pluck('id');

        foreach ($facilityIds as $facilityId) {
            DB::transaction(function () use ($facilityId) {
                $facility = DB::table('facility')
                    ->where('id', $facilityId)
                    ->lockForUpdate()
                    ->first();

                if (!$facility) {
                    return;
                }

                $hasBranch = DB::table('branch_facility')
                    ->where('facility_id', $facility->id)
                    ->exists();

                $roomIds = DB::table('room_facility')
                    ->where('facility_id', $facility->id)
                    ->pluck('room_id');

                $hasRoom = $roomIds->isNotEmpty();

                if ($hasBranch && $hasRoom) {
                    /*
                     * Jika proses sebelumnya sudah sempat membuat record room,
                     * gunakan record tersebut agar tidak membuat duplikat.
                     */
                    $roomFacility = DB::table('facility')
                        ->where('owner_id', $facility->owner_id)
                        ->where('facility_name', $facility->facility_name)
                        ->where('facility_type', 'room')
                        ->where('id', '<>', $facility->id)
                        ->first();

                    if (!$roomFacility) {
                        $roomFacilityId = DB::table('facility')->insertGetId([
                            'owner_id' => $facility->owner_id,
                            'facility_name' => $facility->facility_name,
                            'facility_type' => 'room',
                            'description' => $facility->description,
                            'created_at' => $facility->created_at,
                            'updated_at' => $facility->updated_at,
                        ]);
                    } else {
                        $roomFacilityId = $roomFacility->id;
                    }

                    DB::table('room_facility')
                        ->where('facility_id', $facility->id)
                        ->whereIn('room_id', $roomIds)
                        ->update(['facility_id' => $roomFacilityId]);

                    DB::table('facility')
                        ->where('id', $facility->id)
                        ->update(['facility_type' => 'branch']);
                } elseif ($hasRoom) {
                    DB::table('facility')
                        ->where('id', $facility->id)
                        ->update(['facility_type' => 'room']);
                } else {
                    DB::table('facility')
                        ->where('id', $facility->id)
                        ->update(['facility_type' => 'branch']);
                }
            });
        }

        /*
         * Buat unique index baru hanya jika belum tersedia.
         */
        $newIndexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'facility')
            ->where('index_name', 'facility_owner_name_type_unique')
            ->exists();

        if (!$newIndexExists) {
            Schema::table('facility', function (Blueprint $table) {
                $table->unique(
                    ['owner_id', 'facility_name', 'facility_type'],
                    'facility_owner_name_type_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (
            DB::table('facility')
                ->select('owner_id', 'facility_name')
                ->groupBy('owner_id', 'facility_name')
                ->havingRaw('COUNT(*) > 1')
                ->exists()
        ) {
            throw new RuntimeException(
                'Rollback tidak dapat dilakukan otomatis karena fasilitas dengan nama sama sudah dipisahkan berdasarkan jenis. Amankan dan gabungkan relasi secara manual terlebih dahulu.'
            );
        }

        $newIndexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'facility')
            ->where('index_name', 'facility_owner_name_type_unique')
            ->exists();

        if ($newIndexExists) {
            Schema::table('facility', function (Blueprint $table) {
                $table->dropUnique('facility_owner_name_type_unique');
            });
        }

        if (Schema::hasColumn('facility', 'facility_type')) {
            Schema::table('facility', function (Blueprint $table) {
                $table->dropColumn('facility_type');
            });
        }

        $oldIndexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'facility')
            ->where('index_name', 'facility_owner_name_unique')
            ->exists();

        if (!$oldIndexExists) {
            Schema::table('facility', function (Blueprint $table) {
                $table->unique(
                    ['owner_id', 'facility_name'],
                    'facility_owner_name_unique'
                );
            });
        }

        // facility_owner_id_index sengaja tidak dihapus karena dapat
        // menjadi index pendukung foreign key facility.owner_id.
    }
};
