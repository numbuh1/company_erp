<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ isset($task) ? 'Edit Task' : 'Create Task' }}
            </h2>
            <a href="{{ route('tasks.index') }}"><x-secondary-button>Quay lại</x-secondary-button></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ isset($task) ? route('tasks.update', $task) : route('tasks.store') }}">
                    @csrf
                    @if(isset($task)) @method('PUT') @endif

                    {{-- Name --}}
                    <div class="mb-4">
                        <x-input-label for="name" value="Name *" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                            value="{{ old('name', $task->name ?? '') }}" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    {{-- Project --}}
                    <div class="mb-4">
                        <x-input-label for="project_id" value="Linked Project" />
                        <select id="project_id" name="project_id"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— None (standalone) —</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}"
                                    {{ old('project_id', $task->project_id ?? $default_project_id ?? '') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('project_id')" class="mt-1" />
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <x-input-label for="description" value="Mô tả" />
                        <textarea id="description" name="description" rows="4"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $task->description ?? '') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    {{-- Progress --}}
                    <div class="mb-4">
                        <x-input-label for="progress" value="Tiến độ (%)" />
                        <x-text-input id="progress" name="progress" type="number" min="0" max="100"
                            class="mt-1 block w-full"
                            value="{{ old('progress', $task->progress ?? 0) }}" />
                        <x-input-error :messages="$errors->get('progress')" class="mt-1" />
                    </div>

                    {{-- Status --}}
                    <div class="mb-4">
                        <x-input-label for="status" value="Trạng thái" />
                        <select id="status" name="status"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(['Not Started', 'In Progress', 'Done'] as $s)
                                <option value="{{ $s }}" {{ old('status', $task->status ?? 'Not Started') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-1" />
                    </div>                    

                    {{-- Dates --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <div>
                            <x-input-label for="start_date" value="Ngày bắt đầu" />
                            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full"
                                value="{{ old('start_date', isset($task) ? $task->start_date?->format('Y-m-d') : '') }}" />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="expected_end_date" value="Dự kiến kết thúc" />
                            <x-text-input id="expected_end_date" name="expected_end_date" type="date" class="mt-1 block w-full"
                                value="{{ old('expected_end_date', isset($task) ? $task->expected_end_date?->format('Y-m-d') : '') }}" />
                            <x-input-error :messages="$errors->get('expected_end_date')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="actual_end_date" value="Kết thúc thực tế" />
                            <x-text-input id="actual_end_date" name="actual_end_date" type="date" class="mt-1 block w-full"
                                value="{{ old('actual_end_date', isset($task) ? $task->actual_end_date?->format('Y-m-d') : '') }}" />
                            <x-input-error :messages="$errors->get('actual_end_date')" class="mt-1" />
                        </div>
                    </div>

                    {{-- Assignees --}}
                    <div class="mb-6">
                        <x-input-label value="Người được phân công" />
                        <select name="assignees[]" id="assignees-select" data-multi-select
                                data-placeholder="Chọn người được phân công…" class="mt-2 block w-full" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ in_array($user->id, old('assignees', isset($task) ? $task->assignees->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('assignees')" class="mt-1" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('tasks.index') }}"><x-secondary-button type="button">Hủy</x-secondary-button></a>
                        <x-primary-button type="submit">{{ isset($task) ? 'Update Task' : 'Create Task' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
        @push('scripts')
    <script>
        var allUsers       = @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]));
        var projectMembers = @json($projectMembers);
        var initialSelected = @json(array_map('intval', old('assignees', isset($task) ? $task->assignees->pluck('id')->toArray() : [])));

        document.addEventListener('DOMContentLoaded', function () {
            var projectSelect  = document.getElementById('project_id');
            var assigneeSelect = document.getElementById('assignees-select');

            function getTheme() {
                return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            }

            function rebuildAssignees(selectedIds) {
                var pid     = projectSelect.value;
                var allowed = (pid && projectMembers[pid]) ? projectMembers[pid] : null;

                var usersToShow = allowed !== null
                    ? allUsers.filter(function (u) { return allowed.includes(u.id); })
                    : allUsers;

                assigneeSelect.innerHTML = '';
                usersToShow.forEach(function (u) {
                    var opt       = document.createElement('option');
                    opt.value     = u.id;
                    opt.textContent = u.name;
                    if (selectedIds.includes(u.id)) opt.selected = true;
                    assigneeSelect.appendChild(opt);
                });
            }

            // Runs BEFORE MultiSelect initialises — MultiSelect then reads our filtered options
            rebuildAssignees(initialSelected);

            // After MultiSelect is live, respond to project changes
            projectSelect.addEventListener('change', function () {
                // Capture currently checked values from MultiSelect data
                var currentSelected = [];
                if (assigneeSelect._multiSelect) {
                    assigneeSelect._multiSelect.data.forEach(function (item) {
                        if (item.selected) currentSelected.push(parseInt(item.value));
                    });
                    assigneeSelect._multiSelect.destroy();
                }

                rebuildAssignees(currentSelected);
                new MultiSelect(assigneeSelect, { theme: getTheme() });
            });
        });
    </script>
    @endpush
</x-app-layout>
