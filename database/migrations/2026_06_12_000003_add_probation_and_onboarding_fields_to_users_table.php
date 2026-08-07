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
        Schema::table('users', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->after('email');
            $table->string('employment_status')->default('active')->after('is_active');
            $table->date('probation_start_date')->nullable()->after('employment_status');
            $table->date('probation_end_date')->nullable()->after('probation_start_date');
            $table->foreignId('recruitment_applicant_id')->nullable()->after('probation_end_date')
                ->constrained('recruitment_applicants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recruitment_applicant_id');
            $table->dropColumn(['contact_email', 'employment_status', 'probation_start_date', 'probation_end_date']);
        });
    }
};
