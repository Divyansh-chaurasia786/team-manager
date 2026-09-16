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
        Schema::table('content_shoots', function (Blueprint $table) {
            $table->foreignId('managing_member_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_shoots', function (Blueprint $table) {
            $table->dropForeign(['managing_member_id']);
            $table->dropColumn('managing_member_id');
        });
    }
};
