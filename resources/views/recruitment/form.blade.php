<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($recruitmentPosition) ? 'Edit Position' : 'New Position' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">

                <form method="POST"
                    action="{{ isset($recruitmentPosition) ? route('recruitment.update', $recruitmentPosition) : route('recruitment.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    @if(isset($recruitmentPosition)) @method('PUT') @endif

                    @if($errors->any())
                        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded text-sm text-red-700 dark:text-red-300">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Position Name -->
                    <div class="mb-4">
                        <x-input-label for="name" value="Position Name *" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                            value="{{ old('name', $recruitmentPosition->name ?? '') }}" required />
                        @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            @foreach(\App\Models\RecruitmentPosition::$statuses as $s)
                                <option value="{{ $s }}" {{ old('status', $recruitmentPosition->status ?? 'upcoming') === $s ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Team -->
                    <div class="mb-4">
                        <x-input-label for="team_id" value="Team" />
                        <select id="team-select" name="team_id"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">— None —</option>
                            @foreach($teamOptions as $team)
                                <option value="{{ $team->id }}"
                                    {{ old('team_id', $recruitmentPosition->team_id ?? '') == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('team_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Search Period -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <x-input-label for="search_start_date" value="Search Start Date" />
                            <x-text-input id="search_start_date" name="search_start_date" type="date" class="mt-1 block w-full"
                                value="{{ old('search_start_date', isset($recruitmentPosition) ? $recruitmentPosition->search_start_date?->format('Y-m-d') : '') }}" />
                            @error('search_start_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <x-input-label for="search_end_date" value="Search End Date" />
                            <x-text-input id="search_end_date" name="search_end_date" type="date" class="mt-1 block w-full"
                                value="{{ old('search_end_date', isset($recruitmentPosition) ? $recruitmentPosition->search_end_date?->format('Y-m-d') : '') }}" />
                            @error('search_end_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Salary Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <x-input-label for="salary_min" value="Salary Min" />
                            <x-text-input id="salary_min" name="salary_min" type="number" min="0" step="100" class="mt-1 block w-full"
                                value="{{ old('salary_min', $recruitmentPosition->salary_min ?? '') }}" />
                            @error('salary_min')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <x-input-label for="salary_max" value="Salary Max" />
                            <x-text-input id="salary_max" name="salary_max" type="number" min="0" step="100" class="mt-1 block w-full"
                                value="{{ old('salary_max', $recruitmentPosition->salary_max ?? '') }}" />
                            @error('salary_max')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <x-input-label for="description" value="Job Description (text)" />
                        <textarea id="description" name="description" rows="6"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm"
                            placeholder="Describe the role, requirements, responsibilities…">{{ old('description', $recruitmentPosition->description ?? '') }}</textarea>
                        @error('description')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- File upload -->
                    <div class="mb-4">
                        <x-input-label for="file" value="Job Description File (PDF/DOC, optional)" />
                        @if(isset($recruitmentPosition) && $recruitmentPosition->file_path)
                            <div class="mt-1 mb-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <span>📄 Current file:</span>
                                <a href="{{ route('recruitment.jd.download', $recruitmentPosition) }}"
                                    class="text-indigo-600 dark:text-indigo-400 hover:underline">Download</a>
                            </div>
                        @endif
                        <input id="file" name="file" type="file"
                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                   file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0
                                   file:text-sm file:bg-indigo-50 file:text-indigo-700
                                   hover:file:bg-indigo-100">
                        @error('file')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Assigned Users -->
                    <div class="mb-6">
                        <x-input-label value="Assigned Users" />
                        <p class="text-xs text-gray-400 mb-2">These users can view this position and update applicant statuses.</p>
                        <select name="assigned_users[]" id="assigned-users-select" data-multi-select
                                data-placeholder="Select users…" class="mt-1 block w-full" multiple>
                            @foreach($userOptions as $opt)
                                <option value="{{ $opt->id }}"
                                    {{ (isset($recruitmentPosition) && $recruitmentPosition->assignedUsers->contains($opt->id)) ? 'selected' : '' }}>
                                    {{ $opt->name }}{{ $opt->position ? ' · ' . $opt->position : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_users')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- Skills -->
                    <div class="mb-4">
                        <x-input-label value="Required Skills" />
                        <div class="mt-2 space-y-4 max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-white dark:bg-gray-900">
                            @foreach($skillOptions->groupBy('category') as $cat => $group)
                                <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">{{ ucfirst($cat) }}</p>
                                    <div class="space-y-1.5">
                                        @foreach($group as $skill)
                                            @php
                                                $checked  = isset($recruitmentPosition) && $recruitmentPosition->skills->contains($skill->id);
                                                $curLevel = $checked ? $recruitmentPosition->skills->find($skill->id)->pivot->level : 'beginner';
                                            @endphp
                                            <div class="flex items-center gap-3">
                                                <input type="checkbox" name="skills[]" value="{{ $skill->id }}"
                                                    id="skill_pos_{{ $skill->id }}"
                                                    {{ $checked ? 'checked' : '' }}
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                <label for="skill_pos_{{ $skill->id }}"
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


                    <!-- Position Tags -->
                    <div class="mb-6">
                        <x-input-label value="Tags" />
                        <p class="text-xs text-gray-400 mb-1">Select existing or type to create a new tag.</p>
                        <select name="tags[]" id="position-tags-select" multiple class="mt-1 block w-full">
                            @foreach($tagOptions as $tag)
                                <option value="{{ $tag->id }}"
                                    {{ (isset($recruitmentPosition) && $recruitmentPosition->tags->contains($tag->id)) ? 'selected' : '' }}>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('tags')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>{{ isset($recruitmentPosition) ? 'Update' : 'Create' }}</x-primary-button>
                        <a href="{{ isset($recruitmentPosition) ? route('recruitment.show', $recruitmentPosition) : route('recruitment.index') }}">
                            <x-secondary-button type="button">Cancel</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new TomSelect('#team-select', { maxOptions: null });

            new TomSelect('#position-tags-select', {
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
