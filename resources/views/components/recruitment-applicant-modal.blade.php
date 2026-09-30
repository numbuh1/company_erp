@php
    $userOptions   = \App\Models\User::orderBy('name')->get(['id', 'name', 'position']);
    $skillOptions  = \App\Models\Skill::orderBy('category')->orderBy('name')->get();
    $tagOptions    = \App\Models\RecruitmentTag::where('type', 'applicant')->orderBy('name')->get();
    $statuses      = $position->allStatuses();
    $canViewSalary = auth()->user()->can('view recruitment salary');
    $canViewHrNote = auth()->user()->can('view recruitment hr note');
    $canDelete     = auth()->user()->can('edit recruitment');
    $skillsByCategory = $skillOptions
        ->groupBy('category')
        ->map(fn($g) => $g->values()->map(fn($s) => ['id' => $s->id, 'name' => $s->name]));
@endphp

<div id="recruitment-applicant-modal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    onclick="if(event.target===this) closeApplicantModal()">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 id="am-title" class="font-semibold text-lg text-gray-800 dark:text-gray-100">{{ __('Edit Applicant') }}</h3>
                <p id="am-subtitle" class="hidden text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ __('The CV has been saved. Review and update the applicant details below.') }}</p>
            </div>
            <button type="button" onclick="closeApplicantModal()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl leading-none">&times;</button>
        </div>

        <!-- Body -->
        <div class="overflow-y-auto flex-1 px-6 py-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Left: applicant fields -->
                <div class="space-y-4">

                    <!-- Status -->
                    <div>
                        <x-input-label for="am-status" :value="__('Status') . ' *'" />
                        <select id="am-status"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            @foreach($statuses as $s => $label)
                                <option value="{{ $s }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p id="am-error-status" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Name -->
                    <div>
                        <x-input-label for="am-name" :value="__('Applicant Name') . ' *'" />
                        <x-text-input id="am-name" type="text" class="mt-1 block w-full" />
                        <p id="am-error-name" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Contact -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="am-email" :value="__('Email')" />
                            <x-text-input id="am-email" type="email" class="mt-1 block w-full" />
                            <p id="am-error-email" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                        </div>
                        <div>
                            <x-input-label for="am-phone" :value="__('Phone')" />
                            <x-text-input id="am-phone" type="text" class="mt-1 block w-full" />
                            <p id="am-error-phone" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                        </div>
                    </div>

                    <!-- CV Upload -->
                    <div>
                        <x-input-label for="am-cv" :value="__('CV File')" />
                        <div id="am-current-cv" class="hidden mt-1 mb-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <span>📄 {{ __('Current CV:') }}</span>
                            <a id="am-current-cv-link" href="#" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('Download') }}</a>
                            <span id="am-cv-uploaded-at" class="text-xs text-gray-400"></span>
                        </div>
                        <input id="am-cv" type="file"
                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                   file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0
                                   file:text-sm file:bg-indigo-50 file:text-indigo-700
                                   hover:file:bg-indigo-100">
                        <p id="am-error-cv" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    @if($canViewHrNote)
                        <!-- HR Note (private) -->
                        <div>
                            <x-input-label for="am-hr-note" :value="__('HR Note (private)')" />
                            <textarea id="am-hr-note" rows="3"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm"
                                placeholder="{{ __('Internal HR note — only visible to users with permission…') }}"></textarea>
                            <p id="am-error-hr_note" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                        </div>
                    @endif

                    <!-- Notes -->
                    <div>
                        <x-input-label for="am-notes" :value="__('Notes')" />
                        <textarea id="am-notes" rows="4"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm"
                            placeholder="{{ __('Notes, feedback, interview comments…') }}"></textarea>
                        <p id="am-error-notes" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Evaluation -->
                    <div>
                        <x-input-label :value="__('Evaluation')" />
                        <div class="flex items-center gap-1 mt-2" id="am-star-rating">
                            @for($i = 1; $i <= 3; $i++)
                                <button type="button" data-star="{{ $i }}"
                                    class="star-btn text-3xl focus:outline-none transition-transform hover:scale-110"
                                    onclick="setRating({{ $i }})">
                                    ☆
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" id="evaluation-input" value="0">
                        <p id="am-error-evaluation" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Profile URL -->
                    <div>
                        <x-input-label for="am-profile-url" :value="__('Profile URL (LinkedIn, etc.)')" />
                        <x-text-input id="am-profile-url" type="url" class="mt-1 block w-full" placeholder="https://linkedin.com/in/…" />
                        <p id="am-error-profile_url" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Salary & Availability -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if($canViewSalary)
                            <div>
                                <x-input-label for="am-salary-expectation" :value="__('Salary Expectation')" />
                                <x-text-input id="am-salary-expectation" type="number" min="0" step="100" class="mt-1 block w-full" />
                                <p id="am-error-salary_expectation" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                            </div>
                        @endif
                        <div>
                            <x-input-label for="am-available-date" :value="__('Available From')" />
                            <x-text-input id="am-available-date" type="date" class="mt-1 block w-full" />
                            <p id="am-error-available_date" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                        </div>
                    </div>

                    <!-- Referer -->
                    <div>
                        <x-input-label for="am-referer-select" :value="__('Referred By')" />
                        <select id="am-referer-select"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('— None —') }}</option>
                            @foreach($userOptions as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}{{ $u->position ? ' · ' . $u->position : '' }}</option>
                            @endforeach
                        </select>
                        <p id="am-error-referer_user_id" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Skills -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <x-input-label :value="__('Skills')" />
                            @if($skillOptions->isNotEmpty())
                                <button type="button" onclick="openSkillModal()"
                                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ __('Edit Skills') }}
                                </button>
                            @endif
                        </div>
                        <div id="skills-summary"
                            class="flex flex-wrap gap-1.5 min-h-[2.5rem] p-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900">
                        </div>
                        <div id="skills-inputs"></div>
                        <p id="am-error-skills" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Tags -->
                    <div>
                        <x-input-label :value="__('Tags')" />
                        <select id="am-tags-select" multiple class="mt-1 block w-full">
                            @foreach($tagOptions as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                        <p id="am-error-tags" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <p id="am-error" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                </div>

                <!-- Right: CV preview -->
                <div>
                    <x-input-label :value="__('CV Preview')" />
                    <div id="am-cv-preview" class="mt-1"></div>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-700">
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">{{ __('Activity Log') }}</h4>
                <div id="am-activity-log" class="space-y-3 max-h-56 overflow-y-auto pr-1">
                    <p class="text-sm text-gray-400">{{ __('Loading…') }}</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
            @if($canDelete)
                <button type="button" id="am-delete-btn" onclick="deleteApplicantModal()"
                    class="px-4 py-2 text-sm text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                    {{ __('Delete') }}
                </button>
            @else
                <span></span>
            @endif
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeApplicantModal()"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    {{ __('Close') }}
                </button>
                <button type="button" id="am-submit-btn" onclick="submitApplicantModal()"
                    class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                    {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- "Duplicate applicant" pop-up: shown when the email/phone entered
     above matches another applicant record (any position). -->
<div id="recruitment-duplicate-modal"
    class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    onclick="if(event.target===this) cancelDuplicateModal()">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg max-h-[85vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">{{ __('Applicant already exists') }}</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                {{ __('This email or phone number already exists in the position(s) below. You can import the applicant\'s previous details (keeping the current CV), delete this applicant, or keep the details you just entered.') }}
            </p>
        </div>
        <div id="dup-list" class="overflow-y-auto flex-1 px-6 py-4 space-y-2"></div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
            <button type="button" onclick="deleteApplicantFromDuplicateModal()"
                class="px-4 py-2 text-sm text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                {{ __('Delete this applicant') }}
            </button>
            <div class="flex items-center gap-3">
                <button type="button" onclick="cancelDuplicateModal()"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    {{ __('Cancel') }}
                </button>
                <button type="button" onclick="dismissDuplicateModal()"
                    class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                    {{ __('Keep new info') }}
                </button>
            </div>
        </div>
    </div>
</div>

<x-skill-picker-modal />

<x-js-i18n :keys="['Delete', 'No CV file yet.', 'Word/Doc previews require a URL that is reachable from the Internet. If the preview does not appear, download the file to view it.',
    'No preview available for this file type.', 'The Word preview will be available after saving.', 'Uploaded: :date', 'No activity yet.',
    'Applicant added from CV', 'Edit Applicant', 'Could not load applicant details. Please try again.', 'Add New Applicant', 'Filter CVs',
    'Please enter the applicant name.', 'Saving…', 'Save', 'Duplicate applicant', 'No duplicate applicants found.', 'Keep new info',
    'Import old info', 'Delete this applicant', 'Are you sure you want to delete this applicant? This action cannot be undone.',
    'Could not delete the applicant. Please try again.', 'Could not add an applicant from this file. Please try again.',
    'Added :success/:total applicants. Some files could not be imported.', 'Could not add applicants from these files. Please try again.',
    'Are you sure you want to delete this applicant?', 'Create tag', 'Could not save. Please try again.',
    'Please enter a status name.', 'Could not add the status. It may already exist.']" />

@push('scripts')
<script>
    window.recruitmentSkillsByCategory = @js($skillsByCategory);
</script>
@endpush
