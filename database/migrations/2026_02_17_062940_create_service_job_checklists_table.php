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
        Schema::create('service_job_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->nullable()->constrained('service_jobs')->onDelete('cascade');
            $table->foreignId('checklist_id')->nullable()->constrained('checklists')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('pending'); // pending|approved|rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_job_checklists');
    }
};
