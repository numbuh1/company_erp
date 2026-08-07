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
            // Ordered list of applicant status names (fixed + custom) for the
            // Kanban board / status dropdown. Null = use default order.
            $table->json('applicant_status_order')->nullable()->after('custom_applicant_statuses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_positions', function (Blueprint $table) {
            $table->dropColumn('applicant_status_order');
        });
    }
};
