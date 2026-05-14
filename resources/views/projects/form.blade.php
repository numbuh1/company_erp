<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($project) ? 'Chỉnh sửa Dự án' : 'Tạo Dự án' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form method="POST" action="{{ isset($project) ? route('projects.update', $project) : route('projects.store') }}">
                @csrf
                @if(isset($project)) @method('PUT') @endif

                {{-- Main Info --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Chi tiết dự án</h3>

                    <div class="mb-4">
                        <x-input-label value="Tên" />
                        <x-text-input name="name" class="mt-1 block w-full"
                            value="{{ old('name', $project->name ?? '') }}" required />
                    </div>

                    <div class="mb-4">
                        <x-input-label value="Trạng thái" />
                        <select name="status"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @foreach(['Not Started', 'In Progress', 'Done'] as $s)
                                <option value="{{ $s }}" {{ old('status', $project->status ?? 'Not Started') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>                    

                    <div class="mb-4">
                        <x-input-label value="Mô tả" />
                        <textarea name="description" rows="4"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $project->description ?? '') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label value="Ngày bắt đầu" />
                            <x-text-input type="date" name="start_date" class="mt-1 block w-full"
                                value="{{ old('start_date', isset($project) ? $project->start_date?->format('Y-m-d') : '') }}" />
                        </div>
                        <div>
                            <x-input-label value="Ngày kết thúc dự kiến" />
                            <x-text-input type="date" name="expected_end_date" class="mt-1 block w-full"
                                value="{{ old('expected_end_date', isset($project) ? $project->expected_end_date?->format('Y-m-d') : '') }}" />
                        </div>
                        <div>
                            <x-input-label value="Ngày kết thúc thực tế" />
                            <x-text-input type="date" name="actual_end_date" class="mt-1 block w-full"
                                value="{{ old('actual_end_date', isset($project) ? $project->actual_end_date?->format('Y-m-d') : '') }}" />
                        </div>
                    </div>
                </div>

                {{-- Team & Member Assignment --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Phân công</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        {{-- Teams --}}
                        <div>
                            <x-input-label value="Nhóm được phân công" />
                            <select name="teams[]" id="teams-select" data-multi-select
                                    data-placeholder="Chọn nhóm…" class="mt-1 block w-full" multiple>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}"
                                        {{ collect(old('teams', isset($project) ? $project->teams->pluck('id')->toArray() : []))->contains($team->id) ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Members --}}
                        <div>
                            <x-input-label value="Thành viên được phân công" />
                            <select name="members[]" id="members-select" data-multi-select
                                    data-placeholder="Chọn thành viên…" class="mt-1 block w-full" multiple>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}"
                                        {{ collect(old('members', isset($project) ? $project->users->pluck('id')->toArray() : []))->contains($u->id) ? 'selected' : '' }}>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex justify-end gap-2 mt-5 mb-10">
                    <a href="{{ isset($project) ? route('projects.show', $project) : route('projects.index') }}">
                        <x-secondary-button type="button">Hủy</x-secondary-button>
                    </a>
                    <x-primary-button>{{ isset($project) ? 'Lưu' : 'Tạo' }}</x-primary-button>
                </div>

            </form>
        </div>
    </div>

        @push('scripts')
    <script>
        var allUsers     = @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]));
        var teamMembers  = @json($teamMembers);
        var initialSelectedMembers = @json(array_map('intval', isset($project) ? $project->users->pluck('id')->toArray() : []));

        document.addEventListener('DOMContentLoaded', function () {
            var teamsSelect   = document.getElementById('teams-select');
            var membersSelect = document.getElementById('members-select');

            function getTheme() {
                return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            }

            function getSelectedTeamIds() {
                return Array.from(teamsSelect.selectedOptions).map(function (o) { return parseInt(o.value); });
            }

            function rebuildMembers(selectedIds) {
                var selectedTeams = getSelectedTeamIds();

                var usersToShow;
                if (selectedTeams.length > 0) {
                    var allowed = new Set();
                    selectedTeams.forEach(function (tid) {
                        (teamMembers[tid] || []).forEach(function (uid) { allowed.add(uid); });
                    });
                    usersToShow = allUsers.filter(function (u) { return allowed.has(u.id); });
                } else {
                    usersToShow = allUsers;
                }

                membersSelect.innerHTML = '';
                usersToShow.forEach(function (u) {
                    var opt         = document.createElement('option');
                    opt.value       = u.id;
                    opt.textContent = u.name;
                    if (selectedIds.includes(u.id)) opt.selected = true;
                    membersSelect.appendChild(opt);
                });
            }

            // Runs BEFORE MultiSelect initialises — MultiSelect reads already-filtered options
            rebuildMembers(initialSelectedMembers);

            // Respond to team changes after MultiSelect is live
            teamsSelect.addEventListener('change', function () {
                var currentSelected = [];
                if (membersSelect._multiSelect) {
                    membersSelect._multiSelect.data.forEach(function (item) {
                        if (item.selected) currentSelected.push(parseInt(item.value));
                    });
                    membersSelect._multiSelect.destroy();
                }

                rebuildMembers(currentSelected);
                new MultiSelect(membersSelect, { theme: getTheme() });
            });
        });
    </script>
    @endpush
</x-app-layout>
