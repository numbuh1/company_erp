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
        Schema::table('recruitment_positions', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete()->after('name');
            $table->decimal('salary_min', 12, 2)->nullable()->after('file_path');
            $table->decimal('salary_max', 12, 2)->nullable()->after('salary_min');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn(['salary_min', 'salary_max']);
        });
    }
};
