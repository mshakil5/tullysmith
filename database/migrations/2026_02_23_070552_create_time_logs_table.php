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
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('service_job_id')->constrained('service_jobs')->onDelete('cascade');
            $table->foreignId('job_assignment_id')->constrained('job_assignments')->onDelete('cascade');
            $table->dateTime('clock_in_at');
            $table->dateTime('clock_out_at')->nullable();
            $table->string('clock_in_photo')->nullable();
            $table->string('clock_out_photo')->nullable();
            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_lng', 10, 7)->nullable();
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->string('location_note')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
