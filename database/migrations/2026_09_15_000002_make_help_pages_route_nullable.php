<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_pages', function (Blueprint $table) {
            $table->string('route')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('help_pages', function (Blueprint $table) {
            $table->string('route')->nullable(false)->change();
        });
    }
};
