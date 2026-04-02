<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\OvertimeRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\RecruitmentController;
use App\Http\Controllers\RecruitmentApplicantController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('users', UserController::class);
    Route::get('users/{user}/leave-balance-history', [UserController::class, 'leaveBalanceHistory'])
        ->name('users.leave-balance-history');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('users.reset-password');
    Route::get('/profile', [UserController::class, 'profile'])->name('users.profile');

    Route::resource('teams', TeamController::class);
    Route::post('/teams/{team}/assign-user', [TeamUserController::class, 'store']);
    Route::delete('/teams/{team}/remove-user/{user}', [TeamUserController::class, 'destroy']);

    Route::resource('roles', RoleController::class);

    Route::resource('leave-requests', LeaveRequestController::class);
    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])
        ->name('leave-requests.approve');

    Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])
        ->name('leave-requests.reject');

    Route::resource('overtime-requests', OvertimeRequestController::class);
    Route::post('overtime-requests/{overtimeRequest}/approve', [OvertimeRequestController::class, 'approve'])
        ->name('overtime-requests.approve');
    Route::post('overtime-requests/{overtimeRequest}/reject', [OvertimeRequestController::class, 'reject'])
        ->name('overtime-requests.reject');


    Route::get('/projects/search', [ProjectController::class, 'search'])->name('projects.search');
    Route::get('/tasks/search', [TaskController::class, 'search'])->name('tasks.search');

    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/files', [ProjectController::class, 'uploadFile'])
        ->name('projects.files.upload');
    Route::post('projects/{project}/files/{file}/rename', [ProjectController::class, 'renameItem'])
        ->name('projects.files.rename');
    Route::delete('projects/{project}/files/{file}', [ProjectController::class, 'deleteItem'])
        ->name('projects.files.delete');
    Route::post('projects/{project}/folders', [ProjectController::class, 'createFolder'])
        ->name('projects.folders.create');
    Route::get('projects/{project}/files/{file}/download', [ProjectController::class, 'downloadItem'])
        ->name('projects.files.download');

    Route::resource('tasks', TaskController::class);

    Route::resource('time-logs', TimeLogController::class);
    Route::get('timesheets/weekly', [TimeLogController::class, 'weekly'])->name('timesheets.weekly');

    Route::post('announcements/upload-image', [AnnouncementController::class, 'uploadImage'])
        ->name('announcements.upload-image');
    Route::resource('announcements', AnnouncementController::class);

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('/attendance/{attendance}/approve', [AttendanceController::class, 'approve'])->name('attendance.approve');
    Route::post('/attendance/{attendance}/reject', [AttendanceController::class, 'reject'])->name('attendance.reject');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read', [NotificationController::class, 'mark-read'])->name('notifications.mark-read'); 

    Route::get('/admin/settings', [SettingController::class, 'edit'])->name('admin.settings.edit');
    Route::put('/admin/settings', [SettingController::class, 'update'])->name('admin.settings.update');

    Route::resource('skills', SkillController::class)->except(['show']);

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Events
    Route::get('/events',                      [EventController::class, 'index'])->name('events.index');
    Route::post('/events',                     [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/edit',         [EventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}',              [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}',           [EventController::class, 'destroy'])->name('events.destroy');
    Route::get('/events/users',                [EventController::class, 'userOptions'])->name('events.users');
    Route::get('/events/locations',            [EventController::class, 'locationOptions'])->name('events.locations');
    Route::get('/events/{event}/data',         [EventController::class, 'apiShow'])->name('events.data');


    // Recruitment
    Route::prefix('recruitment')->name('recruitment.')->group(function () {
        Route::get('/',                                             [RecruitmentController::class, 'index'])->name('index');
        Route::get('/create',                                       [RecruitmentController::class, 'create'])->name('create');
        Route::post('/',                                            [RecruitmentController::class, 'store'])->name('store');
        Route::get('/{recruitmentPosition}',                        [RecruitmentController::class, 'show'])->name('show');
        Route::get('/{recruitmentPosition}/edit',                   [RecruitmentController::class, 'edit'])->name('edit');
        Route::put('/{recruitmentPosition}',                        [RecruitmentController::class, 'update'])->name('update');
        Route::delete('/{recruitmentPosition}',                     [RecruitmentController::class, 'destroy'])->name('destroy');
        Route::get('/{recruitmentPosition}/jd/download',            [RecruitmentController::class, 'downloadJd'])->name('jd.download');

        Route::get('/{recruitmentPosition}/applicants/create',                              [RecruitmentApplicantController::class, 'create'])->name('applicants.create');
        Route::post('/{recruitmentPosition}/applicants',                                    [RecruitmentApplicantController::class, 'store'])->name('applicants.store');
        Route::get('/{recruitmentPosition}/applicants/{recruitmentApplicant}/edit',         [RecruitmentApplicantController::class, 'edit'])->name('applicants.edit');
        Route::put('/{recruitmentPosition}/applicants/{recruitmentApplicant}',              [RecruitmentApplicantController::class, 'update'])->name('applicants.update');
        Route::delete('/{recruitmentPosition}/applicants/{recruitmentApplicant}',           [RecruitmentApplicantController::class, 'destroy'])->name('applicants.destroy');
        Route::get('/{recruitmentPosition}/applicants/{recruitmentApplicant}/cv/download',  [RecruitmentApplicantController::class, 'downloadCv'])->name('applicants.cv.download');
    });

});

require __DIR__.'/auth.php';
