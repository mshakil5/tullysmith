<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('app_incidents', function(Blueprint $t) {
  $t->id(); $t->timestamp('created_at')->index(); $t->unsignedBigInteger('user_id')->nullable()->index();
  $t->string('source',16); $t->string('operation',120); $t->string('kind',40)->index();
  $t->unsignedSmallInteger('status')->nullable(); $t->unsignedInteger('duration_ms')->nullable();
  $t->string('reference',64)->nullable()->index(); $t->string('platform',20)->nullable(); $t->string('version',40)->nullable();
 }); }
 public function down(): void { Schema::dropIfExists('app_incidents'); }
};
