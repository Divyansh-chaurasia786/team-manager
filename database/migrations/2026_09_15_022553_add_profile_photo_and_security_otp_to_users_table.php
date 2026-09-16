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
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable()->after('email');
            $table->string('security_otp')->nullable()->after('profile_photo_path');
            $table->timestamp('security_otp_expires_at')->nullable()->after('security_otp');
            $table->json('pending_profile_update')->nullable()->after('security_otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo_path',
                'security_otp',
                'security_otp_expires_at',
                'pending_profile_update',
            ]);
        });
    }
};
