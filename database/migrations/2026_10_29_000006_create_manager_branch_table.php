<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manager_branch', function (Blueprint $table) {
            $table->unsignedBigInteger('manager_id');
            $table->unsignedBigInteger('branch_dorm_id');
            $table->unsignedBigInteger('owner_id');

            $table->primary(['manager_id', 'branch_dorm_id'], 'manager_branch_primary');
            $table->index(['manager_id', 'owner_id'], 'manager_branch_manager_owner_index');
            $table->index(['branch_dorm_id', 'owner_id'], 'manager_branch_branch_owner_index');

            $table->foreign(['manager_id', 'owner_id'], 'manager_branch_manager_owner_fk')
                ->references(['id', 'owner_id'])->on('managers')->cascadeOnDelete();

            $table->foreign(['branch_dorm_id', 'owner_id'], 'manager_branch_branch_owner_fk')
                ->references(['id', 'owner_id'])->on('branch_dorm')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_branch');
    }
};
