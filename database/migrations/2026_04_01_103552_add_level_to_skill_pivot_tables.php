<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_position_skill', function (Blueprint $table) {
            $table->string('level')->default('beginner')->after('skill_id');
        });
        Schema::table('recruitment_applicant_skill', function (Blueprint $table) {
            $table->string('level')->default('beginner')->after('skill_id');
        });
    }
    
    public function down(): void
    {
        Schema::table('recruitment_position_skill', function (Blueprint $table) {
            $table->dropColumn('level');
        });
        Schema::table('recruitment_applicant_skill', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
