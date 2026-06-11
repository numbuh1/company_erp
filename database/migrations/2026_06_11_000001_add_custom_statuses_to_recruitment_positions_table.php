<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recruitment_positions', function (Blueprint $table) {
            // Custom applicant statuses added via the Kanban board, scoped to this position only.
            $table->json('custom_applicant_statuses')->nullable()->after('status');
        });

        // Migrate existing applicant statuses to the new fixed status set.
        $map = [
            'CV Screening'           => 'Lọc CV',
            'Approved for Interview' => 'Duyệt phỏng vấn',
            'Approved'               => 'Cân nhắc offer',
            'Rejected'               => 'Không phù hợp',
            'Offered'                => 'Đã gửi offer',
            'Hired'                  => 'Đã tuyển',
        ];

        foreach ($map as $old => $new) {
            DB::table('recruitment_applicants')->where('status', $old)->update(['status' => $new]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_positions', function (Blueprint $table) {
            $table->dropColumn('custom_applicant_statuses');
        });
    }
};
