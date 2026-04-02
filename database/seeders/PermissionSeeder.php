<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Parent (module)
        $team_permission = Permission::firstOrCreate(
            ['name' => 'teams'],
            ['display_name' => 'Teams', 'parent_id' => null]
        );

        $role_permission = Permission::updateOrCreate(
            ['name' => 'roles'],
            ['display_name' => 'Roles', 'parent_id' => null]
        );

        $leave_permission = Permission::updateOrCreate(
            ['name' => 'leaves'],
            ['display_name' => 'Leave Requests', 'parent_id' => null]
        );

        $ot_permission = Permission::updateOrCreate(
            ['name' => 'ot'],
            ['display_name' => 'OT Requests', 'parent_id' => null]
        );

        $announcement_permission = Permission::updateOrCreate(
            ['name' => 'announcement'],
            ['display_name' => 'Announcements & Policies', 'parent_id' => null]
        );

        $user_permission = Permission::updateOrCreate(
            ['name' => 'users'],
            ['display_name' => 'Users', 'parent_id' => null]
        );

        $project_permission = Permission::updateOrCreate(
            ['name' => 'projects'],
            ['display_name' => 'Projects', 'parent_id' => null]
        );

        $task_permission = Permission::updateOrCreate(
            ['name' => 'task'],
            ['display_name' => 'Tasks', 'parent_id' => null]
        );

        $timesheet_permission = Permission::updateOrCreate(
            ['name' => 'timesheet'],
            ['display_name' => 'Timesheet', 'parent_id' => null]
        );

        // Children (actions)

        // TEAMS        
        $team_permissions = [
            'module teams' => 'Enable',
            'view own teams' => 'View Own Teams',
            'view teams' => 'View All Teams',
            'edit teams' => 'Create/Edit Teams',
            'delete teams' => 'Delete Teams',
        ];

        foreach ($team_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $label,
                    'parent_id' => $team_permission->id
                ]
            );
        }

        // ROLES
        $role_permissions = [
            'module roles' => 'Enable',
            'edit roles' => 'Create/Edit Roles',
            'delete roles' => 'Delete Roles',
        ];

        foreach ($role_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $label,
                    'parent_id' => $role_permission->id
                ]
            );
        }

        // LEAVE REQUESTS
        $leave_permissions = [
            'module leaves' => 'Enable',
            'view own leaves' => 'View Own Leaves',
            'edit own leaves' => 'Create/Edit Own Leaves',
            'delete own leaves' => 'Delete Own Leaves',
            'view team leaves' => 'View Team Leaves',
            'edit team leaves' => 'Create/Edit Team Leaves',
            'approve team leaves' => 'Approve Team Leaves',
            'delete team leaves' => 'Delete Team Leaves',
            'edit team leaves balance' => 'Edit Team Leaves Balance',
            'view all leaves' => 'View All Leaves',
            'edit all leaves' => 'Create/Edit All Leaves',
            'delete all leaves' => 'Delete All Leaves',
            'approve all leaves' => 'Approve All Leaves',
            'edit all leaves balance' => 'Edit All Leaves Balance',
        ];

        foreach ($leave_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $label,
                    'parent_id' => $leave_permission->id
                ]
            );
        }

        // OT REQUESTS
        $ot_permissions = [
            'module ot' => 'Enable',
            'view own ot' => 'View Own OT Requests',
            'edit own ot' => 'Create/Edit Own OT Requests',
            'delete own ot' => 'Delete Own OT Requests',
            'view team ot' => 'View Team OT Requests',
            'edit team ot' => 'Create/Edit Team OT Requests',
            'delete team ot' => 'Delete Team OT Requests',
            'approve team ot' => 'Approve Team OT Requests',
            'view all ot' => 'View All OT Requests',
            'edit all ot' => 'Create/Edit All OT Requests',
            'delete all ot' => 'Delete All OT Requests',
            'approve all ot' => 'Approve All OT Requests',
        ];

        foreach ($ot_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $label,
                    'parent_id' => $ot_permission->id
                ]
            );
        }

        // ANNOUNCEMENTS & POLICIES
        $announcement_permissions = [
            'module announcements' => 'Enable',
            'edit announcements' => 'Create/Edit Announcements',
            'delete announcements' => 'Delete Announcements',
            'edit policies' => 'Edit Policies',
        ];

        foreach ($announcement_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $label,
                    'parent_id' => $announcement_permission->id
                ]
            );
        }

        // USERS
        $user_permissions = [
            'module user' => 'Enable',
            'view team user' => 'View Team\'s User Profile',
            'edit team user' => 'Edit Team\'s User Profile',
            'delete team user' => 'Delete Team\'s User Profile',
            'view all user' => 'View All User Profile',
            'create all user' => 'Create All User Profile',
            'edit all user' => 'Edit All User Profile',
            'delete all user' => 'Delete All User Profile',
        ];

        foreach ($user_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $label,
                    'parent_id' => $user_permission->id
                ]
            );
        }

        // PROJECTS        
        $project_permissions = [
            'module projects' => 'Enable',
            'view all projects' => 'View All Projects',
            'view assigned projects' => 'View Assigned Projects',
            'edit projects' => 'Create/Edit Projects',
            'edit assigned projects' => 'Edit Assigned Projects',
            'delete projects' => 'Delete All Projects',
            'edit own project files' => 'Upload/Edit Own Files',
            'edit all project files' => 'Upload/Edit All Files & Folders',
        ];

        foreach ($project_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $label,
                    'parent_id' => $project_permission->id
                ]
            );
        }

        // TASKS        
        $task_permissions = [
            'module tasks' => 'Enable',
            'view all tasks' => 'View All Tasks',
            'view team tasks' => 'View Team Tasks',
            'view assigned tasks' => 'View Assigned Tasks',
            'edit tasks' => 'Create/Edit All Tasks',
            'edit team tasks' => 'Create/Edit Team Tasks',
            'edit assigned tasks' => 'Edit Assigned Tasks',
            'delete tasks' => 'Delete All Tasks',
        ];

        foreach ($task_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $label,
                    'parent_id' => $task_permission->id
                ]
            );
        }

        // TIMESHEET        
        $timesheet_permissions = [
            'module timesheet' => 'Enable',
            'view all timesheet' => 'View All Timesheet',
            'view team timesheet' => 'View Team Timesheet',
            'view own timesheet' => 'View Own Timesheet',
            'edit timesheet' => 'Log Time For All',
            'edit team timesheet' => 'Log Time For Team',
            'edit own timesheet' => 'Log Time For Yourself',
        ];

        foreach ($timesheet_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $label,
                    'parent_id' => $timesheet_permission->id
                ]
            );
        }

        // ATTENDANCE
        $attendance_permission = Permission::updateOrCreate(
            ['name' => 'attendance'],
            ['display_name' => 'Attendance', 'parent_id' => null]
        );

        $attendance_permissions = [
            'module attendance'   => 'Enable',
            'view all attendance' => 'View All Attendance',
            'approve attendance'  => 'Approve WFH Requests',
        ];

        foreach ($attendance_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $label,
                    'parent_id'    => $attendance_permission->id,
                ]
            );
        }

        // SETTINGS
        $settings_permission = Permission::updateOrCreate(
            ['name' => 'settings'],
            ['display_name' => 'Settings', 'parent_id' => null]
        );

        $settings_permissions = [
            'module settings' => 'Enable',
            'manage settings' => 'Manage System Settings',
        ];

        foreach ($settings_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $label, 'parent_id' => $settings_permission->id]
            );
        }

        // RECRUITMENT
        $recruitment_parent = Permission::updateOrCreate(
            ['name' => 'recruitment'],
            ['display_name' => 'Recruitment', 'parent_id' => null]
        );

        $recruitment_permissions = [
            'module recruitment' => 'Enable',
            'edit recruitment'   => 'Create / Edit / Delete Positions & Applicants',
        ];

        foreach ($recruitment_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $label, 'parent_id' => $recruitment_parent->id]
            );
        }

        // CALENDAR & EVENTS
        $calendar_parent = Permission::updateOrCreate(
            ['name' => 'calendar'],
            ['display_name' => 'Calendar & Events', 'parent_id' => null]
        );

        $calendar_permissions = [
            'module calendar' => 'Enable',
            'edit events'     => 'Create / Edit / Delete Events',
        ];

        foreach ($calendar_permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $label, 'parent_id' => $calendar_parent->id]
            );
        }
    }
}
