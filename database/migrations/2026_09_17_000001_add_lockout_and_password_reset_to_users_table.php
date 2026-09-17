<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('must_change_password');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('otp_expires_at')->nullable()->after('locked_until');
            $table->string('password_reset_otp')->nullable()->after('otp_expires_at');
            $table->timestamp('password_reset_otp_expires_at')->nullable()->after('password_reset_otp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'failed_login_attempts',
                'locked_until',
                'otp_expires_at',
                'password_reset_otp',
                'password_reset_otp_expires_at',
            ]);
        });
    }
};
