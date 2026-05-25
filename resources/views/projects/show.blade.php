<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <span class="font-mono text-sm font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">PJ-{{ $project->id }}</span>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $project->name }}</h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('time-logs.create', ['project_id' => $project->id]) }}"><x-secondary-button>Chấm công</x-secondary-button></a>
                @canany(['edit projects', 'edit assigned projects'])
                    <a href="{{ route('projects.edit', $project) }}"><x-secondary-button>Chỉnh sửa</x-secondary-button></a>
                @endcanany
                <a href="{{ route('projects.index') }}"><x-secondary-button>Quay lại</x-secondary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="{
        teamModal: false,
        teamName: '',
        activeTeamId: null,
        activeTab: '{{ $tsInitialTab }}',
        cols: {
            status:     true,
            assignees:  true,
            progress:   true,
            start_date: true,
            due_date:   true,
        },
        openTeamModal(id, name) {
            this.activeTeamId = id;
            this.teamName = name;
            this.teamModal = true;
        },
        init() {
            this.$watch('activeTab', (tab) => {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                if (tab !== 'timesheet') {
                    url.searchParams.delete('tsmonth');
                }
                window.history.replaceState({}, '', url.toString());
            });
        }
    }">
        @if(session('success'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        <div class="max-w-7xl mx-auto space-y-6">

            {{-- Project Info --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Chi tiết dự án</h3>

                @php
                    $statusClass = match($project->status) {
                        'Đang tiến hành' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                        'Đã xong'        => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                        default          => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                    };
                @endphp

                <div class="mb-4">
                    <x-input-label value="Trạng thái" />
                    <div class="mt-1">
                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ $statusClass }}">{{ $project->status }}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <x-input-label value="Mô tả" />
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $project->description ?? '—' }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label value="Bắt đầu" />
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $project->start_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <x-input-label value="End (Est.)" />
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $project->expected_end_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <x-input-label value="End (Actual)" />
                        <p class="mt-1 text-sm {{ $project->actual_end_date ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $project->actual_end_date?->format('d/m/Y') ?? '—' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label value="Nhóm được phân công" />
                        <div class="flex flex-wrap gap-1 mt-1">
                            @forelse($project->teams as $team)
                                <button type="button"
                                    @click="openTeamModal({{ $team->id }}, '{{ addslashes($team->name) }}')"
                                    class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 text-xs px-2 py-0.5 rounded hover:bg-blue-200 dark:hover:bg-blue-800 cursor-pointer transition">
                                    {{ $team->name }}
                                </button>
                            @empty
                                <span class="text-sm text-gray-400">Không có</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <x-input-label value="Thành viên được phân công" />
                        <div class="mt-2 space-y-1">
                            @forelse($project->users as $member)
                                <a href="{{ route('users.show', $member) }}" class="flex items-center gap-2 hover:opacity-80 transition rounded px-1 py-0.5">
                                    <x-user-status :user="$member" />
                                </a>
                            @empty
                                <span class="text-sm text-gray-400">Không có</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div>
                {{-- Tab Bar --}}
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button @click="activeTab = 'tasks'"
                        :class="activeTab === 'tasks'
                            ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="px-5 py-3 text-sm font-medium -mb-px transition">
                        Công việc
                    </button>
                    <button @click="activeTab = 'files'"
                        :class="activeTab === 'files'
                            ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="px-5 py-3 text-sm font-medium -mb-px transition">
                        Files
                    </button>
                    <button @click="activeTab = 'comments'"
                        :class="activeTab === 'comments'
                            ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="px-5 py-3 text-sm font-medium -mb-px transition">
                        Comments
                        @if($project->comments->isNotEmpty())
                            <span class="ml-1.5 text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 px-1.5 py-0.5 rounded-full">{{ $project->comments->count() }}</span>
                        @endif
                    </button>
                    <button @click="activeTab = 'activity'"
                        :class="activeTab === 'activity'
                            ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="px-5 py-3 text-sm font-medium -mb-px transition">
                        Nhật ký hoạt động
                    </button>
                    @canany(['view own timesheet', 'view team timesheet', 'view all timesheet'])
                    <button @click="activeTab = 'timesheet'"
                        :class="activeTab === 'timesheet'
                            ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="px-5 py-3 text-sm font-medium -mb-px transition">
                        Timesheet
                    </button>
                    @endcanany
                </div>

                {{-- Tasks Panel --}}
                <div x-show="activeTab === 'tasks'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-b-lg sm:rounded-tr-lg">

                    {{-- Filter & column bar --}}
                    <div class="px-5 pt-5 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200">Công việc</h3>
                            @can('edit tasks')
                                <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}">
                                    <x-secondary-button>Thêm công việc</x-secondary-button>
                                </a>
                            @endcan
                        </div>

                        <form method="GET" action="{{ route('projects.show', $project) }}" class="flex flex-wrap items-end gap-3">
                            <input type="hidden" name="tab" value="tasks">

                            {{-- Search --}}
                            <div class="flex-1 min-w-[180px]">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tìm kiếm</label>
                                <input type="text" name="search" value="{{ $taskSearch }}"
                                       placeholder="Tên hoặc TK-ID…"
                                       class="block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            {{-- Assignee --}}
                            <div class="min-w-[160px]">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Người phân công</label>
                                <select id="proj-task-assignee-select" name="assignee_id">
                                    <option value="">— Tất cả —</option>
                                    @foreach($taskAssignees as $u)
                                        <option value="{{ $u->id }}" @selected($taskAssigneeId == $u->id)>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Sort --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Sắp xếp</label>
                                <select name="sort"
                                        class="mt-0 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                    <option value="id_asc"   @selected($taskSort === 'id_asc')>ID ↑</option>
                                    <option value="id_desc"  @selected($taskSort === 'id_desc')>ID ↓</option>
                                    <option value="due_asc"  @selected($taskSort === 'due_asc')>End Date ↑</option>
                                    <option value="due_desc" @selected($taskSort === 'due_desc')>End Date ↓</option>
                                </select>
                            </div>

                            {{-- Buttons --}}
                            <div class="flex items-end gap-2">
                                <button type="submit"
                                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition">
                                    Lọc
                                </button>
                                @if($taskSearch || $taskAssigneeId || $taskSort !== 'id_asc')
                                    <a href="{{ route('projects.show', ['project' => $project->id, 'tab' => 'tasks']) }}"
                                       class="px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </form>

                        {{-- Column visibility toggles --}}
                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-x-5 gap-y-1.5">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 shrink-0">Cột:</span>
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400 cursor-pointer select-none">
                                <input type="checkbox" x-model="cols.status"     class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> Trạng thái
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400 cursor-pointer select-none">
                                <input type="checkbox" x-model="cols.assignees"  class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> Người phân công
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400 cursor-pointer select-none">
                                <input type="checkbox" x-model="cols.progress"   class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> Tiến độ
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400 cursor-pointer select-none">
                                <input type="checkbox" x-model="cols.start_date" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> Bắt đầu
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400 cursor-pointer select-none">
                                <input type="checkbox" x-model="cols.due_date"   class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> End (EST)
                            </label>
                        </div>
                    </div>

                    {{-- Table --}}
                    @if($projectTasks->isEmpty())
                        <div class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            {{ ($taskSearch || $taskAssigneeId) ? 'Không tìm thấy công việc nào khớp với bộ lọc.' : 'Chưa có công việc.' }}
                        </div>
                    @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Tên</th>
                                    <th :class="{ 'hidden': !cols.status }"     class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Trạng thái</th>
                                    <th :class="{ 'hidden': !cols.assignees }"  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Người phân công</th>
                                    <th :class="{ 'hidden': !cols.progress }"   class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Tiến độ</th>
                                    <th :class="{ 'hidden': !cols.start_date }" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Bắt đầu</th>
                                    <th :class="{ 'hidden': !cols.due_date }"   class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">End (EST)</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($projectTasks as $task)
                                    @php
                                        $taskStatusClass = match($task->status) {
                                            'Đang tiến hành' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                            'Đã xong'        => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                                            default          => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">

                                        {{-- ID --}}
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a href="{{ route('tasks.show', $task) }}"
                                               class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                                TK-{{ $task->id }}
                                            </a>
                                        </td>

                                        {{-- Name --}}
                                        <td class="px-4 py-3">
                                            <a href="{{ route('tasks.show', $task) }}"
                                               class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ $task->name }}
                                            </a>
                                        </td>

                                        {{-- Status --}}
                                        <td :class="{ 'hidden': !cols.status }" class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-xs font-medium px-2 py-0.5 rounded {{ $taskStatusClass }}">{{ $task->status }}</span>
                                        </td>

                                        {{-- Assignees --}}
                                        <td :class="{ 'hidden': !cols.assignees }" class="px-4 py-3">
                                            <div class="flex flex-col gap-1">
                                                @forelse($task->assignees as $assignee)
                                                    <a href="{{ route('users.show', $assignee) }}"
                                                       class="flex items-center gap-1.5 hover:opacity-80 transition min-w-max">
                                                        @if($assignee->profile_picture)
                                                            <img src="{{ asset('storage/profile_pictures/' . $assignee->profile_picture) }}"
                                                                 class="w-5 h-5 rounded-full object-cover shrink-0" alt="">
                                                        @else
                                                            <div class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                                                                <span class="text-indigo-600 dark:text-indigo-400 font-semibold text-[9px]">{{ mb_strtoupper(mb_substr($assignee->name, 0, 1)) }}</span>
                                                            </div>
                                                        @endif
                                                        <span class="text-xs text-gray-700 dark:text-gray-300">{{ $assignee->name }}</span>
                                                    </a>
                                                @empty
                                                    <span class="text-gray-400 text-xs">—</span>
                                                @endforelse
                                            </div>
                                        </td>

                                        {{-- Progress --}}
                                        <td :class="{ 'hidden': !cols.progress }" class="px-4 py-3">
                                            <div class="flex items-center gap-2 min-w-[90px]">
                                                <div class="flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $task->progress }}%"></div>
                                                </div>
                                                <span class="text-xs text-gray-500 w-7 text-right">{{ $task->progress }}%</span>
                                            </div>
                                        </td>

                                        {{-- Start --}}
                                        <td :class="{ 'hidden': !cols.start_date }" class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            {{ $task->start_date?->format('d/m/Y') ?? '—' }}
                                        </td>

                                        {{-- Due --}}
                                        <td :class="{ 'hidden': !cols.due_date }" class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            {{ $task->expected_end_date?->format('d/m/Y') ?? '—' }}
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('tasks.show', $task) }}" title="Xem"
                                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-blue-600 hover:border-blue-400 bg-white dark:bg-gray-700 transition">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xem</span>
                                                </a>
                                                @canany(['edit tasks', 'edit assigned tasks'])
                                                    <a href="{{ route('tasks.edit', $task) }}" title="Sửa"
                                                        class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Sửa</span>
                                                    </a>
                                                @endcanany
                                                @can('delete tasks')
                                                    <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" title="Xóa"
                                                            onclick="return confirm('Xóa công việc này?')"
                                                            class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xóa</span>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                {{-- Files Panel --}}
                <div x-show="activeTab === 'files'" class="sm:rounded-b-lg sm:rounded-tr-lg overflow-hidden">
                    <x-file-explorer
                        :model="$project"
                        route-prefix="projects"
                        :storage-path="'project_files/' . $project->id"
                        :items="$items"
                        :current-folder="$currentFolder"
                        :breadcrumb="$breadcrumb"
                        :can-upload="true"
                        :can-manage-all="$canManageAll"
                        :extra-params="['tab' => 'files']"
                    />
                </div>

                {{-- Comments Panel --}}
                <div x-show="activeTab === 'comments'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-b-lg sm:rounded-tr-lg p-6">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-5">Bình luận</h3>
                    @include('partials.comments', [
                        'commentable'     => $project,
                        'commentableType' => 'project',
                    ])
                </div>

                {{-- Activity Log Panel --}}
                <div x-show="activeTab === 'activity'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-b-lg sm:rounded-tr-lg p-6">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Nhật ký hoạt động</h3>

                    @if($activities->isEmpty())
                        <p class="text-sm text-gray-400">Chưa có hoạt động nào.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($activities as $activity)
                                <div class="flex gap-4 text-sm border-l-2 border-indigo-300 pl-4 py-1">
                                    <div class="text-gray-400 whitespace-nowrap w-32 shrink-0">
                                        {{ $activity->created_at->format('d/m/y H:i') }}
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $activity->causer?->name ?? 'System' }}
                                        </span>
                                        <span class="text-gray-500 ml-1">{{ $activity->description }}</span>

                                        @php $changes = $activity->properties['attributes'] ?? []; @endphp
                                        @if(count($changes))
                                            <div class="mt-1 space-y-0.5">
                                                @foreach($changes as $key => $newVal)
                                                    @php $oldVal = $activity->properties['old'][$key] ?? null; @endphp
                                                    <div class="text-xs text-gray-500">
                                                        <span class="font-medium">{{ str_replace('_', ' ', $key) }}</span>:
                                                        @if($oldVal !== null)
                                                            <span class="line-through text-red-400">{{ $oldVal }}</span> →
                                                        @endif
                                                        <span class="text-green-600">{{ $newVal }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Timesheet Panel --}}
                @canany(['view own timesheet', 'view team timesheet', 'view all timesheet'])
                <div x-show="activeTab === 'timesheet'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-b-lg sm:rounded-tr-lg">
                    @php
                        $fmtCost = function(?float $n) {
                            if (!$n) return null;
                            if ($n >= 1_000_000) return number_format($n / 1_000_000, 1) . 'M';
                            if ($n >= 1_000)     return number_format($n / 1_000, 0) . 'k';
                            return number_format($n, 0);
                        };
                        $fmtHours = function(float $h): string {
                            return $h > 0 ? number_format($h, 1) . 'h' : '';
                        };
                        $nDays = $tsDays->count();

                        $bgData1 = 'bg-white dark:bg-gray-800';
                        $bgData2 = 'bg-gray-50 dark:bg-gray-800';
                        $bgHead  = 'bg-gray-50 dark:bg-gray-700';
                        $bgSec   = 'bg-gray-200 dark:bg-gray-600';
                        $bgTot   = 'bg-gray-100 dark:bg-gray-700';
                        $edge    = 'shadow-[2px_0_5px_-1px_rgba(0,0,0,0.12)] dark:shadow-[2px_0_5px_-1px_rgba(0,0,0,0.40)]';

                        $thC1 = "sticky left-0 z-20 {$bgHead} w-[180px] min-w-[180px] max-w-[180px] px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap";
                        $thC2 = "sticky left-[180px] z-20 {$bgHead} w-[88px] min-w-[88px] {$edge} px-2 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase border-r border-gray-200 dark:border-gray-600";
                        $tdC1 = "sticky left-0 z-10 {$bgData1} w-[180px] min-w-[180px] max-w-[180px] px-3 py-2 whitespace-nowrap overflow-hidden text-ellipsis";
                        $tdC2 = "sticky left-[180px] z-10 {$bgData2} w-[88px] min-w-[88px] {$edge} px-2 py-1.5 text-center border-r border-gray-200 dark:border-gray-600";
                        $secC1 = "sticky left-0 z-10 {$bgSec} w-[180px] min-w-[180px] max-w-[180px] px-3 py-1.5 text-[11px] font-bold text-gray-600 dark:text-gray-200 uppercase tracking-widest";
                        $secC2 = "sticky left-[180px] z-10 {$bgSec} w-[88px] min-w-[88px] {$edge} border-r border-gray-300 dark:border-gray-500";
                        $totC1 = "sticky left-0 z-10 {$bgTot} w-[180px] min-w-[180px] max-w-[180px] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300";
                        $totC2 = "sticky left-[180px] z-10 {$bgTot} w-[88px] min-w-[88px] {$edge} px-2 py-1.5 text-center font-semibold border-r border-gray-200 dark:border-gray-600";
                    @endphp

                    {{-- Month nav --}}
                    <div class="px-4 pt-4 pb-2 flex items-center gap-2">
                        <a href="{{ route('projects.show', ['project' => $project->id, 'tab' => 'timesheet', 'tsmonth' => $tsPrevMonth]) }}"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition">←</a>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $tsMonthDate->translatedFormat('F Y') }}</span>
                        <a href="{{ route('projects.show', ['project' => $project->id, 'tab' => 'timesheet', 'tsmonth' => $tsNextMonth]) }}"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition">→</a>
                        @if($tsMonthStr !== now()->format('Y-m'))
                            <a href="{{ route('projects.show', ['project' => $project->id, 'tab' => 'timesheet']) }}"
                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline px-1">Tháng này</a>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="text-xs border-collapse" style="table-layout:fixed; width:max-content; min-width:100%">
                            <colgroup>
                                <col style="width:180px">
                                <col style="width:88px">
                                @foreach($tsDays as $__)
                                    <col style="width:56px">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="{{ $thC1 }}">Mục</th>
                                    <th class="{{ $thC2 }}">Tổng</th>
                                    @foreach($tsDays as $day)
                                        @php
                                            $dk      = $day->format('Y-m-d');
                                            $isHol   = in_array($dk, $tsHolidayDates);
                                            $isWknd  = $day->isWeekend();
                                            $isToday = $day->isToday();
                                            $dayCls  = $isToday
                                                ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-bold'
                                                : ($isHol || $isWknd ? 'text-red-400 dark:text-red-400' : 'text-gray-500 dark:text-gray-400');
                                        @endphp
                                        <th class="px-1 py-2 text-center font-medium whitespace-nowrap {{ $dayCls }}">
                                            <div>{{ $day->format('d') }}</div>
                                            <div class="text-[10px] font-normal opacity-60">{{ $day->translatedFormat('D') }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                                {{-- Section: Công việc --}}
                                <tr>
                                    <td class="{{ $secC1 }}">Công việc</td>
                                    <td class="{{ $secC2 }}"></td>
                                    <td colspan="{{ $nDays }}" class="{{ $bgSec }}"></td>
                                </tr>

                                @forelse($tsTaskRows as $key => $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                        <td class="{{ $tdC1 }}" title="{{ $row['task']?->name ?? $row['label'] }}">
                                            @if($row['task'])
                                                <a href="{{ route('tasks.show', $row['task_id']) }}"
                                                    class="font-mono text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">TK-{{ $row['task_id'] }}</a>
                                                <span class="ml-1 text-gray-700 dark:text-gray-300">{{ $row['task']->name }}</span>
                                            @else
                                                <span class="text-gray-400 italic">{{ $row['label'] }}</span>
                                            @endif
                                        </td>
                                        <td class="{{ $tdC2 }}">
                                            @if($row['total_hours'] > 0)
                                                <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($row['total_hours']) }}</div>
                                            @endif
                                            @if($row['total_ot'] > 0)
                                                <div class="text-orange-500">+{{ $fmtHours($row['total_ot']) }}</div>
                                            @endif
                                            @if($tsCanViewSalary && $row['total_cost'] > 0)
                                                <div class="text-gray-400 text-[10px]">{{ $fmtCost($row['total_cost']) }}</div>
                                            @endif
                                            @if($tsCanViewSalary && ($row['total_ot_cost'] ?? 0) > 0)
                                                <div class="text-orange-500 text-[10px]">+{{ $fmtCost($row['total_ot_cost']) }}</div>
                                            @endif
                                        </td>
                                        @foreach($tsDays as $day)
                                            @php
                                                $dk   = $day->format('Y-m-d');
                                                $cell = $row['days'][$dk] ?? null;
                                                $wkBg = (in_array($dk, $tsHolidayDates) || $day->isWeekend()) ? 'bg-red-50/40 dark:bg-red-900/10' : '';
                                                $url  = route('time-logs.index', array_filter([
                                                    'project_id' => $project->id,
                                                    'task_id'    => $row['task_id'] ?: null,
                                                    'date_from'  => $dk, 'date_to' => $dk,
                                                ]));
                                            @endphp
                                            <td class="px-0.5 py-1.5 text-center align-top {{ $wkBg }}">
                                                @if($cell && ($cell['hours'] > 0 || $cell['ot_hours'] > 0))
                                                    <a href="{{ $url }}" class="block rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/30 px-0.5 py-0.5 transition">
                                                        @if($cell['hours'] > 0)<div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($cell['hours']) }}</div>@endif
                                                        @if($cell['ot_hours'] > 0)<div class="text-orange-500">+{{ $fmtHours($cell['ot_hours']) }}</div>@endif
                                                        @if($tsCanViewSalary && $cell['cost'] > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($cell['cost']) }}</div>@endif
                                                        @if($tsCanViewSalary && ($cell['ot_cost'] ?? 0) > 0)<div class="text-orange-500 text-[10px]">+{{ $fmtCost($cell['ot_cost']) }}</div>@endif
                                                    </a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="{{ $tdC1 }} text-gray-400 italic">Không có dữ liệu</td>
                                        <td class="{{ $tdC2 }}"></td>
                                        <td colspan="{{ $nDays }}"></td>
                                    </tr>
                                @endforelse

                                {{-- Task section total --}}
                                @if(count($tsTaskRows) > 0)
                                <tr class="border-t border-gray-200 dark:border-gray-600">
                                    <td class="{{ $totC1 }}">Tổng công việc</td>
                                    <td class="{{ $totC2 }}">
                                        <div class="text-gray-800 dark:text-gray-200">{{ $fmtHours($tsGrandTotalHours) }}</div>
                                        @if($tsGrandTotalOt > 0)<div class="text-orange-500">+{{ $fmtHours($tsGrandTotalOt) }}</div>@endif
                                        @if($tsCanViewSalary && $tsGrandTotalCost > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($tsGrandTotalCost) }}</div>@endif
                                        @if($tsCanViewSalary && $tsGrandTotalOtCost > 0)<div class="text-orange-500 text-[10px]">+{{ $fmtCost($tsGrandTotalOtCost) }}</div>@endif
                                    </td>
                                    @foreach($tsDays as $day)
                                        @php $dk = $day->format('Y-m-d'); $tot = $tsDayTotals[$dk] ?? ['hours'=>0,'ot_hours'=>0,'cost'=>0,'ot_cost'=>0]; @endphp
                                        <td class="px-0.5 py-1.5 text-center {{ $bgTot }}">
                                            @if($tot['hours'] > 0 || $tot['ot_hours'] > 0)
                                                <div class="text-gray-700 dark:text-gray-300">{{ $fmtHours($tot['hours']) }}</div>
                                                @if($tot['ot_hours'] > 0)<div class="text-orange-500">+{{ $fmtHours($tot['ot_hours']) }}</div>@endif
                                                @if($tsCanViewSalary && $tot['cost'] > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($tot['cost']) }}</div>@endif
                                                @if($tsCanViewSalary && ($tot['ot_cost'] ?? 0) > 0)<div class="text-orange-500 text-[10px]">+{{ $fmtCost($tot['ot_cost']) }}</div>@endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endif

                                {{-- Section: Thành viên --}}
                                <tr>
                                    <td class="{{ $secC1 }} border-t-[3px] border-gray-400 dark:border-gray-500">Thành viên</td>
                                    <td class="{{ $secC2 }} border-t-[3px] border-gray-400 dark:border-gray-500"></td>
                                    <td colspan="{{ $nDays }}" class="{{ $bgSec }} border-t-[3px] border-gray-400 dark:border-gray-500"></td>
                                </tr>

                                @forelse($tsUserRows as $key => $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                        <td class="{{ $tdC1 }}">
                                            @if($row['user'])
                                                <a href="{{ route('users.show', $row['user_id']) }}"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                                    {{ $row['user']->name }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">#{{ $row['user_id'] }}</span>
                                            @endif
                                        </td>
                                        <td class="{{ $tdC2 }}">
                                            @if($row['total_hours'] > 0)
                                                <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($row['total_hours']) }}</div>
                                            @endif
                                            @if($row['total_ot'] > 0)
                                                <div class="text-orange-500">+{{ $fmtHours($row['total_ot']) }}</div>
                                            @endif
                                            @if($tsCanViewSalary && $row['total_cost'] > 0)
                                                <div class="text-gray-400 text-[10px]">{{ $fmtCost($row['total_cost']) }}</div>
                                            @endif
                                            @if($tsCanViewSalary && ($row['total_ot_cost'] ?? 0) > 0)
                                                <div class="text-orange-500 text-[10px]">+{{ $fmtCost($row['total_ot_cost']) }}</div>
                                            @endif
                                        </td>
                                        @foreach($tsDays as $day)
                                            @php
                                                $dk   = $day->format('Y-m-d');
                                                $cell = $row['days'][$dk] ?? null;
                                                $wkBg = (in_array($dk, $tsHolidayDates) || $day->isWeekend()) ? 'bg-red-50/40 dark:bg-red-900/10' : '';
                                                $url  = route('time-logs.index', array_filter([
                                                    'project_id' => $project->id,
                                                    'user_id'    => $row['user_id'],
                                                    'date_from'  => $dk, 'date_to' => $dk,
                                                ]));
                                            @endphp
                                            <td class="px-0.5 py-1.5 text-center align-top {{ $wkBg }}">
                                                @if($cell && ($cell['hours'] > 0 || $cell['ot_hours'] > 0))
                                                    <a href="{{ $url }}" class="block rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/30 px-0.5 py-0.5 transition">
                                                        @if($cell['hours'] > 0)<div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($cell['hours']) }}</div>@endif
                                                        @if($cell['ot_hours'] > 0)<div class="text-orange-500">+{{ $fmtHours($cell['ot_hours']) }}</div>@endif
                                                        @if($tsCanViewSalary && $cell['cost'] > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($cell['cost']) }}</div>@endif
                                                        @if($tsCanViewSalary && ($cell['ot_cost'] ?? 0) > 0)<div class="text-orange-500 text-[10px]">+{{ $fmtCost($cell['ot_cost']) }}</div>@endif
                                                    </a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="{{ $tdC1 }} text-gray-400 italic">Không có dữ liệu</td>
                                        <td class="{{ $tdC2 }}"></td>
                                        <td colspan="{{ $nDays }}"></td>
                                    </tr>
                                @endforelse

                                {{-- Grand total --}}
                                @if(count($tsUserRows) > 0)
                                <tr class="border-t border-gray-200 dark:border-gray-600">
                                    <td class="{{ $totC1 }}">Tổng cộng</td>
                                    <td class="{{ $totC2 }}">
                                        <div class="text-gray-800 dark:text-gray-200">{{ $fmtHours($tsGrandTotalHours) }}</div>
                                        @if($tsGrandTotalOt > 0)<div class="text-orange-500">+{{ $fmtHours($tsGrandTotalOt) }}</div>@endif
                                        @if($tsCanViewSalary && $tsGrandTotalCost > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($tsGrandTotalCost) }}</div>@endif
                                        @if($tsCanViewSalary && $tsGrandTotalOtCost > 0)<div class="text-orange-500 text-[10px]">+{{ $fmtCost($tsGrandTotalOtCost) }}</div>@endif
                                    </td>
                                    @foreach($tsDays as $day)
                                        @php $dk = $day->format('Y-m-d'); $tot = $tsDayTotals[$dk] ?? ['hours'=>0,'ot_hours'=>0,'cost'=>0,'ot_cost'=>0]; @endphp
                                        <td class="px-0.5 py-1.5 text-center {{ $bgTot }}">
                                            @if($tot['hours'] > 0 || $tot['ot_hours'] > 0)
                                                <div class="text-gray-700 dark:text-gray-300">{{ $fmtHours($tot['hours']) }}</div>
                                                @if($tot['ot_hours'] > 0)<div class="text-orange-500">+{{ $fmtHours($tot['ot_hours']) }}</div>@endif
                                                @if($tsCanViewSalary && $tot['cost'] > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($tot['cost']) }}</div>@endif
                                                @if($tsCanViewSalary && ($tot['ot_cost'] ?? 0) > 0)<div class="text-orange-500 text-[10px]">+{{ $fmtCost($tot['ot_cost']) }}</div>@endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endif

                            </tbody>
                        </table>
                    </div>

                    {{-- Footer summary --}}
                    @if($tsGrandTotalHours > 0 || $tsGrandTotalOt > 0)
                    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex flex-wrap gap-6 text-sm">
                            <div>
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-0.5">Tổng giờ</div>
                                <div class="font-bold text-gray-800 dark:text-gray-100">{{ number_format($tsGrandTotalHours + $tsGrandTotalOt, 1) }}h</div>
                                @if($tsGrandTotalOt > 0)
                                    <div class="text-xs text-orange-500">OT: {{ number_format($tsGrandTotalOt, 1) }}h</div>
                                @endif
                            </div>
                            @if($tsCanViewSalary && ($tsGrandTotalCost + $tsGrandTotalOtCost) > 0)
                            <div>
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-0.5">Tổng chi phí</div>
                                <div class="font-bold text-gray-800 dark:text-gray-100">{{ number_format($tsGrandTotalCost + $tsGrandTotalOtCost, 0, '.', ',') }} ₫</div>
                                @if($tsGrandTotalOtCost > 0)
                                    <div class="text-xs text-orange-500">OT: {{ number_format($tsGrandTotalOtCost, 0, '.', ',') }} ₫</div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @endcanany

            </div>

        </div>
    </div>

    {{-- Team Members Modal --}}
    <x-team-modal :teams="$project->teams" />

    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet"/>
    <style>
        .ts-wrapper .ts-control { border-color: #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; min-height: 2.25rem; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
        .dark .ts-wrapper .ts-control { background: #111827; border-color: #374151; color: #d1d5db; }
        .dark .ts-dropdown { background: #1f2937; border-color: #374151; color: #d1d5db; }
        .dark .ts-dropdown .option:hover, .dark .ts-dropdown .option.active { background: #374151; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('proj-task-assignee-select');
        if (el) new TomSelect(el, { allowEmptyOption: true, maxOptions: 200 });
    });
    </script>
    @endpush
</x-app-layout>
