<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile_number')->nullable()->after('email');
            $table->string('designation')->nullable()->after('mobile_number');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->string('temp_password_plain')->nullable()->after('must_change_password');
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mobile_number', 'designation', 'must_change_password', 'temp_password_plain']);
        });
    }
};