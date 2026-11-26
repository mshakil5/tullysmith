<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('users', function(Blueprint $t){$t->timestamp('fcm_registered_at')->nullable();}); }
 public function down(): void { Schema::table('users', function(Blueprint $t){$t->dropColumn('fcm_registered_at');}); }
};
