<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('status', 'tasks_status_idx');
            $table->index('deadline', 'tasks_deadline_idx');
            $table->index('updated_at', 'tasks_updated_at_idx');
            $table->index('created_at', 'tasks_created_at_idx');
        });

        Schema::table('team_thoughts', function (Blueprint $table) {
            $table->index('group_type', 'team_thoughts_group_type_idx');
            $table->index('tl_id', 'team_thoughts_tl_id_idx');
            $table->index('created_at', 'team_thoughts_created_at_idx');
            $table->index('updated_at', 'team_thoughts_updated_at_idx');
            $table->index('is_deleted', 'team_thoughts_is_deleted_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_idx');
            $table->index('created_by', 'users_created_by_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_status_idx');
            $table->dropIndex('tasks_deadline_idx');
            $table->dropIndex('tasks_updated_at_idx');
            $table->dropIndex('tasks_created_at_idx');
        });

        Schema::table('team_thoughts', function (Blueprint $table) {
            $table->dropIndex('team_thoughts_group_type_idx');
            $table->dropIndex('team_thoughts_tl_id_idx');
            $table->dropIndex('team_thoughts_created_at_idx');
            $table->dropIndex('team_thoughts_updated_at_idx');
            $table->dropIndex('team_thoughts_is_deleted_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_idx');
            $table->dropIndex('users_created_by_idx');
        });
    }
};
