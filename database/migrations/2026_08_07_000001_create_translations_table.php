<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10);
            $table->string('key', 1000);
            $table->text('value');
            $table->timestamps();

            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
