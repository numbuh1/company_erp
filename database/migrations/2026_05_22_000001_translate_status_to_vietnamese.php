<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $map = [
        'Not Started' => 'Chưa bắt đầu',
        'In Progress' => 'Đang tiến hành',
        'Done'        => 'Đã xong',
    ];

    public function up(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table('projects')->where('status', $old)->update(['status' => $new]);
            DB::table('tasks')->where('status', $old)->update(['status' => $new]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('Chưa bắt đầu')->change();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status')->default('Chưa bắt đầu')->change();
        });
    }

    public function down(): void
    {
        $reverse = array_flip($this->map);

        foreach ($reverse as $new => $old) {
            DB::table('projects')->where('status', $new)->update(['status' => $old]);
            DB::table('tasks')->where('status', $new)->update(['status' => $old]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('Not Started')->change();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status')->default('Not Started')->change();
        });
    }
};
