<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('drive_folders')) {
            Schema::create('drive_folders', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('parent_id')->nullable()->constrained('drive_folders')->cascadeOnDelete();
                $table->string('drive_folder_id')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        Schema::table('drive_files', function (Blueprint $table) {
            if (!Schema::hasColumn('drive_files', 'folder_id')) {
                $table->foreignId('folder_id')->nullable()->after('uploaded_by')->constrained('drive_folders')->nullOnDelete();
            }
            if (!Schema::hasColumn('drive_files', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_type');
            }
            if (!Schema::hasColumn('drive_files', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('file_size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('drive_files', function (Blueprint $table) {
            if (Schema::hasColumn('drive_files', 'folder_id')) {
                $table->dropConstrainedForeignId('folder_id');
            }
            if (Schema::hasColumn('drive_files', 'file_size')) {
                $table->dropColumn('file_size');
            }
            if (Schema::hasColumn('drive_files', 'mime_type')) {
                $table->dropColumn('mime_type');
            }
        });

        Schema::dropIfExists('drive_folders');
    }
};
