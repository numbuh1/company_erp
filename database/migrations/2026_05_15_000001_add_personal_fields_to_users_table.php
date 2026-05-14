<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number', 30)->nullable()->after('position');
            $table->string('citizen_id', 30)->nullable()->after('phone_number');
            $table->text('home_address')->nullable()->after('citizen_id');
            $table->string('tax_code', 20)->nullable()->after('home_address');
            $table->string('social_insurance_id', 20)->nullable()->after('tax_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'citizen_id', 'home_address', 'tax_code', 'social_insurance_id']);
        });
    }
};
