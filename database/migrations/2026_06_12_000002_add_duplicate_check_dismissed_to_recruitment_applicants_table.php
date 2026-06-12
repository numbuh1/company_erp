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
            // Set to true once the user has been shown the "duplicate
            // applicant" pop-up (matching email/phone with another
            // applicant) and chose to keep this record's data as-is.
            // Reset to false whenever email/phone change so the check
            // runs again for the new values.
            $table->boolean('duplicate_check_dismissed')->default(false)->after('hr_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_applicants', function (Blueprint $table) {
            $table->dropColumn('duplicate_check_dismissed');
        });
    }
};
