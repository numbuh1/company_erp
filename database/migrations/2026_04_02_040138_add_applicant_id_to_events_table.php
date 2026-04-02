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
        Schema::create('event_recruitment_applicant', function (Blueprint $table) {
            $table->foreignId('event_id')
                  ->constrained('events')
                  ->cascadeOnDelete();
            $table->foreignId('recruitment_applicant_id')
                  ->constrained('recruitment_applicants')
                  ->cascadeOnDelete();
            $table->primary(['event_id', 'recruitment_applicant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            Schema::dropIfExists('event_recruitment_applicant');
        });
    }
};
