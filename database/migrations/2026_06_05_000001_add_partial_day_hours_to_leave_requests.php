<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Hours absent on the first partial day of a multi-day leave.
            // Null for single-day leaves or legacy records (fall back to computed value).
            $table->float('start_day_hours')->nullable()->after('hours');

            // Hours absent on the last partial day of a multi-day leave.
            $table->float('end_day_hours')->nullable()->after('start_day_hours');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['start_day_hours', 'end_day_hours']);
        });
    }
};
