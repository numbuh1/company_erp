<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** WFH permission => the leave permission whose holders get it on first install. */
    private const MIRROR = [
        'module wfh'                    => 'module leaves',
        'view own wfh'                  => 'view own leaves',
        'edit own wfh'                  => 'edit own leaves',
        'delete own wfh'                => 'delete own leaves',
        'view team wfh'                 => 'view team leaves',
        'edit team wfh'                 => 'edit team leaves',
        'delete team wfh'               => 'delete team leaves',
        'approve team wfh'              => 'approve team leaves',
        'view all wfh'                  => 'view all leaves',
        'edit all wfh'                  => 'edit all leaves',
        'delete all wfh'                => 'delete all leaves',
        'approve all wfh'               => 'approve all leaves',
        'receive all wfh notifications' => 'receive all leave notifications',
    ];

    public function up(): void
    {
        Schema::create('wfh_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->double('hours');
            $table->text('description')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reject_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'start_at']);
        });

        // Give WFH the same audience as leave requests, so approvers work out of the box.
        // The PermissionSeeder keeps these permissions (and their labels) in sync afterwards.
        $parent = Permission::firstOrCreate(['name' => 'wfh', 'guard_name' => 'web'], ['display_name' => 'WFH Requests']);
        foreach (self::MIRROR as $wfh => $leave) {
            $permission = Permission::firstOrCreate(['name' => $wfh, 'guard_name' => 'web'], ['parent_id' => $parent->id]);
            $source     = Permission::where('name', $leave)->where('guard_name', 'web')->first();
            if (!$source) continue;

            foreach (['role_has_permissions' => 'role_id', 'model_has_permissions' => 'model_id'] as $pivot => $key) {
                $rows = DB::table($pivot)->where('permission_id', $source->id)->get();
                foreach ($rows as $row) {
                    $new = (array) $row;
                    $new['permission_id'] = $permission->id;
                    DB::table($pivot)->insertOrIgnore($new);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('wfh_requests');
        Permission::whereIn('name', array_merge(array_keys(self::MIRROR), ['wfh']))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
