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
        Schema::create('recruitment_applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_position_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('cv_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('CV Screening');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_applicants');
    }
};
