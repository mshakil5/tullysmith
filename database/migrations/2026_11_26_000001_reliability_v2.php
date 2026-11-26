<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('time_logs',function(Blueprint $t){$t->timestamp('exception_reviewed_at')->nullable();});
  Schema::create('attendance_exception_audits',function(Blueprint $t){$t->id();$t->unsignedBigInteger('time_log_id')->index();$t->unsignedBigInteger('reviewer_id');$t->text('reason');$t->json('before');$t->json('after');$t->timestamp('created_at');});
  Schema::table('app_incidents',function(Blueprint $t){$t->unsignedInteger('distance_metres')->nullable();$t->unsignedInteger('accuracy_metres')->nullable();});
 }
 public function down(): void {Schema::table('app_incidents',fn(Blueprint $t)=>$t->dropColumn(['distance_metres','accuracy_metres']));Schema::dropIfExists('attendance_exception_audits');Schema::table('time_logs',fn(Blueprint $t)=>$t->dropColumn('exception_reviewed_at'));}
};
