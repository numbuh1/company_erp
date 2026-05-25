<?php

namespace App\Helper;

use App\Models\User;
use App\Notifications\GeneralNotification;

class NotificationHelper
{
    /**
     * Send a database notification to a user.
     *
     * @param  User       $receivingUser  The user who receives the notification
     * @param  string     $title          Short heading shown in the bell dropdown
     * @param  string     $description    Supporting detail line
     * @param  string     $url            Where to navigate when the user clicks the notification
     * @param  User|null  $incomingUser   Optional sender (avatar shown in the bell dropdown)
     */
    public static function send(
        User $receivingUser,
        string $title,
        string $description,
        string $url,
        ?User $incomingUser = null
    ): void {
        $receivingUser->notify(new GeneralNotification(
            title: $title,
            description: $description,
            url: $url,
            incomingUserId: $incomingUser?->id,
        ));
    }

    public static function sendRequestApprovalNotification($request, $type)
    {
        $message = '';
        $url = '';
        $status = $request->status == 'approved' ? ' đã được duyệt' : ' bị từ chối';
        $request_type = $type == 'leave' ? 'Nghĩ phép' : 'OT';
        switch($type) {
            case 'leave':
                $title = 'Yêu cầu Nghỉ phép '  . $request->status;
                $type = ' leave request ';
                $url = route('leave-requests.index');
                break;
            case 'ot':
                $title = 'Yêu cầu OT '  . $request->status;
                $type = ' request ';
                $url = route('overtime-requests.index');
                break;
            default:
                break;
        }


        switch($request->status) {
            case 'approved':
                $message = 'Yêu cầu ' . $request_type . ' (' .
                            $request->start_at->format('d/m/Y') . ') ' . $status . '.';
                break;
            case 'rejected':
                $message = 'Yêu cầu ' . $request_type . ' (' .
                            $request->start_at->format('d/m/Y') . ') ' . $status . '. ' .
                            'Lý do: ' . $request->reject_reason;
                break;
            default:
                break;
        }

        NotificationHelper::send(
            receivingUser: $request->user,
            title: $title,
            description: $message,
            url: $url,
            incomingUser: auth()->user(),
        );
    }

    /**
     * Notify team leaders, supervisors, and permission holders when a new leave/OT request is submitted.
     */
    public static function sendNewRequestNotification($leaveOrOtRequest, string $type): void
    {
        $requester = User::find($leaveOrOtRequest->user_id);
        if (!$requester) return;

        // 1. Team leaders of the requester's teams
        $teamIds   = $requester->teams()->pluck('teams.id');
        $leaderIds = User::whereHas('teams', function ($q) use ($teamIds) {
            $q->whereIn('teams.id', $teamIds)->where('team_user.is_leader', true);
        })->pluck('id');

        // 2. Supervisors
        $supervisorIds = $requester->supervisors()->pluck('users.id');

        // 3. Permission holders
        $permName      = $type === 'leave' ? 'receive all leave notifications' : 'receive all ot notifications';
        $permHolderIds = User::permission($permName)->pluck('id');

        $notifyUserIds = collect()
            ->merge($leaderIds)
            ->merge($supervisorIds)
            ->merge($permHolderIds)
            ->unique()
            ->filter(fn($id) => $id !== $requester->id);

        if ($type === 'leave') {
            $title       = 'Yêu cầu Nghỉ phép mới';
            $description = $requester->name . ' gửi yêu cầu nghỉ phép ('
                . $leaveOrOtRequest->start_at->format('d/m/Y') . ')';
            $url         = route('leave-requests.index');
        } else {
            $title       = 'Yêu cầu OT mới';
            $description = $requester->name . ' gửi yêu cầu tăng ca ('
                . $leaveOrOtRequest->start_at->format('d/m/Y') . ')';
            $url         = route('overtime-requests.index');
        }

        foreach ($notifyUserIds as $userId) {
            $receiver = User::find($userId);
            if ($receiver) {
                self::send(
                    receivingUser: $receiver,
                    title: $title,
                    description: $description,
                    url: $url,
                    incomingUser: $requester,
                );
            }
        }
    }
}
