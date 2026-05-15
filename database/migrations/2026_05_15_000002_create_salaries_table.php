<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Base pay (moved from users table)
            $table->bigInteger('salary')->nullable();
            $table->string('salary_type', 20)->nullable(); // monthly|weekly|daily|hourly

            // Allowances (added to gross pay)
            $table->bigInteger('allowance_adjustment')->nullable(); // general +/- adjustment
            $table->bigInteger('allowance_bonus')->nullable();      // one-off or recurring bonus
            $table->bigInteger('allowance_excl_tax')->nullable();   // non-taxable allowance

            // Deductions (subtracted from gross pay)
            $table->bigInteger('parking_fee')->nullable();
            $table->bigInteger('insurance')->nullable();
            $table->bigInteger('personal_income_tax')->nullable();
            $table->bigInteger('other_deduction')->nullable();

            $table->timestamps();
        });

        // Migrate existing salary data from users table
        DB::statement("
            INSERT INTO salaries (user_id, salary, salary_type, created_at, updated_at)
            SELECT id, salary, salary_type, NOW(), NOW()
            FROM users
            WHERE salary IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
