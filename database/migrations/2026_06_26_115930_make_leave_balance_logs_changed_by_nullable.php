<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_balance_logs', function (Blueprint $table) {
            $table->dropForeign(['changed_by']);
        });
        Schema::table('leave_balance_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('changed_by')->nullable()->change();
        });
        Schema::table('leave_balance_logs', function (Blueprint $table) {
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_balance_logs', function (Blueprint $table) {
            $table->dropForeign(['changed_by']);
        });
        Schema::table('leave_balance_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('changed_by')->nullable(false)->change();
        });
        Schema::table('leave_balance_logs', function (Blueprint $table) {
            $table->foreign('changed_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
