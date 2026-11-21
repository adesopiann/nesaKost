<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Preflight sebelum perubahan schema agar masalah data lama tidak
        // meninggalkan kolom/tabel setengah jadi ketika migrasi dihentikan.
        $orphanedHousemaid = DB::table('housemaid as h')
            ->leftJoin('branch_dorm as b', 'b.id', '=', 'h.branch_dorm_id')
            ->whereNull('b.id')
            ->exists();

        if ($orphanedHousemaid) {
            throw new RuntimeException('Migrasi ART dihentikan: ada data ART yang tidak memiliki cabang penempatan lama. Perbaiki data tersebut terlebih dahulu.');
        }

        $duplicatePhone = DB::table('housemaid as h')
            ->join('branch_dorm as b', 'b.id', '=', 'h.branch_dorm_id')
            ->select('b.owner_id', 'h.phone_number')
            ->whereNotNull('h.phone_number')
            ->groupBy('b.owner_id', 'h.phone_number')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicatePhone) {
            throw new RuntimeException('Migrasi ART dihentikan: terdapat nomor telepon duplikat untuk Owner ID ' . $duplicatePhone->owner_id . '. Rapikan data terlebih dahulu.');
        }

        Schema::table('housemaid', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('owners')->cascadeOnDelete();
            $table->string('status', 20)->default('active')->after('address');
        });

        DB::table('housemaid')
            ->join('branch_dorm', 'branch_dorm.id', '=', 'housemaid.branch_dorm_id')
            ->update(['housemaid.owner_id' => DB::raw('branch_dorm.owner_id')]);

        Schema::create('housemaid_branch', function (Blueprint $table) {
            $table->foreignId('housemaid_id')->constrained('housemaid')->cascadeOnDelete();
            $table->foreignId('branch_dorm_id')->constrained('branch_dorm')->cascadeOnDelete();
            $table->primary(['housemaid_id', 'branch_dorm_id'], 'housemaid_branch_primary');
            $table->index('branch_dorm_id', 'housemaid_branch_branch_dorm_id_index');
        });

        DB::table('housemaid')->select(['id', 'branch_dorm_id'])->orderBy('id')->chunk(500, function ($rows) {
            $pivotRows = $rows->map(fn ($row) => [
                'housemaid_id' => $row->id,
                'branch_dorm_id' => $row->branch_dorm_id,
            ])->all();
            if ($pivotRows !== []) {
                DB::table('housemaid_branch')->insertOrIgnore($pivotRows);
            }
        });

        // Penempatan pada cabang nonaktif langsung dilepaskan sesuai kebijakan.
        $inactiveBranchIds = DB::table('branch_dorm')
            ->where('status', '!=', 'active')
            ->pluck('id');

        if ($inactiveBranchIds->isNotEmpty()) {
            DB::table('housemaid_branch')->whereIn('branch_dorm_id', $inactiveBranchIds)->delete();
        }

        // ART lama tanpa satu pun cabang aktif menjadi nonaktif.
        DB::table('housemaid')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('housemaid_branch as hb')
                    ->join('branch_dorm as bd', 'bd.id', '=', 'hb.branch_dorm_id')
                    ->whereColumn('hb.housemaid_id', 'housemaid.id')
                    ->where('bd.status', 'active');
            })
            ->update(['status' => 'inactive']);

        Schema::table('housemaid', function (Blueprint $table) {
            $table->unique(['owner_id', 'phone_number'], 'housemaid_owner_phone_unique');
            $table->dropForeign(['branch_dorm_id']);
            $table->dropIndex('housemaid_branch_dorm_id_index');
            $table->dropColumn(['branch_dorm_id', 'name']);
        });

        Schema::table('housemaid', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        $multipleAssignments = DB::table('housemaid_branch')
            ->select('housemaid_id')->groupBy('housemaid_id')->havingRaw('COUNT(*) > 1')->exists();

        if ($multipleAssignments) {
            throw new RuntimeException('Rollback dibatalkan: ada ART dengan beberapa penempatan; skema lama hanya mendukung satu cabang.');
        }

        Schema::table('housemaid', function (Blueprint $table) {
            $table->dropUnique('housemaid_owner_phone_unique');
            $table->unsignedBigInteger('branch_dorm_id')->nullable()->after('id');
            $table->string('name', 150)->nullable()->after('branch_dorm_id');
        });

        DB::statement("UPDATE housemaid h LEFT JOIN housemaid_branch hb ON hb.housemaid_id = h.id SET h.branch_dorm_id = hb.branch_dorm_id, h.name = TRIM(CONCAT(COALESCE(h.fname, ''), ' ', COALESCE(h.lname, ''))) ");

        Schema::table('housemaid', function (Blueprint $table) {
            $table->foreign('branch_dorm_id')->references('id')->on('branch_dorm')->cascadeOnDelete();
            $table->index('branch_dorm_id', 'housemaid_branch_dorm_id_index');
            $table->dropForeign(['owner_id']);
            $table->dropColumn(['owner_id', 'status']);
        });

        Schema::dropIfExists('housemaid_branch');
    }
};
