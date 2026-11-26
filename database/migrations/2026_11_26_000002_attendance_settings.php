<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('attendance_settings', function(Blueprint $t){$t->id();$t->json('settings');$t->timestamps();});
  Schema::create('attendance_reminder_events', function(Blueprint $t){$t->id();$t->unsignedBigInteger('assignment_id');$t->string('kind',32);$t->timestamp('created_at');$t->unique(['assignment_id','kind']);});
 }
 public function down(): void {Schema::dropIfExists('attendance_reminder_events');Schema::dropIfExists('attendance_settings');}
};
