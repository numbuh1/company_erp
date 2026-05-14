<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete()->after('user_id');
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete()->after('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['task_id']);
            $table->dropColumn(['project_id', 'task_id']);
        });
    }
};
