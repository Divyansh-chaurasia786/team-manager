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
            $table->dateTime('overdue_reminder_sent_at')->nullable()->after('reassignment_count');
            $table->unsignedInteger('overdue_reminder_count')->default(0)->after('overdue_reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['overdue_reminder_sent_at', 'overdue_reminder_count']);
        });
    }
};
