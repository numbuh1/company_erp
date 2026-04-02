<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_position_skill', function (Blueprint $table) {
            $table->foreignId('recruitment_position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['recruitment_position_id', 'skill_id']);
        });

        Schema::create('recruitment_position_tag', function (Blueprint $table) {
            $table->foreignId('recruitment_position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recruitment_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['recruitment_position_id', 'recruitment_tag_id']);
        });

        Schema::create('recruitment_applicant_skill', function (Blueprint $table) {
            $table->foreignId('recruitment_applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['recruitment_applicant_id', 'skill_id']);
        });

        Schema::create('recruitment_applicant_tag', function (Blueprint $table) {
            $table->foreignId('recruitment_applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recruitment_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['recruitment_applicant_id', 'recruitment_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_applicant_tag');
        Schema::dropIfExists('recruitment_applicant_skill');
        Schema::dropIfExists('recruitment_position_tag');
        Schema::dropIfExists('recruitment_position_skill');
    }
};
