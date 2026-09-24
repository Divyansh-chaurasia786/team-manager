<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drive_files', function (Blueprint $table) {
            if (!Schema::hasColumn('drive_files', 'content_shoot_id')) {
                $table->foreignId('content_shoot_id')
                      ->nullable()
                      ->after('task_id')
                      ->constrained('content_shoots')
                      ->nullOnDelete();
            }
            if (!Schema::hasColumn('drive_files', 'account_handle')) {
                $table->string('account_handle', 150)
                      ->nullable()
                      ->after('content_shoot_id')
                      ->index();
            }
            if (!Schema::hasColumn('drive_files', 'platform')) {
                $table->string('platform', 50)
                      ->nullable()
                      ->after('account_handle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('drive_files', function (Blueprint $table) {
            if (Schema::hasColumn('drive_files', 'content_shoot_id')) {
                $table->dropConstrainedForeignId('content_shoot_id');
            }
            if (Schema::hasColumn('drive_files', 'account_handle')) {
                $table->dropColumn('account_handle');
            }
            if (Schema::hasColumn('drive_files', 'platform')) {
                $table->dropColumn('platform');
            }
        });
    }
};
