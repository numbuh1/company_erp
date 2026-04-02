<?php
namespace App\Http\Controllers;

use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentPosition;
use App\Models\RecruitmentTag;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecruitmentApplicantController extends Controller
{
    private function _authorizePosition(RecruitmentPosition $position): bool
    {
        $user = auth()->user();
        if (!$user->can('module recruitment')) abort(403);

        if ($user->can('edit recruitment')) return true;

        $isAssigned = $position->assignedUsers()->where('users.id', $user->id)->exists();
        if (!$isAssigned) abort(403);

        return false; // is assigned but cannot fully edit
    }

    public function create(RecruitmentPosition $recruitmentPosition)
    {
        $canFullEdit = $this->_authorizePosition($recruitmentPosition);
        if (!$canFullEdit) abort(403);

        $userOptions  = User::orderBy('name')->get(['id', 'name', 'position']);
        $skillOptions = Skill::orderBy('category')->orderBy('name')->get();
        $tagOptions   = RecruitmentTag::where('type', 'applicant')->orderBy('name')->get();

        return view('recruitment.applicants.form', compact(
            'recruitmentPosition', 'canFullEdit', 'userOptions', 'skillOptions', 'tagOptions'
        ));
    }

    public function store(Request $request, RecruitmentPosition $recruitmentPosition)
    {
        $canFullEdit = $this->_authorizePosition($recruitmentPosition);
        if (!$canFullEdit) abort(403);

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'cv'     => 'nullable|file|max:10240',
            'notes'  => 'nullable|string|max:2000',
            'status' => 'nullable|in:' . implode(',', RecruitmentApplicant::$statuses),
            'evaluation'       => 'nullable|integer|min:0|max:3',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'profile_url'      => 'nullable|url|max:500',
            'salary_expectation' => 'nullable|numeric|min:0',
            'available_date'   => 'nullable|date',
            'referer_user_id'  => 'nullable|exists:users,id',
            'skills'           => 'nullable|array',
            'skills.*'         => 'exists:skills,id',
            'tags'             => 'nullable|array',
        ]);

        $data['recruitment_position_id'] = $recruitmentPosition->id;
        $data['status'] = $data['status'] ?? 'CV Screening';

        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store(
                'recruitment/cv/' . $recruitmentPosition->id, 'public'
            );
        }

        RecruitmentApplicant::create($data);
        $skillsData = [];
        foreach ($request->input('skills', []) as $skillId) {
            $skillsData[(int)$skillId] = [
                'level' => $request->input('skill_levels.' . $skillId, 'beginner'),
            ];
        }
        $applicant->skills()->sync($skillsData);

        $tagIds = RecruitmentTag::resolveIds($request->input('tags', []), 'applicant');
        $applicant->tags()->sync($tagIds);
        return redirect()->route('recruitment.show', $recruitmentPosition)->with('success', 'Applicant added.');
    }

    public function edit(RecruitmentPosition $recruitmentPosition, RecruitmentApplicant $recruitmentApplicant)
    {
        $this->_authorizePosition($recruitmentPosition); // ensures access (403 if not assigned/permitted)
        $canFullEdit = true;

        $userOptions  = User::orderBy('name')->get(['id', 'name', 'position']);
        $skillOptions = Skill::orderBy('category')->orderBy('name')->get();
        $tagOptions   = RecruitmentTag::where('type', 'applicant')->orderBy('name')->get();

        $recruitmentApplicant->load('skills', 'tags', 'referer');

        return view('recruitment.applicants.form', compact(
            'recruitmentPosition', 'recruitmentApplicant',
            'canFullEdit', 'userOptions', 'skillOptions', 'tagOptions'
        ));
    }

    public function update(Request $request, RecruitmentPosition $recruitmentPosition, RecruitmentApplicant $recruitmentApplicant)
    {
        $this->_authorizePosition($recruitmentPosition);

        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'cv'                 => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'notes'              => 'nullable|string|max:2000',
            'status'             => 'required|in:' . implode(',', RecruitmentApplicant::$statuses),
            'evaluation'         => 'nullable|integer|min:0|max:3',
            'email'              => 'nullable|email|max:255',
            'phone'              => 'nullable|string|max:50',
            'profile_url'        => 'nullable|url|max:500',
            'salary_expectation' => 'nullable|numeric|min:0',
            'available_date'     => 'nullable|date',
            'referer_user_id'    => 'nullable|exists:users,id',
            'skills'             => 'nullable|array',
            'skills.*'           => 'exists:skills,id',
            'tags'               => 'nullable|array',
        ]);

        if ($request->hasFile('cv')) {
            if ($recruitmentApplicant->cv_path) {
                Storage::disk('public')->delete($recruitmentApplicant->cv_path);
            }
            $data['cv_path'] = $request->file('cv')->store(
                'recruitment/cv/' . $recruitmentPosition->id, 'public'
            );
        }

        $recruitmentApplicant->update($data);
        $skillsData = [];
        foreach ($request->input('skills', []) as $skillId) {
            $skillsData[(int)$skillId] = [
                'level' => $request->input('skill_levels.' . $skillId, 'beginner'),
            ];
        }
        $recruitmentApplicant->skills()->sync($skillsData);

        $tagIds = RecruitmentTag::resolveIds($request->input('tags', []), 'applicant');
        $recruitmentApplicant->tags()->sync($tagIds);

        return redirect()->route('recruitment.show', $recruitmentPosition)->with('success', 'Applicant updated.');
    }

    public function destroy(RecruitmentPosition $recruitmentPosition, RecruitmentApplicant $recruitmentApplicant)
    {
        $canFullEdit = $this->_authorizePosition($recruitmentPosition);
        if (!$canFullEdit) abort(403);

        $recruitmentApplicant->delete();
        return redirect()->route('recruitment.show', $recruitmentPosition)->with('success', 'Applicant removed.');
    }

    public function downloadCv(RecruitmentPosition $recruitmentPosition, RecruitmentApplicant $recruitmentApplicant)
    {
        $this->_authorizePosition($recruitmentPosition);

        if (!$recruitmentApplicant->cv_path || !Storage::disk('public')->exists($recruitmentApplicant->cv_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($recruitmentApplicant->cv_path,
            $recruitmentApplicant->name . '_CV.' . pathinfo($recruitmentApplicant->cv_path, PATHINFO_EXTENSION));
    }
}
