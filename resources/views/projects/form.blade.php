<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($project) ? 'Edit Project' : 'Create Project' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form method="POST" action="{{ isset($project) ? route('projects.update', $project) : route('projects.store') }}">
                @csrf
                @if(isset($project)) @method('PUT') @endif

                {{-- Main Info --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Project Details</h3>

                    <div class="mb-4">
                        <x-input-label value="Name" />
                        <x-text-input name="name" class="mt-1 block w-full"
                            value="{{ old('name', $project->name ?? '') }}" required />
                    </div>

                    <div class="mb-4">
                        <x-input-label value="Status" />
                        <select name="status"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach(['Not Started', 'In Progress', 'Done'] as $s)
                                <option value="{{ $s }}" {{ old('status', $project->status ?? 'Not Started') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>                    

                    <div class="mb-4">
                        <x-input-label value="Description" />
                        <textarea name="description" rows="4"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $project->description ?? '') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label value="Start Date" />
                            <x-text-input type="date" name="start_date" class="mt-1 block w-full"
                                value="{{ old('start_date', isset($project) ? $project->start_date?->format('Y-m-d') : '') }}" />
                        </div>
                        <div>
                            <x-input-label value="Expected End Date" />
                            <x-text-input type="date" name="expected_end_date" class="mt-1 block w-full"
                                value="{{ old('expected_end_date', isset($project) ? $project->expected_end_date?->format('Y-m-d') : '') }}" />
                        </div>
                        <div>
                            <x-input-label value="Actual End Date" />
                            <x-text-input type="date" name="actual_end_date" class="mt-1 block w-full"
                                value="{{ old('actual_end_date', isset($project) ? $project->actual_end_date?->format('Y-m-d') : '') }}" />
                        </div>
                    </div>
                </div>

                {{-- Team & Member Assignment --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Assignment</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        {{-- Teams --}}
                        <div>
                            <x-input-label value="Assigned Teams" />
                            <input type="text" placeholder="Search teams…" id="search-teams"
                                class="mt-1 mb-2 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm p-2">
                            <div class="border border-gray-300 dark:border-gray-600 rounded-md p-3 space-y-2 max-h-60 overflow-y-auto">
                                @foreach($teams as $team)
                                    <label class="flex items-center gap-2 team-item text-sm text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" name="teams[]" value="{{ $team->id }}"
                                            {{ collect(old('teams', isset($project) ? $project->teams->pluck('id')->toArray() : []))->contains($team->id) ? 'checked' : '' }}>
                                        {{ $team->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Members --}}
                        <div>
                            <x-input-label value="Assigned Members" />
                            <input type="text" placeholder="Search members…" id="search-members"
                                class="mt-1 mb-2 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm p-2">
                            <div class="border border-gray-300 dark:border-gray-600 rounded-md p-3 space-y-2 max-h-60 overflow-y-auto">
                                @foreach($users as $u)
                                    <label class="flex items-center gap-2 member-item text-sm text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" name="members[]" value="{{ $u->id }}"
                                            {{ collect(old('members', isset($project) ? $project->users->pluck('id')->toArray() : []))->contains($u->id) ? 'checked' : '' }}>
                                        {{ $u->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end gap-2">
                    <a href="{{ isset($project) ? route('projects.show', $project) : route('projects.index') }}">
                        <x-secondary-button type="button">Cancel</x-secondary-button>
                    </a>
                    <x-primary-button>{{ isset($project) ? 'Update' : 'Create' }}</x-primary-button>
                </div>

            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            function setupSearch(inputId, itemClass) {
                const input = document.getElementById(inputId);
                input.addEventListener('input', () => {
                    const term = input.value.toLowerCase();
                    document.querySelectorAll('.' + itemClass).forEach(el => {
                        el.style.display = el.textContent.toLowerCase().includes(term) ? '' : 'none';
                    });
                });
            }
            setupSearch('search-teams', 'team-item');
            setupSearch('search-members', 'member-item');
        });
    </script>
    @endpush
</x-app-layout>
