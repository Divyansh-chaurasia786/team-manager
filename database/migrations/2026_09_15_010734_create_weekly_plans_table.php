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
        Schema::create('weekly_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // The employee assigned to this weekly plan
            $table->string('title');
            $table->text('goals')->nullable(); // Main objectives & weekly goals
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['draft', 'shared', 'in-progress', 'completed'])->default('shared');
            $table->json('days_breakdown')->nullable(); // Monday - Friday specific tasks/focus areas
            $table->text('tl_notes')->nullable(); // Instructions or guidance from TL
            $table->text('employee_feedback')->nullable(); // Review notes from employee
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_plans');
    }
};
