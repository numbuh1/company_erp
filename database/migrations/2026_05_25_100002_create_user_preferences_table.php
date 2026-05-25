<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Remove the old column from users if it exists (from the superseded migration)
        if (Schema::hasColumn('users', 'column_preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('column_preferences');
            });
        }

        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('task_list_column_preferences')->nullable();
            $table->json('project_task_column_preferences')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');

        if (!Schema::hasColumn('users', 'column_preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('column_preferences')->nullable()->after('wfh_without_approval');
            });
        }
    }
};
