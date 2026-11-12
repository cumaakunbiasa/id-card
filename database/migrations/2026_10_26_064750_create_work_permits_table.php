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
        Schema::create('work_permits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('full_name');
            $table->string('company_name');
            $table->string('job_title');
            $table->string('work_location');
            $table->text('work_description');
            $table->date('start_date');
            $table->date('end_date');

            $table->string('status')->default('ditinjau');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_permits');
    }
};
