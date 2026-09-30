<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_shoots', function (Blueprint $table) {
            if (!Schema::hasColumn('content_shoots', 'drive_file_id')) {
                $table->string('drive_file_id')->nullable()->after('published_url');
            }
            if (!Schema::hasColumn('content_shoots', 'drive_url')) {
                $table->string('drive_url')->nullable()->after('drive_file_id');
            }
            if (!Schema::hasColumn('content_shoots', 'published_folder_id')) {
                $table->foreignId('published_folder_id')
                      ->nullable()
                      ->after('drive_url')
                      ->constrained('drive_folders')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_shoots', function (Blueprint $table) {
            if (Schema::hasColumn('content_shoots', 'published_folder_id')) {
                $table->dropConstrainedForeignId('published_folder_id');
            }
            if (Schema::hasColumn('content_shoots', 'drive_file_id')) {
                $table->dropColumn('drive_file_id');
            }
            if (Schema::hasColumn('content_shoots', 'drive_url')) {
                $table->dropColumn('drive_url');
            }
        });
    }
};
