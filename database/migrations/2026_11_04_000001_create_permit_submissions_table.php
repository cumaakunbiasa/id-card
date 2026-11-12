<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('permit_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('token');
            $table->uuid('group_id');
            $table->timestamp('created_at');
            $table->unique(['user_id', 'token']);
        });
    }
    public function down(): void { Schema::dropIfExists('permit_submissions'); }
};
