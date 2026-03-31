<?php
namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Support\Collection;

class AttendanceStatusCache
{
    private static ?Collection $attendances = null;
    private static ?Collection $leaveUserIds = null;

    private static function load(): void
    {
        if (static::$attendances !== null) return;

        $today = now()->toDateString();

        static::$attendances = Attendance::whereDate('date', $today)
            ->get()
            ->keyBy('user_id');

        static::$leaveUserIds = LeaveRequest::where('status', 'approved')
            ->whereDate('start_at', '<=', $today)
            ->whereDate('end_at', '>=', $today)
            ->pluck('user_id')
            ->flip();
    }

    public static function getAttendance(int $userId): mixed
    {
        static::load();
        return static::$attendances->get($userId);
    }

    public static function isOnLeave(int $userId): bool
    {
        static::load();
        return static::$leaveUserIds->has($userId);
    }
}
