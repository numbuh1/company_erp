<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_code', 50)->nullable()->unique()->after('name');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('task_code', 50)->nullable()->unique()->after('name');
        });

        foreach (DB::table('projects')->get(['id']) as $project) {
            DB::table('projects')->where('id', $project->id)->update(['project_code' => 'PJ-' . $project->id]);
        }

        foreach (DB::table('tasks')->get(['id']) as $task) {
            DB::table('tasks')->where('id', $task->id)->update(['task_code' => 'TK-' . $task->id]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('project_code');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('task_code');
        });
    }
};
