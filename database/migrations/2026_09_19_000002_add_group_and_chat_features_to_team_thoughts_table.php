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
        Schema::table('team_thoughts', function (Blueprint $table) {
            $table->string('group_type')->default('team')->after('user_id'); // 'team' or 'company'
            $table->foreignId('tl_id')->nullable()->after('group_type')->constrained('users')->nullOnDelete();
            $table->boolean('is_deleted')->default(false)->after('is_expired');
            $table->foreignId('deleted_by')->nullable()->after('is_deleted')->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->nullable()->after('deleted_by');
            $table->json('seen_by')->nullable()->after('deleted_at');
            $table->json('reactions')->nullable()->after('seen_by');
        });

        Schema::create('chat_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tl_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // 'admin' or 'member'
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tl_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_group_members');

        Schema::table('team_thoughts', function (Blueprint $table) {
            $table->dropForeign(['tl_id']);
            $table->dropForeign(['deleted_by']);
            $table->dropColumn([
                'group_type',
                'tl_id',
                'is_deleted',
                'deleted_by',
                'deleted_at',
                'seen_by',
                'reactions',
            ]);
        });
    }
};
