<?php
namespace App\Http\Controllers;

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
                'applicants as cv_screening_count'      => fn($q) => $q->where('status', 'CV Screening'),
                'applicants as interview_count'          => fn($q) => $q->where('status', 'Approved for Interview'),
                'applicants as approved_count'           => fn($q) => $q->where('status', 'Approved'),
                'applicants as rejected_count'           => fn($q) => $q->where('status', 'Rejected'),
                'applicants as offered_count'            => fn($q) => $q->where('status', 'Offered'),
                'applicants as hired_count'              => fn($q) => $q->where('status', 'Hired'),
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

        return view('recruitment.form', compact('userOptions', 'teamOptions', 'skillOptions', 'tagOptions'));
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
        ]);

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

        return redirect()->route('recruitment.show', $position)->with('success', 'Position created.');
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
