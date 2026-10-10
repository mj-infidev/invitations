<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: a customer or template with invitations can't be removed by accident.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('template_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique(); // priya-karthik-x7k2
            $table->enum('status', ['draft', 'published', 'expired', 'disabled'])->default('draft')->index();
            $table->enum('language', ['ta', 'en', 'both'])->default('both'); // what guests see
            // Nullable because a fresh draft has no names or date yet. The server fills
            // both from `data` on save (title from the English names, event_date from
            // muhurtham.date).
            $table->string('title')->nullable(); // "Priya weds Karthik", for the WhatsApp preview
            $table->date('event_date')->nullable()->index(); // sorting and expiry
            // Every template field: names, parents, events, venue, message. Format: PLAN.md > Template form fields.
            $table->json('data');
            $table->string('og_image_path')->nullable(); // made when published
            $table->string('preview_token', 64)->unique(); // lets the owner open the draft
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            // Soft delete: the cascade on invitation_media below never fires when a
            // customer deletes an invitation. Its media rows and files must be removed
            // in code (Step 4).
            $table->softDeletes();
        });

        Schema::create('invitation_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['photo', 'music']);
            $table->string('path');
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_media');
        Schema::dropIfExists('invitations');
    }
};
