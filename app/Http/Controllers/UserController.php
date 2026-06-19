<?php

namespace App\Http\Controllers;

use App\Helper\NotificationHelper;
use App\Mail\WelcomeUserMail;
use App\Models\RecruitmentApplicant;
use App\Models\Team;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\LeaveBalanceLog;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $users           = User::with(['roles', 'teams', 'salaryRecord'])->paginate(20);
        $canViewSalary   = auth()->user()->canAny(['view salary', 'edit all user']);
        $canViewPersonal = auth()->user()->canAny(['view all user personal info', 'edit all user']);
        return view('users.index', compact('users', 'canViewSalary', 'canViewPersonal'));
    }

    public function create(Request $request)
    {
        $roles            = Role::all();
        $supervisorOptions = User::orderBy('name')->get(['id', 'name', 'position']);

        // Pre-fill values when arriving from the "Begin Onboard" action
        // on a hired recruitment applicant.
        $prefill = [
            'name'                     => $request->query('name'),
            'contact_email'            => $request->query('contact_email'),
            'phone_number'             => $request->query('phone_number'),
            'position'                 => $request->query('position'),
            'recruitment_applicant_id' => $request->query('recruitment_applicant_id'),
        ];

        return view('users.form', compact('roles', 'supervisorOptions', 'prefill'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required',
            'full_name'            => 'nullable|string|max:255',
            'email'                => 'required|email|unique:users',
            'contact_email'        => 'nullable|email|max:255',
            'password'             => 'required|min:6|confirmed',
            'position'             => 'nullable|string|max:255',
            'grade'                => 'nullable|string|max:255',
            'birthday'             => 'nullable|date',
            'contract_expiry'      => 'nullable|date',
            'salary'               => 'nullable|integer|min:0',
            'salary_type'          => 'nullable|in:monthly,weekly,daily,hourly',
            'phone_number'         => 'nullable|string|max:30',
            'citizen_id'           => 'nullable|string|max:30',
            'home_address'         => 'nullable|string',
            'tax_code'             => 'nullable|string|max:20',
            'social_insurance_id'  => 'nullable|string|max:20',
            'employment_status'    => 'nullable|in:active,on_probation,inactive',
            'probation_start_date' => 'nullable|date',
            'probation_end_date'   => 'nullable|date',
            'recruitment_applicant_id' => 'nullable|exists:recruitment_applicants,id',
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

        $plainPassword    = $data['password'];
        $data['password'] = bcrypt($plainPassword);

        $user = User::create($data);

        if (auth()->user()->can('edit all user')) {
            $user->syncRoles($request->roles ?? []);
            $user->supervisors()->sync($request->input('supervisors', []));

            $employmentStatus = $request->input('employment_status', 'active');

            $user->update([
                'wfh_without_approval' => $request->boolean('wfh_without_approval'),
                'salary'               => $request->input('salary') ?: null,
                'salary_type'          => $request->input('salary_type') ?: null,
                'phone_number'         => $request->input('phone_number'),
                'citizen_id'           => $request->input('citizen_id'),
                'home_address'         => $request->input('home_address'),
                'tax_code'             => $request->input('tax_code'),
                'social_insurance_id'  => $request->input('social_insurance_id'),
                'employment_status'    => $employmentStatus,
                'is_active'            => $employmentStatus !== 'inactive',
                'probation_start_date' => $request->input('probation_start_date') ?: null,
                'probation_end_date'   => $request->input('probation_end_date') ?: null,
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

        // Send welcome email
        try {
            Mail::to($user->email)->send(new WelcomeUserMail($user, $plainPassword, route('login')));
        } catch (\Throwable $e) {
            logger()->error("Welcome email failed for user {$user->id}: " . $e->getMessage());
        }

        // Notify the recruitment position's assigned members that this
        // applicant has been onboarded as a new user.
        if (!empty($data['recruitment_applicant_id'])) {
            $applicant = RecruitmentApplicant::with('position.assignedUsers')->find($data['recruitment_applicant_id']);
            if ($applicant && $applicant->position) {
                $actor = auth()->user();
                foreach ($applicant->position->assignedUsers as $assignedUser) {
                    if ($assignedUser->id === $actor->id) continue;
                    NotificationHelper::send(
                        receivingUser: $assignedUser,
                        title: 'Ứng viên đã được onboard',
                        description: "{$actor->name} đã tạo hồ sơ nhân viên cho ứng viên \"{$applicant->name}\" ({$applicant->position->name}).",
                        url: route('users.show', $user),
                        incomingUser: $actor,
                    );
                }
            }
        }

        return redirect()->route('users.index')->with('success', 'User created');
    }

    public function edit(User $user)
    {
        $roles             = Role::all();
        $supervisorOptions = User::where('id', '!=', $user->id)->orderBy('name')->get(['id', 'name', 'position']);
        $user->load(['supervisors', 'salaryRecord', 'preferences', 'teams', 'recruitmentApplicant']);
        $spentBalance = $this->_spentLeaveBalance($user);
        return view('users.form', compact('user', 'roles', 'supervisorOptions', 'spentBalance'));
    }

    public function profile()
    {
        return $this->show(auth()->user());
    }

    public function importForm()
    {
        if (!auth()->user()->can('create all user')) abort(403);
        return view('users.import');
    }

    public function downloadImportTemplate()
    {
        if (!auth()->user()->can('create all user')) abort(403);

        $headers = ['name', 'email', 'password', 'position', 'grade', 'roles', 'team', 'team_leader'];
        $sample  = ['Nguyen Van A', 'vana@company.com', '', 'Developer', 'Junior', 'Staff', 'Dev Team', '0'];

        $csv = implode(',', $headers) . "\n" . implode(',', $sample) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_import_template.csv"',
        ]);
    }

    public function import(Request $request)
    {
        if (!auth()->user()->can('create all user')) abort(403);

        $request->validate(['csv_file' => 'required|file|mimes:csv,txt|max:2048']);

        $file      = $request->file('csv_file');
        $sendEmail = $request->boolean('send_email', true);
        $handle    = fopen($file->getRealPath(), 'r');

        // Read header row
        $headers = array_map('trim', fgetcsv($handle));
        $headers = array_map('strtolower', $headers);

        $created = 0;
        $skipped = 0;
        $failed  = 0;
        $errors  = [];
        $row     = 1;

        while (($cols = fgetcsv($handle)) !== false) {
            $row++;
            if (count(array_filter($cols)) === 0) continue; // skip blank rows

            $data = array_combine($headers, array_pad($cols, count($headers), ''));

            $name       = trim($data['name']        ?? '');
            $email      = trim($data['email']       ?? '');
            $password   = trim($data['password']    ?? '');
            $position   = trim($data['position']    ?? '') ?: null;
            $grade      = trim($data['grade']       ?? '') ?: null;
            $roles      = array_filter(array_map('trim', explode('|', $data['roles'] ?? '')));
            $teamName   = trim($data['team']        ?? '') ?: null;
            $isLeader   = in_array(trim($data['team_leader'] ?? ''), ['1', 'true', 'yes'], true);

            if (!$name || !$email) {
                $errors[] = "Dòng {$row}: thiếu name hoặc email.";
                $failed++;
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Dòng {$row}: email '{$email}' không hợp lệ.";
                $failed++;
                continue;
            }
            if (User::where('email', $email)->exists()) {
                $errors[] = "Dòng {$row}: email '{$email}' đã tồn tại — bỏ qua.";
                $skipped++;
                continue;
            }

            $plainPassword = $password ?: Str::random(10);

            try {
                $user = User::create([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => bcrypt($plainPassword),
                    'position' => $position,
                    'grade'    => $grade,
                ]);

                if (!empty($roles)) {
                    $validRoles = Role::whereIn('name', $roles)->pluck('name')->toArray();
                    $user->syncRoles($validRoles);
                }

                if ($teamName) {
                    $team = Team::firstOrCreate(['name' => $teamName]);
                    // Attach user to team (or update pivot if already a member)
                    $team->users()->syncWithoutDetaching([
                        $user->id => ['is_leader' => $isLeader],
                    ]);
                }

                if ($sendEmail) {
                    try {
                        Mail::to($user->email)->send(new WelcomeUserMail($user, $plainPassword, route('login')));
                    } catch (\Throwable $e) {
                        logger()->error("Welcome email failed for imported user {$user->id}: " . $e->getMessage());
                    }
                }

                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Dòng {$row} ({$email}): " . $e->getMessage();
                $failed++;
            }
        }

        fclose($handle);

        return redirect()->route('users.import.form')->with('import_results', compact('created', 'skipped', 'failed', 'errors'));
    }

    public function show(User $user)
    {
        $user->load(['roles', 'teams', 'supervisors', 'recruitmentApplicant']);
        $isOwnProfile    = auth()->id() === $user->id;
        $canViewSalary   = $isOwnProfile || auth()->user()->canAny(['view salary', 'edit all user']);
        $canViewPersonal = $isOwnProfile || auth()->user()->canAny(['view all user personal info', 'edit all user']);
        $spentBalance    = $this->_spentLeaveBalance($user);
        return view('users.show', compact('user', 'canViewSalary', 'canViewPersonal', 'spentBalance'));
    }

    /**
     * Total leave-balance hours deducted over time (sum of negative changes).
     */
    private function _spentLeaveBalance(User $user): float
    {
        return (float) abs($user->leaveBalanceLogs()->where('change_hours', '<', 0)->sum('change_hours'));
    }


    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'                 => 'required',
            'full_name'            => 'nullable|string|max:255',
            'email'                => 'required|email|unique:users,email,' . $user->id,
            'contact_email'        => 'nullable|email|max:255',
            'password'             => 'nullable|min:6|confirmed',
            'position'             => 'nullable|string|max:255',
            'grade'                => 'nullable|string|max:255',
            'birthday'             => 'nullable|date',
            'contract_expiry'      => 'nullable|date',
            'salary'               => 'nullable|integer|min:0',
            'salary_type'          => 'nullable|in:monthly,weekly,daily,hourly',
            'phone_number'         => 'nullable|string|max:30',
            'citizen_id'           => 'nullable|string|max:30',
            'home_address'         => 'nullable|string',
            'tax_code'             => 'nullable|string|max:20',
            'social_insurance_id'  => 'nullable|string|max:20',
            'employment_status'    => 'nullable|in:active,on_probation,inactive',
            'probation_start_date' => 'nullable|date',
            'probation_end_date'   => 'nullable|date',
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
            foreach (['contract_expiry','phone_number','citizen_id','home_address','tax_code','social_insurance_id','salary','salary_type','contact_email','employment_status','probation_start_date','probation_end_date'] as $f) {
                unset($data[$f]);
            }
        }

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
            $user->supervisors()->sync($request->input('supervisors', []));

            $employmentStatus = $request->input('employment_status', $user->employment_status ?? 'active');

            $user->update([
                'is_active'            => $employmentStatus !== 'inactive',
                'employment_status'    => $employmentStatus,
                'wfh_without_approval' => $request->boolean('wfh_without_approval'),
                'salary'               => $request->input('salary') ?: null,
                'salary_type'          => $request->input('salary_type') ?: null,
                'phone_number'         => $request->input('phone_number'),
                'citizen_id'           => $request->input('citizen_id'),
                'home_address'         => $request->input('home_address'),
                'tax_code'             => $request->input('tax_code'),
                'social_insurance_id'  => $request->input('social_insurance_id'),
                'contact_email'        => $request->input('contact_email'),
                'probation_start_date' => $request->input('probation_start_date') ?: null,
                'probation_end_date'   => $request->input('probation_end_date') ?: null,
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

        // Email notification preferences — only the user themselves can update these
        if (auth()->id() === $user->id && $request->has('_email_prefs')) {
            UserPreference::updateOrCreate(
                ['user_id' => $user->id],
                ['email_notifications' => [
                    'leave'        => $request->boolean('email_notify_leave'),
                    'ot'           => $request->boolean('email_notify_ot'),
                    'project'      => $request->boolean('email_notify_project'),
                    'announcement' => $request->boolean('email_notify_announcement'),
                ]]
            );
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

        $redirectTo = (auth()->id() === $user->id)
            ? route('users.profile')
            : route('users.edit', $user);
        return redirect($redirectTo)->with('success', 'Đã lưu thay đổi.');
    }

    public function updateColumnPreferences(Request $request)
    {
        $request->validate([
            'context' => 'required|string|in:task_list_column_preferences,project_task_column_preferences',
            'cols'    => 'required|array',
        ]);

        UserPreference::updateOrCreate(
            ['user_id' => auth()->id()],
            [$request->input('context') => $request->input('cols')]
        );

        return response()->json(['ok' => true]);
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

    public function requestInfo(User $user)
    {
        $otYearTotal = \App\Models\OvertimeRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereYear('start_at', now()->year)
            ->sum('hours');

        return response()->json([
            'leave_balance' => $user->leave_balance,
            'ot_year_total' => (float) $otYearTotal,
        ]);
    }

}