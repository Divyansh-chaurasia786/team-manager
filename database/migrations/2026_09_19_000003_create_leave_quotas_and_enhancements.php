<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Leave Quotas per User per Year
        Schema::create('leave_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('year')->default(2026);
            $table->integer('casual_quota')->default(12);
            $table->integer('sick_quota')->default(8);
            $table->integer('emergency_quota')->default(5);
            $table->integer('privilege_quota')->default(15);
            $table->timestamps();

            $table->unique(['user_id', 'year']);
        });

        // 2. Leave Settings (TL on/off toggle for team)
        Schema::create('leave_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tl_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_leave_enabled')->default(true);
            $table->foreignId('toggled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Add columns to leave_applications
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->boolean('is_ceo_granted')->default(false)->after('status');
            $table->string('approved_leave_type')->nullable()->after('leave_type');
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn(['is_ceo_granted', 'approved_leave_type']);
        });
        Schema::dropIfExists('leave_settings');
        Schema::dropIfExists('leave_quotas');
    }
};
