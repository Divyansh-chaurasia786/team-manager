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
        Schema::create('content_shoots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->enum('platform', ['instagram', 'youtube', 'both', 'other'])->default('both');
            $table->string('instagram_handle')->nullable(); // e.g. @ecofone_official
            $table->string('youtube_channel')->nullable(); // e.g. @ecofonetech
            $table->dateTime('shoot_date'); // scheduled date & call time
            $table->string('location')->nullable(); // studio room, outdoor set, etc.
            
            // Cast & Crew assignments
            $table->foreignId('camera_person_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('camera_person_name')->nullable(); // for external/custom crew
            $table->foreignId('model_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('model_name')->nullable(); // for external/creator model
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('editor_name')->nullable();
            $table->foreignId('director_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('director_name')->nullable();
            $table->string('other_crew')->nullable(); // e.g. lights, sound assistant

            // Creative & Script
            $table->text('hook')->nullable(); // opening 3-second hook
            $table->longText('script')->nullable(); // dialogue, scene breakdown, talking points
            $table->text('concept_notes')->nullable(); // lighting, props, visual style
            $table->text('reference_links')->nullable(); // audio link, reference reel, drive assets

            // Production Status Pipeline
            $table->enum('status', [
                'planning',     // ideation & brief
                'scripting',    // script writing
                'scheduled',    // shoot locked & call time set
                'shooting',     // in production on set
                'editing',      // post-production & rough cuts
                'review',       // TL review & revisions
                'published'     // live on Instagram/YouTube
            ])->default('planning');

            $table->dateTime('target_publish_date')->nullable();
            $table->string('published_url')->nullable(); // final live URL once published
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_shoots');
    }
};
