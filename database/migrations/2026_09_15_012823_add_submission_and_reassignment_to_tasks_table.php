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
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('submission_remarks')->nullable()->after('submitted_at');
            $table->string('submission_link')->nullable()->after('submission_remarks');
            $table->string('submission_file')->nullable()->after('submission_link');
            $table->string('submission_file_type')->nullable()->after('submission_file'); // image, video, pdf, document
            $table->dateTime('previous_deadline')->nullable()->after('deadline');
            $table->text('revision_notes')->nullable()->after('previous_deadline');
            $table->integer('reassignment_count')->default(0)->after('revision_notes');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'submission_remarks',
                'submission_link',
                'submission_file',
                'submission_file_type',
                'previous_deadline',
                'revision_notes',
                'reassignment_count',
            ]);
        });
    }
};
