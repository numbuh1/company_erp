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
        Schema::table('recruitment_applicants', function (Blueprint $table) {
            $table->tinyInteger('evaluation')->default(0)->after('status'); // 0–3 stars
            $table->string('email')->nullable()->after('evaluation');
            $table->string('phone')->nullable()->after('email');
            $table->string('profile_url')->nullable()->after('phone');
            $table->decimal('salary_expectation', 12, 2)->nullable()->after('profile_url');
            $table->date('available_date')->nullable()->after('salary_expectation');
            $table->foreignId('referer_user_id')->nullable()->constrained('users')->nullOnDelete()->after('available_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_applicants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referer_user_id');
            $table->dropColumn(['evaluation', 'email', 'phone', 'profile_url', 'salary_expectation', 'available_date']);
        });
    }
};
