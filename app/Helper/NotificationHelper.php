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
        switch($type) {
            case 'leave':
                $title = 'Leave Request '  . $request->status;
                $type = ' leave request ';
                $url = route('leave-requests.index');
                break;
            case 'ot':
                $title = 'OT Request '  . $request->status;
                $type = ' request ';
                $url = route('overtime-requests.index');
                break;
            default:
                break;
        }


        switch($request->status) {
            case 'approved':
                $message = 'Your ' . $request->type .  $type . ' (' .
                            $request->start_at->format('d/m/Y') . ') has been approved.';
                break;
            case 'rejected':
                $message = 'Your ' . $request->type .  $type . ' (' .
                            $request->start_at->format('d/m/Y') . ') has been rejected. ' .
                            'Reason: ' . $request->reject_reason;
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
}
