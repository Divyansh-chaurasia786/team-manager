<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('drive_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('drive_file_id');
            $table->string('drive_url');
            $table->enum('file_type', ['photo', 'video', 'document']);
            $table->string('upload_date');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('drive_files'); }
};
