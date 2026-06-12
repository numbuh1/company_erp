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

    /**
     * Build the JSON representation of an applicant used to populate the
     * edit modal (includes skills with pivot levels + tag ids, and strips
     * `hr_note` for users without the "view recruitment hr note" permission).
     */
    private function _applicantToJson(RecruitmentApplicant $applicant): array
    {
        $applicant->loadMissing(['skills', 'tags']);

        $data = $applicant->toArray();
        $data['skills'] = $applicant->skills->map(fn($s) => [
            'id'    => $s->id,
            'level' => $s->pivot->level,
        ])->values();
        $data['tags'] = $applicant->tags->pluck('id')->values();
        $data['available_date'] = $applicant->available_date?->format('Y-m-d');

        if (!auth()->user()->can('view recruitment hr note')) {
            unset($data['hr_note']);
        }

        return $data;
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

    public function show(Request $request, RecruitmentPosition $recruitmentPosition, RecruitmentApplicant $recruitmentApplicant)
    {
        $this->_authorizePosition($recruitmentPosition);

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'applicant' => $this->_applicantToJson($recruitmentApplicant),
                'cv_url'    => $recruitmentApplicant->cv_path ? Storage::disk('public')->url($recruitmentApplicant->cv_path) : null,
            ]);
        }

        $recruitmentApplicant->load(['skills', 'tags', 'referer', 'events.attendants']);
        $recruitmentPosition->load('assignedUsers');

        $canEdit = auth()->user()->can('edit recruitment');

        return view('recruitment.applicants.show', compact(
            'recruitmentPosition', 'recruitmentApplicant', 'canEdit'
        ));
    }

    public function store(Request $request, RecruitmentPosition $recruitmentPosition)
    {
        $canFullEdit = $this->_authorizePosition($recruitmentPosition);
        if (!$canFullEdit) abort(403);

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'cv'     => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp|max:10240',
            'notes'  => 'nullable|string|max:2000',
            'hr_note' => 'nullable|string|max:2000',
            'status' => 'nullable|string|max:100',
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

        if (!auth()->user()->can('view recruitment hr note')) {
            unset($data['hr_note']);
        }

        $data['recruitment_position_id'] = $recruitmentPosition->id;
        $data['status'] = $data['status'] ?? 'Lọc CV';

        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store(
                'recruitment/cv/' . $recruitmentPosition->id, 'public'
            );
        }

        $applicant = RecruitmentApplicant::create($data);
        $skillsData = [];
        foreach ($request->input('skills', []) as $skillId) {
            $skillsData[(int)$skillId] = [
                'level' => $request->input('skill_levels.' . $skillId, 'beginner'),
            ];
        }
        $applicant->skills()->sync($skillsData);

        $tagIds = RecruitmentTag::resolveIds($request->input('tags', []), 'applicant');
        $applicant->tags()->sync($tagIds);

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'applicant' => $this->_applicantToJson($applicant),
                'cv_url'    => $applicant->cv_path ? Storage::disk('public')->url($applicant->cv_path) : null,
            ]);
        }

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
            'cv'                 => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp|max:10240',
            'notes'              => 'nullable|string|max:2000',
            'hr_note'            => 'nullable|string|max:2000',
            'status'             => 'nullable|string|max:100',
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

        if (!auth()->user()->can('view recruitment hr note')) {
            unset($data['hr_note']);
        }

        $norm = fn($v) => ($v === '' || $v === null) ? null : $v;

        // -----------------------------------------------------------
        // Duplicate applicant detection (by email/phone). Skipped when
        // the client has already confirmed how to proceed (either
        // importing past data, or choosing to keep the new data).
        // -----------------------------------------------------------
        $importFromId       = $request->input('import_from_applicant_id');
        $skipDuplicateCheck = $request->boolean('skip_duplicate_check');
        $importSource       = null;

        if ($request->expectsJson() && !$importFromId && !$skipDuplicateCheck) {
            $newEmail = $norm($data['email'] ?? null);
            $newPhone = $norm($data['phone'] ?? null);

            $emailChanged = $newEmail !== $norm($recruitmentApplicant->email);
            $phoneChanged = $newPhone !== $norm($recruitmentApplicant->phone);
            $shouldCheck  = $emailChanged || $phoneChanged || !$recruitmentApplicant->duplicate_check_dismissed;

            if ($shouldCheck && ($newEmail || $newPhone)) {
                $duplicates = $this->_findDuplicateApplicants($recruitmentApplicant, $newEmail, $newPhone);

                if ($duplicates->isNotEmpty()) {
                    return response()->json([
                        'success'    => false,
                        'duplicate'  => true,
                        'duplicates' => $duplicates,
                    ]);
                }
            }
        }

        // -----------------------------------------------------------
        // Import past data from another applicant record. Overwrites
        // all "info" fields with the past record's data — except the
        // CV file (kept from the current record).
        // -----------------------------------------------------------
        if ($importFromId) {
            $importSource = RecruitmentApplicant::with(['skills', 'tags'])->find($importFromId);

            if ($importSource) {
                $data['name']               = $importSource->name;
                $data['notes']              = $importSource->notes;
                if (array_key_exists('hr_note', $data)) {
                    $data['hr_note'] = $importSource->hr_note;
                }
                $data['evaluation']         = $importSource->evaluation;
                $data['email']              = $importSource->email;
                $data['phone']              = $importSource->phone;
                $data['profile_url']        = $importSource->profile_url;
                $data['salary_expectation'] = $importSource->salary_expectation;
                $data['available_date']     = $importSource->available_date;
                $data['referer_user_id']    = $importSource->referer_user_id;
            }
        }

        if ($request->hasFile('cv')) {
            if ($recruitmentApplicant->cv_path) {
                Storage::disk('public')->delete($recruitmentApplicant->cv_path);
            }
            $data['cv_path'] = $request->file('cv')->store(
                'recruitment/cv/' . $recruitmentPosition->id, 'public'
            );
        }

        $contactChanged = $norm($data['email'] ?? null) !== $norm($recruitmentApplicant->email)
            || $norm($data['phone'] ?? null) !== $norm($recruitmentApplicant->phone);

        $recruitmentApplicant->update($data);

        // Persist the "don't show the duplicate pop-up again" choice. If
        // the user picked import/skip, remember that. If email/phone just
        // changed (and weren't part of an import/skip), re-arm the check
        // so a *new* duplicate match on the new value(s) is still caught.
        if ($importFromId || $skipDuplicateCheck) {
            $recruitmentApplicant->duplicate_check_dismissed = true;
            $recruitmentApplicant->save();
        } elseif ($contactChanged) {
            $recruitmentApplicant->duplicate_check_dismissed = false;
            $recruitmentApplicant->save();
        }

        if ($importSource) {
            $skillsData = $importSource->skills->mapWithKeys(fn($s) => [
                $s->id => ['level' => $s->pivot->level],
            ])->toArray();
            $recruitmentApplicant->skills()->sync($skillsData);
            $recruitmentApplicant->tags()->sync($importSource->tags->pluck('id')->toArray());
        } else {
            $skillsData = [];
            foreach ($request->input('skills', []) as $skillId) {
                $skillsData[(int)$skillId] = [
                    'level' => $request->input('skill_levels.' . $skillId, 'beginner'),
                ];
            }
            $recruitmentApplicant->skills()->sync($skillsData);

            $tagIds = RecruitmentTag::resolveIds($request->input('tags', []), 'applicant');
            $recruitmentApplicant->tags()->sync($tagIds);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'applicant' => $this->_applicantToJson($recruitmentApplicant),
                'cv_url'    => $recruitmentApplicant->cv_path ? Storage::disk('public')->url($recruitmentApplicant->cv_path) : null,
            ]);
        }

        return redirect()->route('recruitment.show', $recruitmentPosition)->with('success', 'Applicant updated.');
    }

    /**
     * Find other (non-trashed) applicants — across any recruitment
     * position — whose email or phone matches the given values,
     * excluding $current itself. Returns data for the "duplicate
     * applicant" pop-up: name, status, position name/link, etc.
     */
    private function _findDuplicateApplicants(RecruitmentApplicant $current, ?string $email, ?string $phone)
    {
        return RecruitmentApplicant::with('position')
            ->where('id', '!=', $current->id)
            ->where(function ($q) use ($email, $phone) {
                if ($email) $q->orWhere('email', $email);
                if ($phone) $q->orWhere('phone', $phone);
            })
            ->get()
            ->map(fn($d) => [
                'id'            => $d->id,
                'name'          => $d->name,
                'status'        => $d->status,
                'status_label'  => RecruitmentApplicant::statusLabel($d->status),
                'position_id'   => $d->recruitment_position_id,
                'position_name' => $d->position?->name,
                'url'           => route('recruitment.applicants.show', [$d->recruitment_position_id, $d->id]),
            ])
            ->values();
    }

    public function destroy(Request $request, RecruitmentPosition $recruitmentPosition, RecruitmentApplicant $recruitmentApplicant)
    {
        $canFullEdit = $this->_authorizePosition($recruitmentPosition);
        if (!$canFullEdit) abort(403);

        $recruitmentApplicant->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

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

    public function updateStatus(Request $request, RecruitmentPosition $recruitmentPosition, RecruitmentApplicant $recruitmentApplicant)
    {
        $canFullEdit = $this->_authorizePosition($recruitmentPosition);
        if (!$canFullEdit) abort(403);

        $request->validate([
            'status' => 'required|string|max:100',
        ]);

        $recruitmentApplicant->update(['status' => $request->status]);

        return response()->json(['ok' => true]);
    }

    public function addStatus(Request $request, RecruitmentPosition $recruitmentPosition)
    {
        $canFullEdit = $this->_authorizePosition($recruitmentPosition);
        if (!$canFullEdit) abort(403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $added = $recruitmentPosition->addCustomStatus($data['name']);

        if (!$added) {
            return response()->json(['ok' => false, 'message' => 'Status already exists.'], 422);
        }

        return response()->json([
            'ok'     => true,
            'status' => $data['name'],
            'config' => RecruitmentApplicant::kanbanColConfig($data['name']),
            'label'  => RecruitmentApplicant::statusLabel($data['name']),
        ]);
    }

    public function reorderStatuses(Request $request, RecruitmentPosition $recruitmentPosition)
    {
        $canFullEdit = $this->_authorizePosition($recruitmentPosition);
        if (!$canFullEdit) abort(403);

        $data = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'string|max:100',
        ]);

        $recruitmentPosition->setStatusOrder($data['order']);

        return response()->json(['ok' => true]);
    }

}
