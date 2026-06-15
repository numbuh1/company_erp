<?php
namespace App\Http\Controllers;

use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentPosition;
use App\Models\User;
use App\Models\RecruitmentTag;
use App\Models\Skill;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecruitmentController extends Controller
{
    private function _scopedQuery()
    {
        $user  = auth()->user();
        $query = RecruitmentPosition::with('assignedUsers', 'team')
            ->withCount([
                'applicants',
                'applicants as potential_count'          => fn($q) => $q->where('status', 'Tiềm năng'),
                'applicants as cv_screening_count'       => fn($q) => $q->where('status', 'Lọc CV'),
                'applicants as interview_count'          => fn($q) => $q->where('status', 'Duyệt phỏng vấn'),
                'applicants as offer_consider_count'     => fn($q) => $q->where('status', 'Cân nhắc offer'),
                'applicants as offered_count'            => fn($q) => $q->where('status', 'Đã gửi offer'),
                'applicants as hired_count'              => fn($q) => $q->where('status', 'Đã tuyển'),
                'applicants as rejected_count'           => fn($q) => $q->where('status', 'Không phù hợp'),
            ]);

        if ($user->can('edit recruitment')) return $query;

        return $query->whereHas('assignedUsers', fn($q) => $q->where('users.id', $user->id));
    }


    public function index(Request $request)
    {
        if (!auth()->user()->can('module recruitment')) abort(403);

        $positions = $this->_scopedQuery($request)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('recruitment.index', compact('positions'));
    }

    public function create()
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);

        $userOptions  = User::orderBy('name')->get(['id', 'name', 'position']);
        $teamOptions  = Team::orderBy('name')->get(['id', 'name']);
        $skillOptions = Skill::orderBy('category')->orderBy('name')->get();
        $tagOptions   = RecruitmentTag::where('type', 'position')->orderBy('name')->get();

        // Past positions that have at least one "Tiềm năng" applicant —
        // offered as the "Import potential CV" source list, newest first.
        $pastPositions = RecruitmentPosition::query()
            ->whereHas('applicants', fn($q) => $q->where('status', 'Tiềm năng'))
            ->orderByRaw('COALESCE(search_start_date, DATE(created_at)) DESC')
            ->get(['id', 'name', 'search_start_date', 'created_at']);

        return view('recruitment.form', compact('userOptions', 'teamOptions', 'skillOptions', 'tagOptions', 'pastPositions'));
    }


    public function store(Request $request)
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);

        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'search_start_date' => 'nullable|date',
            'search_end_date'   => 'nullable|date|after_or_equal:search_start_date',
            'description'       => 'nullable|string',
            'file'              => 'nullable|max:10240',
            'assigned_users'    => 'nullable|array',
            'assigned_users.*'  => 'exists:users,id',
            'team_id'           => 'nullable|exists:teams,id',
            'salary_min'        => 'nullable|numeric|min:0',
            'salary_max'        => 'nullable|numeric|min:0|gte:salary_min',
            'skills'            => 'nullable|array',
            'skills.*'          => 'exists:skills,id',
            'tags'              => 'nullable|array',
            'status'            => 'nullable|in:upcoming,in_progress,done',
            'import_potential_from'   => 'nullable|array',
            'import_potential_from.*' => 'exists:recruitment_positions,id',
        ]);

        $importFromIds = $data['import_potential_from'] ?? [];
        unset($data['import_potential_from']);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('recruitment/jd', 'public');
        }

        $position = RecruitmentPosition::create($data);
        $position->assignedUsers()->sync($request->input('assigned_users', []));

        $skillsData = [];
        foreach ($request->input('skills', []) as $skillId) {
            $skillsData[(int)$skillId] = [
                'level' => $request->input('skill_levels.' . $skillId, 'beginner'),
            ];
        }
        $position->skills()->sync($skillsData);


        $tagIds = RecruitmentTag::resolveIds($request->input('tags', []), 'position');
        $position->tags()->sync($tagIds);

        if (!empty($importFromIds)) {
            $this->_importPotentialApplicants($position, $importFromIds);
        }

        return redirect()->route('recruitment.show', $position)->with('success', 'Position created.');
    }

    /**
     * Import all "Tiềm năng" applicants from the given source positions
     * into the newly created $position, as "Lọc CV" applicants.
     * If multiple imported applicants share the same email or phone,
     * only the most recently updated one is imported.
     */
    private function _importPotentialApplicants(RecruitmentPosition $position, array $sourcePositionIds): void
    {
        $applicants = RecruitmentApplicant::with(['skills', 'tags'])
            ->whereIn('recruitment_position_id', $sourcePositionIds)
            ->where('status', 'Tiềm năng')
            ->orderByDesc('updated_at')
            ->get();

        // De-duplicate by email/phone, keeping the most recently updated
        // applicant for each email/phone (list is already ordered newest first).
        $seenKeys = [];
        $toImport = [];

        foreach ($applicants as $applicant) {
            $keys = [];
            if ($applicant->email) $keys[] = 'email:' . mb_strtolower(trim($applicant->email));
            if ($applicant->phone) $keys[] = 'phone:' . preg_replace('/\D+/', '', $applicant->phone);

            $isDuplicate = false;
            foreach ($keys as $key) {
                if (isset($seenKeys[$key])) { $isDuplicate = true; break; }
            }
            if ($isDuplicate) continue;

            foreach ($keys as $key) { $seenKeys[$key] = true; }
            $toImport[] = $applicant;
        }

        foreach ($toImport as $applicant) {
            $newCvPath = null;
            if ($applicant->cv_path && Storage::disk('public')->exists($applicant->cv_path)) {
                $newCvPath = 'recruitment/cv/' . $position->id . '/' . basename($applicant->cv_path);
                Storage::disk('public')->copy($applicant->cv_path, $newCvPath);
            }

            $newApplicant = RecruitmentApplicant::create([
                'recruitment_position_id' => $position->id,
                'name'               => $applicant->name,
                'cv_path'            => $newCvPath,
                'notes'              => $applicant->notes,
                'hr_note'            => $applicant->hr_note,
                'status'             => 'Lọc CV',
                'evaluation'         => $applicant->evaluation,
                'email'              => $applicant->email,
                'phone'              => $applicant->phone,
                'profile_url'        => $applicant->profile_url,
                'salary_expectation' => $applicant->salary_expectation,
                'available_date'     => $applicant->available_date,
                'referer_user_id'    => $applicant->referer_user_id,
                'duplicate_check_dismissed' => true,
            ]);

            $skillsData = $applicant->skills->mapWithKeys(fn($s) => [
                $s->id => ['level' => $s->pivot->level],
            ])->toArray();
            $newApplicant->skills()->sync($skillsData);
            $newApplicant->tags()->sync($applicant->tags->pluck('id')->toArray());
        }
    }

    public function show(RecruitmentPosition $recruitmentPosition)
    {
        $user = auth()->user();
        if (!$user->can('module recruitment')) abort(403);

        if (!$user->can('edit recruitment')) {
            $isAssigned = $recruitmentPosition->assignedUsers()->where('users.id', $user->id)->exists();
            if (!$isAssigned) abort(403);
        }

        $recruitmentPosition->load([
            'assignedUsers', 'team', 'skills', 'tags',
            'applicants.tags', 'applicants.skills',
            'applicants.events.attendants',
        ]);
        $canEdit = $user->can('edit recruitment');

        return view('recruitment.show', compact('recruitmentPosition', 'canEdit'));
    }

    public function edit(RecruitmentPosition $recruitmentPosition)
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);

        $userOptions  = User::orderBy('name')->get(['id', 'name', 'position']);
        $teamOptions  = Team::orderBy('name')->get(['id', 'name']);
        $skillOptions = Skill::orderBy('category')->orderBy('name')->get();
        $tagOptions   = RecruitmentTag::where('type', 'position')->orderBy('name')->get();

        $recruitmentPosition->load('assignedUsers', 'skills', 'tags');
        return view('recruitment.form', compact('recruitmentPosition', 'userOptions', 'teamOptions', 'skillOptions', 'tagOptions'));
    }

    public function update(Request $request, RecruitmentPosition $recruitmentPosition)
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);

        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'search_start_date' => 'nullable|date',
            'search_end_date'   => 'nullable|date|after_or_equal:search_start_date',
            'description'       => 'nullable|string',
            'file'              => 'nullable|max:10240',
            'assigned_users'    => 'nullable|array',
            'assigned_users.*'  => 'exists:users,id',
            'team_id'           => 'nullable|exists:teams,id',
            'salary_min'        => 'nullable|numeric|min:0',
            'salary_max'        => 'nullable|numeric|min:0|gte:salary_min',
            'skills'            => 'nullable|array',
            'skills.*'          => 'exists:skills,id',
            'tags'              => 'nullable|array',
            'status'            => 'nullable|in:upcoming,in_progress,done',
        ]);

        if ($request->hasFile('file')) {
            if ($recruitmentPosition->file_path) {
                Storage::disk('public')->delete($recruitmentPosition->file_path);
            }
            $data['file_path'] = $request->file('file')->store('recruitment/jd', 'public');
        }

        $recruitmentPosition->update($data);
        $recruitmentPosition->assignedUsers()->sync($request->input('assigned_users', []));

        $skillsData = [];
        foreach ($request->input('skills', []) as $skillId) {
            $skillsData[(int)$skillId] = [
                'level' => $request->input('skill_levels.' . $skillId, 'beginner'),
            ];
        }
        $recruitmentPosition->skills()->sync($skillsData);

        $tagIds = RecruitmentTag::resolveIds($request->input('tags', []), 'position');
        $recruitmentPosition->tags()->sync($tagIds);

        return redirect()->route('recruitment.show', $recruitmentPosition)->with('success', 'Position updated.');
    }

    public function destroy(RecruitmentPosition $recruitmentPosition)
    {
        if (!auth()->user()->can('edit recruitment')) abort(403);

        $recruitmentPosition->delete();
        return redirect()->route('recruitment.index')->with('success', 'Position deleted.');
    }

    public function downloadJd(RecruitmentPosition $recruitmentPosition)
    {
        $user = auth()->user();
        if (!$user->can('module recruitment')) abort(403);

        if (!$user->can('edit recruitment')) {
            $isAssigned = $recruitmentPosition->assignedUsers()->where('users.id', $user->id)->exists();
            if (!$isAssigned) abort(403);
        }

        if (!$recruitmentPosition->file_path || !Storage::disk('public')->exists($recruitmentPosition->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($recruitmentPosition->file_path,
            $recruitmentPosition->name . '_JD.' . pathinfo($recruitmentPosition->file_path, PATHINFO_EXTENSION));
    }
}
