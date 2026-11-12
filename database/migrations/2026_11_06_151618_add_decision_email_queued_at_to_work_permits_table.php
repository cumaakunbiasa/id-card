<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('work_permits', 'decision_email_queued_at')) {
            Schema::table('work_permits', function (Blueprint $table) {
                $table->timestamp('decision_email_queued_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('work_permits', 'decision_email_queued_at')) {
            Schema::table('work_permits', function (Blueprint $table) {
                $table->dropColumn('decision_email_queued_at');
            });
        }
    }
};
