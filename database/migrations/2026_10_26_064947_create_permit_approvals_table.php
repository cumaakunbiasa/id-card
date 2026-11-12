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
        Schema::create('permit_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('work_permit_id')
                ->constrained('work_permits')
                ->cascadeOnDelete();

            $table->foreignId('admin_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('admin_role');
            $table->string('status')->default('menunggu');
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            $table->unique(['work_permit_id', 'admin_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_approvals');
    }
};
