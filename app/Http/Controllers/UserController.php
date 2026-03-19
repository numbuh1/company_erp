<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LeaveBalanceLog;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'teams'])->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('users.form', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',            
            'roles' => 'array'
        ]);

        $data['password'] = bcrypt($data['password']);

        $user = User::create($data);

        if (auth()->user()->can('edit all user')) {
            $user->syncRoles($request->roles ?? []);
        }

        if (auth()->user()->canAny(['edit team leaves balance', 'edit all leaves balance'])) {
            $balance = $request->input('leave_balance', 112);
            $user->update(['leave_balance' => $balance]);
            LeaveBalanceLog::create([
                'user_id'      => $user->id,
                'changed_by'   => auth()->id(),
                'change_hours' => $balance,
                'balance_after'=> $balance,
                'reason'       => 'Initial balance',
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User created');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('users.form', compact('user', 'roles'));
    }

    public function profile()
    {
        $user = auth()->user();
        $roles = Role::all();
        return view('users.form', compact('user', 'roles'));
    }

    public function show(User $user)
    {
        $user->load(['roles', 'teams']);
        $leaveRequests = \App\Models\LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->orderByRaw("FIELD(status, 'pending', 'approved')")
            ->latest()
            ->get();
        return view('users.show', compact('user', 'leaveRequests'));
    }


    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'position' => 'nullable|string|max:255',
            'roles' => 'array'
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);


        if ($request->filled('profile_picture_cropped')) {
            // Decode base64 and save
            $imageData = $request->input('profile_picture_cropped');
            // Strip the data URI prefix: "data:image/jpeg;base64,..."
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageData = base64_decode($imageData);

            $filename = 'profile_' . $user->id . '_' . time() . '.jpg';
            Storage::disk('public')->put('profile_pictures/' . $filename, $imageData);

            // Delete old picture
            if ($user->profile_picture) {
                Storage::disk('public')->delete('profile_pictures/' . $user->profile_picture);
            }

            $user->profile_picture = $filename;
            $user->save();
        }

        if (auth()->user()->can('edit all user')) {
            $user->syncRoles($request->roles ?? []);
        }

        if (auth()->user()->canAny(['edit team leaves balance', 'edit all leaves balance'])
            && $request->filled('leave_balance')) {
            $old = $user->leave_balance;
            $new = (float) $request->input('leave_balance');
            $user->update(['leave_balance' => $new]);
            LeaveBalanceLog::create([
                'user_id'      => $user->id,
                'changed_by'   => auth()->id(),
                'change_hours' => $new - $old,
                'balance_after'=> $new,
                'reason'       => $request->input('balance_reason'),
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User updated');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return back()->with('success', 'User deleted');
    }

    public function leaveBalanceHistory(User $user)
    {
        $logs = $user->leaveBalanceLogs()
            ->with('changedBy')
            ->latest()
            ->paginate(20);

        return view('users.leave_balance_history', compact('user', 'logs'));
    }

    public function resetPassword(Request $request, User $user)
    {
        $authUser = auth()->user();

        if ($authUser->id !== $user->id && !$authUser->can('edit all user')) {
            abort(403);
        }

        $data = $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $user->update(['password' => bcrypt($data['password'])]);

        return back()->with('success', 'Password updated.');
    }

}