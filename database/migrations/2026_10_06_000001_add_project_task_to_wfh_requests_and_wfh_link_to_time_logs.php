<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wfh_requests', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
        });

        // Time logs created automatically when a WFH request is approved
        Schema::table('time_logs', function (Blueprint $table) {
            $table->foreignId('wfh_request_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wfh_request_id');
        });

        Schema::table('wfh_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('task_id');
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
