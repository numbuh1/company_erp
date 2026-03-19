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
        Schema::table('project_files', function (Blueprint $table) {
            $table->boolean('is_folder')->default(false)->after('project_id');
            $table->unsignedBigInteger('parent_id')->nullable()->after('is_folder');
            $table->string('name')->nullable()->after('parent_id'); // display name for both files and folders
            $table->string('stored_name')->nullable()->change();    // folders have no stored file
            $table->unsignedBigInteger('uploaded_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropColumn(['is_folder', 'parent_id', 'name']);
        });
    }
};
