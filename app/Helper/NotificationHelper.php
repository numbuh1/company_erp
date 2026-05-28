<?php

namespace App\Helper;

use App\Mail\NewRequestMail;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Mail;

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
     * Notify team leaders / supervisors / approvers when a new leave or OT request is submitted.
     *
     * Routing logic:
     *  1. Primary (notification + email TO):  team leaders of the requester's teams
     *  2. Fallback primary:                   supervisors (if no team leaders)
     *  3. Fallback primary:                   "approve all" permission holders (if no supervisors)
     *  4. CC (email only, always):            "approve all" permission holders not already in primary
     *  5. In-app notification:                primary ∪ "approve all" holders (deduplicated)
     *
     * The requester is never notified.
     */
    public static function sendNewRequestNotification($leaveOrOtRequest, string $type): void
    {
        $requester = User::find($leaveOrOtRequest->user_id);
        if (!$requester) return;

        // --- Resolve recipients ---

        // "approve all" permission holders
        $approvePermission = $type === 'leave' ? 'approve all leaves' : 'approve all ot';
        $allApproverIds    = User::permission($approvePermission)->pluck('id');

        // Team leaders of the requester's teams
        $teamIds   = $requester->teams()->pluck('teams.id');
        $leaderIds = $teamIds->isNotEmpty()
            ? User::whereHas('teams', function ($q) use ($teamIds) {
                $q->whereIn('teams.id', $teamIds)->where('team_user.is_leader', true);
              })->pluck('id')
            : collect();

        // Supervisors
        $supervisorIds = $requester->supervisors()->pluck('users.id');

        // Determine primary (TO) and cc
        if ($leaderIds->isNotEmpty()) {
            $primaryIds = $leaderIds;
            $ccIds      = $allApproverIds->diff($leaderIds);
        } elseif ($supervisorIds->isNotEmpty()) {
            $primaryIds = $supervisorIds;
            $ccIds      = $allApproverIds->diff($supervisorIds);
        } else {
            // No leader or supervisor — approvers receive both primary email and notification
            $primaryIds = $allApproverIds;
            $ccIds      = collect();
        }

        // Exclude the requester themselves from all lists
        $rid        = $requester->id;
        $primaryIds = $primaryIds->filter(fn($id) => $id !== $rid)->values();
        $ccIds      = $ccIds->filter(fn($id) => $id !== $rid)->values();
        $notifyIds  = $primaryIds->merge($allApproverIds)->unique()->filter(fn($id) => $id !== $rid)->values();

        // --- Build notification content ---
        if ($type === 'leave') {
            $title       = 'Yêu cầu Nghỉ phép mới';
            $description = $requester->name . ' gửi yêu cầu nghỉ phép ('
                . $leaveOrOtRequest->start_at->format('d/m/Y') . ')';
            $url         = route('requests.index', ['type' => 'leave', 'status' => 'pending']);
        } else {
            $title       = 'Yêu cầu OT mới';
            $description = $requester->name . ' gửi yêu cầu tăng ca ('
                . $leaveOrOtRequest->start_at->format('d/m/Y') . ')';
            $url         = route('requests.index', ['type' => 'ot', 'status' => 'pending']);
        }

        // --- Send in-app notifications ---
        foreach ($notifyIds as $userId) {
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

        // --- Send email ---
        if ($primaryIds->isNotEmpty()) {
            $emailKey     = $type; // 'leave' or 'ot'
            $primaryUsers = User::with('preferences')->whereIn('id', $primaryIds)->get()
                ->filter(fn($u) => $u->emailNotificationEnabled($emailKey));
            $ccUsers      = $ccIds->isNotEmpty()
                ? User::with('preferences')->whereIn('id', $ccIds)->get()
                    ->filter(fn($u) => $u->emailNotificationEnabled($emailKey))
                : collect();

            try {
                $mailer = Mail::to($primaryUsers);
                if ($ccUsers->isNotEmpty()) {
                    $mailer = $mailer->cc($ccUsers);
                }
                $mailer->send(new NewRequestMail($leaveOrOtRequest, $type, $requester));
            } catch (\Throwable $e) {
                logger()->error("New {$type} request email failed for request #{$leaveOrOtRequest->id}: " . $e->getMessage());
            }
        }
    }
}
