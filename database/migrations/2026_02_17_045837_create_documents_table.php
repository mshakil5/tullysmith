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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->nullable()->constrained('service_jobs')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->string('type')->default('document'); // document, photo, invoice, receipt, drawing
            $table->string('title')->nullable();

            $table->string('file')->nullable(); // stored filename
            $table->decimal('amount', 10, 2)->nullable(); // only invoice/receipt

            $table->string('status')->default('approved'); // pending|approved|rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
