<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_thoughts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content')->nullable();
            $table->string('link_url')->nullable();
            
            // Local media storage
            $table->string('media_path')->nullable();
            $table->enum('media_type', ['image', 'video', 'none'])->default('none');
            $table->string('media_original_name')->nullable();
            $table->string('media_mime_type')->nullable();
            $table->unsignedBigInteger('media_size')->nullable();
            
            // Google Drive status (uploaded only upon TL explicit click)
            $table->string('drive_file_id')->nullable();
            $table->string('drive_url')->nullable();
            $table->timestamp('uploaded_to_drive_at')->nullable();
            $table->foreignId('uploaded_to_drive_by')->nullable()->constrained('users')->nullOnDelete();
            
            // 7-day expiration policy for unuploaded local media
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_expired')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_thoughts');
    }
};
