<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_page_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_page_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->longText('content');
            $table->timestamps();
            $table->unique(['help_page_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_page_contents');
    }
};
