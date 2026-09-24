<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drive_files', function (Blueprint $table) {
            if (!Schema::hasColumn('drive_files', 'task_id')) {
                $table->foreignId('task_id')
                      ->nullable()
                      ->after('folder_id')
                      ->constrained('tasks')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('drive_files', function (Blueprint $table) {
            if (Schema::hasColumn('drive_files', 'task_id')) {
                $table->dropConstrainedForeignId('task_id');
            }
        });
    }
};
