<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\User;
use App\Helper\Helper;
use App\Helper\NotificationHelper;
use Illuminate\Http\Request;

class OvertimeRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user  = auth()->user();
        $query = OvertimeRequest::with('user', 'approver');

        if ($user->can('view all ot')) {
            // no filter
        } elseif ($user->can('view team ot')) {
            $query->whereIn('user_id', $user->teamMembers()->pluck('id'));
        } else {
            $query->where('user_id', $user->id);
        }

        $otRequests = $query->latest()->paginate(10);
        return view('overtime_requests.index', compact('otRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user  = auth()->user();
        $query = User::query();

        if ($user->can('edit all ot')) {
            // no filter
        } elseif ($user->can('edit team ot')) {
            $query->whereIn('id', $user->teamMembers()->pluck('id'));
        } else {
            $query->where('id', $user->id);
        }

        $users = $query->get();
        return view('overtime_requests.form', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after_or_equal:start_at',
            'hours'       => 'required|numeric|min:0',
            'type'        => 'required|string',
            'description' => 'nullable|string',
        ]);

        $user = auth()->user();
        if (!$user->can('edit team ot') && !$user->can('edit all ot')) {
            $request->merge(['user_id' => $user->id]);
        }

        OvertimeRequest::create($request->all());
        return redirect()->route('overtime-requests.index')->with('success', 'OT request created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(OvertimeRequest $overtimeRequest)
    {
        Helper::authorizeRequest('view all ot', 'view team ot', $overtimeRequest);

        return view('overtime_requests.form', [
            'ot'       => $overtimeRequest,
            'readonly' => true,
            'users'    => collect([$overtimeRequest->user]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OvertimeRequest $overtimeRequest)
    {
        if (in_array($overtimeRequest->status, ['approved', 'rejected'])) {
            abort(403, 'Cannot edit an approved or rejected OT request.');
        }

        Helper::authorizeRequest('edit all ot', 'edit team ot', $overtimeRequest);

        return view('overtime_requests.form', [
            'ot'    => $overtimeRequest,
            'users' => collect([$overtimeRequest->user]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OvertimeRequest $overtimeRequest)
    {
        Helper::authorizeRequest('edit all ot', 'edit team ot', $overtimeRequest);

        $data = $request->validate([
            'user_id'     => 'required|exists:users,id',
            'type'        => 'required|string',
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after:start_at',
            'hours'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $overtimeRequest->update($data);
        return redirect()->route('overtime-requests.index')->with('success', 'OT request updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OvertimeRequest $overtimeRequest)
    {
        if (in_array($overtimeRequest->status, ['approved', 'rejected'])) {
            abort(403, 'Cannot delete an approved or rejected OT request.');
        }

        Helper::authorizeRequest('delete all ot', 'delete team ot', $overtimeRequest);

        $overtimeRequest->delete();
        return back()->with('success', 'OT request deleted.');
    }

    /**
     * Approve a pending Overtime Request
     */
    public function approve(OvertimeRequest $overtimeRequest)
    {
        $user = auth()->user();
        Helper::authorizeRequest('approve all ot', 'approve team ot', $overtimeRequest);        

        $overtimeRequest->update([
            'status'        => 'approved',
            'approved_by'   => $user->id,
            'reject_reason' => null,
        ]);

        NotificationHelper::sendRequestApprovalNotification($overtimeRequest, 'ot');

        return back()->with('success', 'OT request approved.');
    }

    /**
     * Reject a pending Overtime Request (With reason)
     */
    public function reject(Request $request, OvertimeRequest $overtimeRequest)
    {
        $user = auth()->user();
        Helper::authorizeRequest('approve all ot', 'approve team ot', $overtimeRequest);        

        $data = $request->validate(['reject_reason' => 'required|string|max:500']);

        $overtimeRequest->update([
            'status'        => 'rejected',
            'approved_by'   => $user->id,
            'reject_reason' => $data['reject_reason'],
        ]);

        NotificationHelper::sendRequestApprovalNotification($overtimeRequest, 'ot');

        return back()->with('success', 'OT request rejected.');
    }

    // public function authorize(string $all_permission, string $team_permission, $overtimeRequest) {
    //     $user = auth()->user();

    //     if (!$user->can($all_permission)) {
    //         if(!$user->can($team_permission)) {
    //             return abort(403);
    //         }

    //         $check_leader = Helper::checkLeadOfTeamMate($overtimeRequest->user);
    //         if(!$check_leader) {
    //             return abort(403);
    //         }
    //     }

    //     return true;
    // }
}
