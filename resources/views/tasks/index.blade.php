<x-app-layout>
    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet"/>
    <style>
        .ts-wrapper .ts-control { border-color: #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; min-height: 2.25rem; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
        .dark .ts-wrapper .ts-control { background: #111827; border-color: #374151; color: #d1d5db; }
        .dark .ts-dropdown { background: #1f2937; border-color: #374151; color: #d1d5db; }
        .dark .ts-dropdown .option:hover, .dark .ts-dropdown .option.active { background: #374151; }
    </style>
    @endpush

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Công việc</h2>
            @can('edit tasks')
                <a href="{{ route('tasks.create') }}">
                    <x-primary-button>Tạo Công việc</x-primary-button>
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="px-4 sm:px-6 py-4" x-data="{
        colsOpen: false,
        cols: {{ $colPrefs }},
        saveTimer: null,
        saveCols() {
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => {
                fetch('{{ route('user.column-preferences') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                    },
                    body: JSON.stringify({ context: 'task_list_column_preferences', cols: this.cols }),
                });
            }, 800);
        }
    }">
        <div class="space-y-4">

            @if(session('success'))
                <div class="p-3 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-lg text-sm">{{ session('success') }}</div>
            @endif

            {{-- ── Filter & Column Bar ──────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3">
                <form method="GET" action="{{ route('tasks.index') }}" class="flex flex-wrap items-end gap-3">

                    {{-- Search --}}
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tìm kiếm</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Tên hoặc TK-ID…"
                               class="block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    {{-- Project --}}
                    @if($projects->isNotEmpty())
                    <div class="min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Dự án</label>
                        <select id="task-project-select" name="project_id">
                            <option value="">— Tất cả —</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}" @selected(request('project_id') == $p->id)>{{ $p->project_code }} {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Assignee --}}
                    <div class="min-w-[180px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Người làm</label>
                        <select id="task-assignee-select" name="assignee_id">
                            <option value="">— Tất cả —</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" @selected(request('assignee_id') == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sort --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Sắp xếp</label>
                        <select name="sort"
                                class="mt-0 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="latest"   @selected(request('sort', 'latest') === 'latest')>Mới nhất</option>
                            <option value="id_asc"   @selected(request('sort') === 'id_asc')>ID ↑</option>
                            <option value="id_desc"  @selected(request('sort') === 'id_desc')>ID ↓</option>
                            <option value="due_asc"  @selected(request('sort') === 'due_asc')>End Date ↑</option>
                            <option value="due_desc" @selected(request('sort') === 'due_desc')>End Date ↓</option>
                        </select>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition">
                            Lọc
                        </button>
                        @if(request()->hasAny(['search', 'project_id', 'assignee_id', 'sort']))
                            <a href="{{ route('tasks.index') }}"
                               class="px-4 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>

                {{-- Column visibility popup --}}
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 shrink-0">Cột:</span>
                    <div class="relative" @click.outside="colsOpen = false">
                        <button type="button" @click="colsOpen = !colsOpen"
                                class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                            </svg>
                            Hiển thị cột
                            <svg class="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="colsOpen" x-cloak
                             class="absolute left-0 top-full mt-1 z-30 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-3 space-y-2 min-w-[170px]">
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer select-none hover:text-indigo-600 dark:hover:text-indigo-400">
                                <input type="checkbox" x-model="cols.project"    @change="saveCols()" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> Dự án
                            </label>
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer select-none hover:text-indigo-600 dark:hover:text-indigo-400">
                                <input type="checkbox" x-model="cols.status"     @change="saveCols()" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> Trạng thái
                            </label>
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer select-none hover:text-indigo-600 dark:hover:text-indigo-400">
                                <input type="checkbox" x-model="cols.assignees"  @change="saveCols()" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> Người làm
                            </label>
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer select-none hover:text-indigo-600 dark:hover:text-indigo-400">
                                <input type="checkbox" x-model="cols.budget"     @change="saveCols()" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> Budget Time
                            </label>
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer select-none hover:text-indigo-600 dark:hover:text-indigo-400">
                                <input type="checkbox" x-model="cols.start_date" @change="saveCols()" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> Bắt đầu
                            </label>
                            <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300 cursor-pointer select-none hover:text-indigo-600 dark:hover:text-indigo-400">
                                <input type="checkbox" x-model="cols.due_date"   @change="saveCols()" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"> End (EST)
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Table ───────────────────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Tên</th>
                                <th :class="{ 'hidden': !cols.project }"    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Dự án</th>
                                <th :class="{ 'hidden': !cols.status }"     class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Trạng thái</th>
                                <th :class="{ 'hidden': !cols.assignees }"  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Người làm</th>
                                <th :class="{ 'hidden': !cols.budget }" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Budget</th>
                                <th :class="{ 'hidden': !cols.budget }" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Thời gian</th>
                                <th :class="{ 'hidden': !cols.start_date }" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Bắt đầu</th>
                                <th :class="{ 'hidden': !cols.due_date }"   class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">End (EST)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide whitespace-nowrap">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($tasks as $task)
                                @php
                                    $statusClass = match($task->status) {
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
                                            {{ $task->task_code }}
                                        </a>
                                    </td>

                                    {{-- Name --}}
                                    <td class="px-4 py-3">
                                        <a href="{{ route('tasks.show', $task) }}"
                                           class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $task->name }}
                                        </a>
                                    </td>

                                    {{-- Project --}}
                                    <td :class="{ 'hidden': !cols.project }" class="px-4 py-3 whitespace-nowrap">
                                        @if($task->project)
                                            <a href="{{ route('projects.show', $task->project) }}"
                                               class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $task->project->project_code }}
                                            </a>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">{{ $task->project->name }}</span>
                                        @else
                                            <span class="text-gray-400 text-sm">—</span>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td :class="{ 'hidden': !cols.status }" class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ $statusClass }}">{{ $task->status }}</span>
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

                                    {{-- Budget: progress bar --}}
                                    <td :class="{ 'hidden': !cols.budget }" class="px-4 py-3">
                                        @php
                                            $budgetH   = $task->budget_hours;
                                            $ntH       = $timeSpentMap[$task->id] ?? 0;
                                            $otH       = $otTimeMap[$task->id] ?? 0;
                                            $actualH   = $ntH + $otH;
                                            $isDone    = $task->status === 'Đã xong';
                                            $percent   = $budgetH > 0 ? round($actualH / $budgetH * 100) : 0;
                                            $isOver    = $budgetH > 0 && $actualH > $budgetH;
                                            $barColor  = $isOver
                                                ? ($isDone ? 'bg-amber-700 dark:bg-amber-600' : 'bg-red-500')
                                                : ($isDone ? 'bg-green-500' : 'bg-blue-500');
                                            $textColor = $isOver
                                                ? ($isDone ? 'text-amber-700 dark:text-amber-500' : 'text-red-600 dark:text-red-400')
                                                : ($isDone ? 'text-green-600 dark:text-green-400' : 'text-gray-500');
                                        @endphp
                                        <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-2 overflow-hidden">
                                            <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ min($percent, 100) }}%"></div>
                                        </div>
                                    </td>
                                    {{-- Budget: spent / budget text --}}
                                    <td :class="{ 'hidden': !cols.budget }" class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-xs tabular-nums {{ $isOver ? 'font-semibold' : '' }} {{ $textColor }}">{{ number_format($actualH, 1) }}h / {{ $budgetH > 0 ? number_format($budgetH, 1) . 'h' : '—' }}</span>
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
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                                        Không tìm thấy công việc nào.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($tasks->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $tasks->links() }}</div>
                @endif
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const cfg = { allowEmptyOption: true, maxOptions: 300 };
        const projectEl  = document.getElementById('task-project-select');
        const assigneeEl = document.getElementById('task-assignee-select');
        if (projectEl)  new TomSelect(projectEl,  cfg);
        if (assigneeEl) new TomSelect(assigneeEl, cfg);
    });
    </script>
    @endpush
</x-app-layout>
