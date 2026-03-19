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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('Not Started')->after('actual_end_date');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status')->default('Not Started')->after('actual_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
