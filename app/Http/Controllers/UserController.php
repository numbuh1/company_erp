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
        $users           = User::with(['roles', 'teams', 'salaryRecord'])->paginate(20);
        $canViewSalary   = auth()->user()->canAny(['view salary', 'edit all user']);
        $canViewPersonal = auth()->user()->canAny(['view all user personal info', 'edit all user']);
        return view('users.index', compact('users', 'canViewSalary', 'canViewPersonal'));
    }

    public function create()
    {
        $roles            = Role::all();
        $supervisorOptions = User::orderBy('name')->get(['id', 'name', 'position']);
        return view('users.form', compact('roles', 'supervisorOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required',
            'full_name'            => 'nullable|string|max:255',
            'email'                => 'required|email|unique:users',
            'password'             => 'required|min:6|confirmed',
            'birthday'             => 'nullable|date',
            'contract_expiry'      => 'nullable|date',
            'salary'               => 'nullable|integer|min:0',
            'salary_type'          => 'nullable|in:monthly,weekly,daily,hourly',
            'phone_number'         => 'nullable|string|max:30',
            'citizen_id'           => 'nullable|string|max:30',
            'home_address'         => 'nullable|string',
            'tax_code'             => 'nullable|string|max:20',
            'social_insurance_id'  => 'nullable|string|max:20',
            'roles'                => 'array',
            // Salary table fields
            'allowance_adjustment' => 'nullable|integer',
            'allowance_bonus'      => 'nullable|integer|min:0',
            'allowance_excl_tax'   => 'nullable|integer|min:0',
            'parking_fee'          => 'nullable|integer|min:0',
            'insurance'            => 'nullable|integer|min:0',
            'personal_income_tax'  => 'nullable|integer|min:0',
            'other_deduction'      => 'nullable|integer|min:0',
        ]);

        $data['password'] = bcrypt($data['password']);

        $user = User::create($data);

        if (auth()->user()->can('edit all user')) {
            $user->syncRoles($request->roles ?? []);
            $user->supervisors()->sync($request->input('supervisors', []));
            $user->update([
                'wfh_without_approval' => $request->boolean('wfh_without_approval'),
                'salary'               => $request->input('salary') ?: null,
                'salary_type'          => $request->input('salary_type') ?: null,
                'phone_number'         => $request->input('phone_number'),
                'citizen_id'           => $request->input('citizen_id'),
                'home_address'         => $request->input('home_address'),
                'tax_code'             => $request->input('tax_code'),
                'social_insurance_id'  => $request->input('social_insurance_id'),
            ]);
            $user->salaryRecord()->updateOrCreate([], [
                'salary'               => $request->input('salary') ?: null,
                'salary_type'          => $request->input('salary_type') ?: null,
                'allowance_adjustment' => $request->input('allowance_adjustment') ?: null,
                'allowance_bonus'      => $request->input('allowance_bonus') ?: null,
                'allowance_excl_tax'   => $request->input('allowance_excl_tax') ?: null,
                'parking_fee'          => $request->input('parking_fee') ?: null,
                'insurance'            => $request->input('insurance') ?: null,
                'personal_income_tax'  => $request->input('personal_income_tax') ?: null,
                'other_deduction'      => $request->input('other_deduction') ?: null,
            ]);
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
        $roles             = Role::all();
        $supervisorOptions = User::where('id', '!=', $user->id)->orderBy('name')->get(['id', 'name', 'position']);
        $user->load(['supervisors', 'salaryRecord']);
        return view('users.form', compact('user', 'roles', 'supervisorOptions'));
    }

    public function profile()
    {
        $user = auth()->user();
        $user->load('supervisors');
        $roles             = Role::all();
        $supervisorOptions = User::where('id', '!=', $user->id)->orderBy('name')->get(['id', 'name', 'position']);
        return view('users.form', compact('user', 'roles', 'supervisorOptions'));
    }

    public function show(User $user)
    {
        $user->load(['roles', 'teams', 'supervisors']);
        $leaveRequests = \App\Models\LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->orderByRaw("FIELD(status, 'pending', 'approved')")
            ->latest()
            ->get();
        $isOwnProfile    = auth()->id() === $user->id;
        $canViewSalary   = $isOwnProfile || auth()->user()->canAny(['view salary', 'edit all user']);
        $canViewPersonal = $isOwnProfile || auth()->user()->canAny(['view all user personal info', 'edit all user']);
        return view('users.show', compact('user', 'leaveRequests', 'canViewSalary', 'canViewPersonal'));
    }


    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'                 => 'required',
            'full_name'            => 'nullable|string|max:255',
            'email'                => 'required|email|unique:users,email,' . $user->id,
            'password'             => 'nullable|min:6|confirmed',
            'position'             => 'nullable|string|max:255',
            'birthday'             => 'nullable|date',
            'contract_expiry'      => 'nullable|date',
            'salary'               => 'nullable|integer|min:0',
            'salary_type'          => 'nullable|in:monthly,weekly,daily,hourly',
            'phone_number'         => 'nullable|string|max:30',
            'citizen_id'           => 'nullable|string|max:30',
            'home_address'         => 'nullable|string',
            'tax_code'             => 'nullable|string|max:20',
            'social_insurance_id'  => 'nullable|string|max:20',
            'roles'                => 'array',
            // Salary table fields
            'allowance_adjustment' => 'nullable|integer',
            'allowance_bonus'      => 'nullable|integer|min:0',
            'allowance_excl_tax'   => 'nullable|integer|min:0',
            'parking_fee'          => 'nullable|integer|min:0',
            'insurance'            => 'nullable|integer|min:0',
            'personal_income_tax'  => 'nullable|integer|min:0',
            'other_deduction'      => 'nullable|integer|min:0',
        ]);

        // HR-only fields: strip from data when caller isn't an admin
        if (!auth()->user()->can('edit all user')) {
            foreach (['contract_expiry','phone_number','citizen_id','home_address','tax_code','social_insurance_id','salary','salary_type'] as $f) {
                unset($data[$f]);
            }
        }

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        if (auth()->user()->can('edit all user')) {
            $user->update(['is_active' => $request->boolean('is_active')]);
        }

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
            $user->supervisors()->sync($request->input('supervisors', []));
            $user->update([
                'is_active'            => $request->boolean('is_active'),
                'wfh_without_approval' => $request->boolean('wfh_without_approval'),
                'salary'               => $request->input('salary') ?: null,
                'salary_type'          => $request->input('salary_type') ?: null,
                'phone_number'         => $request->input('phone_number'),
                'citizen_id'           => $request->input('citizen_id'),
                'home_address'         => $request->input('home_address'),
                'tax_code'             => $request->input('tax_code'),
                'social_insurance_id'  => $request->input('social_insurance_id'),
            ]);
            $user->salaryRecord()->updateOrCreate([], [
                'salary'               => $request->input('salary') ?: null,
                'salary_type'          => $request->input('salary_type') ?: null,
                'allowance_adjustment' => $request->input('allowance_adjustment') ?: null,
                'allowance_bonus'      => $request->input('allowance_bonus') ?: null,
                'allowance_excl_tax'   => $request->input('allowance_excl_tax') ?: null,
                'parking_fee'          => $request->input('parking_fee') ?: null,
                'insurance'            => $request->input('insurance') ?: null,
                'personal_income_tax'  => $request->input('personal_income_tax') ?: null,
                'other_deduction'      => $request->input('other_deduction') ?: null,
            ]);
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