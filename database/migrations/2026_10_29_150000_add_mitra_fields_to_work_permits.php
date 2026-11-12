<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('work_permits', function (Blueprint $table) {
            $table->uuid('group_id')->nullable()->index();
            $table->string('person_type')->nullable();
            $table->string('foreman_name')->nullable();
            $table->string('zone_key')->nullable();
            $table->string('zone_color', 7)->nullable();
            $table->string('photo_path')->nullable();
            $table->dateTime('work_starts_at')->nullable();
            $table->dateTime('work_ends_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('work_permits', function (Blueprint $table) {
            $table->dropIndex(['group_id']);
            $table->dropColumn(['group_id','person_type','foreman_name','zone_key','zone_color','photo_path','work_starts_at','work_ends_at']);
        });
    }
};
