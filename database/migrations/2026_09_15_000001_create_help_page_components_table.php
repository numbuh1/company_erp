<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_page_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_page_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('text');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::table('help_page_contents', function (Blueprint $table) {
            $table->foreignId('help_page_component_id')->nullable()->after('help_page_id')
                  ->constrained('help_page_components')->cascadeOnDelete();
        });

        // Migrate existing content rows into components
        $grouped = DB::table('help_page_contents')
            ->whereNull('help_page_component_id')
            ->get()
            ->groupBy('help_page_id');

        foreach ($grouped as $helpPageId => $contents) {
            $componentId = DB::table('help_page_components')->insertGetId([
                'help_page_id' => $helpPageId,
                'type'         => 'text',
                'sort_order'   => 0,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::table('help_page_contents')
                ->whereIn('id', $contents->pluck('id'))
                ->update(['help_page_component_id' => $componentId]);
        }

        // Swap unique constraint from (help_page_id, locale) to (help_page_component_id, locale)
        Schema::table('help_page_contents', function (Blueprint $table) {
            $table->dropUnique(['help_page_id', 'locale']);
            $table->unique(['help_page_component_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::table('help_page_contents', function (Blueprint $table) {
            $table->dropUnique(['help_page_component_id', 'locale']);
            $table->unique(['help_page_id', 'locale']);
            $table->dropConstrainedForeignId('help_page_component_id');
        });

        Schema::dropIfExists('help_page_components');
    }
};
