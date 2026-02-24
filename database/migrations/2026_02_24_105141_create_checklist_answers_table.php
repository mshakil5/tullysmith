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
        Schema::create('checklist_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_checklist_id')->constrained()->onDelete('cascade');
            $table->foreignId('checklist_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('answered_by')->constrained('users')->onDelete('cascade');
            $table->text('answer')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_answers');
    }
};
