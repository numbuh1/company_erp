<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_pages', function (Blueprint $table) {
            $table->unsignedInteger('order_no')->default(0)->after('title')->index();
        });
    }

    public function down(): void
    {
        Schema::table('help_pages', function (Blueprint $table) {
            $table->dropIndex(['order_no']);
            $table->dropColumn('order_no');
        });
    }
};
