<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($recruitmentApplicant) ? 'Edit Applicant' : 'Add Applicant' }}
            <span class="text-base font-normal text-gray-500 dark:text-gray-400 ml-2">— {{ $recruitmentPosition->name }}</span>
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">

                <form method="POST"
                    action="{{ isset($recruitmentApplicant)
                        ? route('recruitment.applicants.update', [$recruitmentPosition, $recruitmentApplicant])
                        : route('recruitment.applicants.store', $recruitmentPosition) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @if(isset($recruitmentApplicant)) @method('PUT') @endif

                    @if($errors->any())
                        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded text-sm text-red-700 dark:text-red-300">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Status (always shown first for assigned users convenience) -->
                    <div class="mb-4">
                        <x-input-label for="status" value="Status *" />
                        <select id="status" name="status"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            @foreach(\App\Models\RecruitmentApplicant::$statuses as $s)
                                <option value="{{ $s }}"
                                    {{ old('status', $recruitmentApplicant->status ?? 'CV Screening') === $s ? 'selected' : '' }}>
                                    {{ $s }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    @if($canFullEdit)
                        <!-- Name -->
                        <div class="mb-4">
                            <x-input-label for="name" value="Applicant Name *" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                value="{{ old('name', $recruitmentApplicant->name ?? '') }}" required />
                            @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- CV Upload -->
                        <div class="mb-4">
                            <x-input-label for="cv" value="CV File" />
                            @if(isset($recruitmentApplicant) && $recruitmentApplicant->cv_path)
                                <div class="mt-1 mb-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span>📄 Current CV:</span>
                                    <a href="{{ route('recruitment.applicants.cv.download', [$recruitmentPosition, $recruitmentApplicant]) }}"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">Download</a>
                                </div>
                            @endif
                            <input id="cv" name="cv" type="file"
                                class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                       file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0
                                       file:text-sm file:bg-indigo-50 file:text-indigo-700
                                       hover:file:bg-indigo-100">
                            @error('cv')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <x-input-label for="notes" value="Notes" />
                            <textarea id="notes" name="notes" rows="4"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm"
                                placeholder="Interview notes, feedback, observations…">{{ old('notes', $recruitmentApplicant->notes ?? '') }}</textarea>
                            @error('notes')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Evaluation -->
                        <div class="mb-4">
                            <x-input-label value="Evaluation" />
                            <div class="flex items-center gap-1 mt-2" id="star-rating">
                                @for($i = 1; $i <= 3; $i++)
                                    <button type="button" data-star="{{ $i }}"
                                        class="star-btn text-3xl focus:outline-none transition-transform hover:scale-110"
                                        onclick="setRating({{ $i }})">
                                        ☆
                                    </button>
                                @endfor
                            </div>
                            <input type="hidden" name="evaluation" id="evaluation-input"
                                value="{{ old('evaluation', $recruitmentApplicant->evaluation ?? 0) }}">
                            @error('evaluation')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Contact -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label for="email" value="Email" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                    value="{{ old('email', $recruitmentApplicant->email ?? '') }}" />
                                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label for="phone" value="Phone" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                                    value="{{ old('phone', $recruitmentApplicant->phone ?? '') }}" />
                                @error('phone')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <!-- Profile URL -->
                        <div class="mb-4">
                            <x-input-label for="profile_url" value="Profile URL (LinkedIn, etc.)" />
                            <x-text-input id="profile_url" name="profile_url" type="url" class="mt-1 block w-full"
                                value="{{ old('profile_url', $recruitmentApplicant->profile_url ?? '') }}"
                                placeholder="https://linkedin.com/in/…" />
                            @error('profile_url')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Salary & Availability -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label for="salary_expectation" value="Salary Expectation" />
                                <x-text-input id="salary_expectation" name="salary_expectation" type="number" min="0" step="100"
                                    class="mt-1 block w-full"
                                    value="{{ old('salary_expectation', $recruitmentApplicant->salary_expectation ?? '') }}" />
                                @error('salary_expectation')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label for="available_date" value="Available From" />
                                <x-text-input id="available_date" name="available_date" type="date" class="mt-1 block w-full"
                                    value="{{ old('available_date', isset($recruitmentApplicant) ? $recruitmentApplicant->available_date?->format('Y-m-d') : '') }}" />
                                @error('available_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <!-- Referer -->
                        <div class="mb-4">
                            <x-input-label for="referer_user_id" value="Referred By" />
                            <select id="referer-select" name="referer_user_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                <option value="">— None —</option>
                                @foreach($userOptions as $u)
                                    <option value="{{ $u->id }}"
                                        {{ old('referer_user_id', $recruitmentApplicant->referer_user_id ?? '') == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }}{{ $u->position ? ' · ' . $u->position : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('referer_user_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Skills -->
                        <div class="mb-4">
                            <x-input-label value="Skills" />
                            <div class="mt-2 space-y-4 max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-900">
                                @foreach($skillOptions->groupBy('category') as $cat => $group)
                                    <div>
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">{{ ucfirst($cat) }}</p>
                                        <div class="space-y-1.5">
                                            @foreach($group as $skill)
                                                @php
                                                    $checked  = isset($recruitmentApplicant) && $recruitmentApplicant->skills->contains($skill->id);
                                                    $curLevel = $checked ? $recruitmentApplicant->skills->find($skill->id)->pivot->level : 'beginner';
                                                @endphp
                                                <div class="flex items-center gap-3">
                                                    <input type="checkbox" name="skills[]" value="{{ $skill->id }}"
                                                        id="skill_app_{{ $skill->id }}"
                                                        {{ $checked ? 'checked' : '' }}
                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                    <label for="skill_app_{{ $skill->id }}"
                                                        class="text-sm text-gray-700 dark:text-gray-300 flex-1 cursor-pointer">
                                                        {{ $skill->name }}
                                                    </label>
                                                    <select name="skill_levels[{{ $skill->id }}]"
                                                        class="text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 rounded shadow-sm py-0.5">
                                                        @foreach(\App\Models\Skill::$levels as $lvl)
                                                            <option value="{{ $lvl }}" {{ $curLevel === $lvl ? 'selected' : '' }}>
                                                                {{ ucfirst($lvl) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                @if($skillOptions->isEmpty())
                                    <p class="text-xs text-gray-400">No skills defined yet.</p>
                                @endif
                            </div>
                            @error('skills')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>


                        <!-- Applicant Tags -->
                        <div class="mb-4">
                            <x-input-label value="Tags" />
                            <select name="tags[]" id="applicant-tags-select" multiple class="mt-1 block w-full">
                                @foreach($tagOptions as $tag)
                                    <option value="{{ $tag->id }}"
                                        {{ (isset($recruitmentApplicant) && $recruitmentApplicant->tags->contains($tag->id)) ? 'selected' : '' }}>
                                        {{ $tag->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tags')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div class="flex items-center gap-3 mt-6">
                        <x-primary-button>{{ isset($recruitmentApplicant) ? 'Update' : 'Add Applicant' }}</x-primary-button>
                        <a href="{{ route('recruitment.show', $recruitmentPosition) }}">
                            <x-secondary-button type="button">Cancel</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        // Star rating
        function setRating(val) {
            document.getElementById('evaluation-input').value = val;
            document.querySelectorAll('.star-btn').forEach(function(btn) {
                btn.textContent = parseInt(btn.dataset.star) <= val ? '★' : '☆';
                btn.style.color  = parseInt(btn.dataset.star) <= val ? '#f59e0b' : '';
            });
        }

        // Init stars on page load
        document.addEventListener('DOMContentLoaded', function () {
            var current = parseInt(document.getElementById('evaluation-input').value) || 0;
            if (current > 0) setRating(current);

            new TomSelect('#referer-select', { maxOptions: null });

            new TomSelect('#applicant-tags-select', {
                create: true,
                createOnBlur: true,
                persist: false,
                maxOptions: null,
                render: {
                    option_create: function(data, escape) {
                        return '<div class="create">Create tag <strong>' + escape(data.input) + '</strong></div>';
                    }
                }
            });
        });
    </script>
    @endpush

</x-app-layout>
