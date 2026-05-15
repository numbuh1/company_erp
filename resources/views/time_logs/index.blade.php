<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Bảng chấm công</h2>
            <a href="{{ route('time-logs.create') }}"><x-primary-button>Chấm công</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            {{-- Tabs --}}
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex gap-1">
                    <a href="{{ route('time-logs.index') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400">
                        Danh sách
                    </a>
                    <a href="{{ route('timesheets.weekly') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        Tuần
                    </a>
                    <a href="{{ route('timesheets.monthly') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        Tháng
                    </a>
                    <a href="{{ route('timesheets.project') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        Dự án
                    </a>
                </nav>
            </div>

            {{-- Filters --}}
            <form method="GET" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
                @if($users)
                    <div>
                        <x-input-label value="Người dùng" />
                        <select name="user_id" class="mt-1 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Tất cả người dùng</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if($teams)
                    <div>
                        <x-input-label value="Nhóm" />
                        <select name="team_id" class="mt-1 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Tất cả nhóm</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}" {{ request('team_id') == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <x-input-label value="Từ" />
                    <x-text-input type="date" id="date_from" name="date_from" class="mt-1 block" value="{{ request('date_from') }}" />
                </div>
                <div>
                    <x-input-label value="Đến" />
                    <x-text-input type="date" id="date_to" name="date_to" class="mt-1 block" value="{{ request('date_to') }}" />
                </div>
                <div class="flex flex-col gap-1 justify-end">
                    <x-input-label value="Nhanh" />
                    <div class="flex gap-1 mt-1">
                        <button type="button" onclick="setDateRange('this_month')"
                            class="px-2.5 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition whitespace-nowrap">
                            Tháng này
                        </button>
                        <button type="button" onclick="setDateRange('last_month')"
                            class="px-2.5 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition whitespace-nowrap">
                            Tháng trước
                        </button>
                    </div>
                </div>
                <div>
                    <x-input-label value="Dự án" />
                    <select name="project_id" class="mt-1 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Tất cả dự án</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                PJ-{{ $project->id }} {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Nhiệm vụ" />
                    <select name="task_id" class="mt-1 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Tất cả nhiệm vụ</option>
                        @foreach($tasks as $task)
                            <option value="{{ $task->id }}" {{ request('task_id') == $task->id ? 'selected' : '' }}>
                                TK-{{ $task->id }} {{ $task->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-primary-button type="submit">Lọc</x-primary-button>
                    <a href="{{ route('time-logs.index') }}"><x-secondary-button type="button">Đặt lại</x-secondary-button></a>
                </div>
            </form>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                            @if($users)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người dùng</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Công việc</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mô tả</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời gian</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($logs as $entry)
                            @php
                                $isOt  = $entry['_type'] === 'ot';
                                $model = $entry['_model'];
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ $isOt ? 'border-l-4 border-orange-400' : '' }}">
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $isOt ? \Carbon\Carbon::parse($model->start_at)->format('d/m/Y') : $model->date->format('d/m/Y') }}
                                </td>
                                @if($users)
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                        {{ $model->user?->name ?? '—' }}
                                    </td>
                                @endif
                                <td class="px-4 py-3 text-sm">
                                    @if($isOt)
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="text-xs font-semibold px-1.5 py-0.5 rounded bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400 shrink-0">OT</span>
                                            @if($model->task)
                                                <a href="{{ route('tasks.show', $model->task) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                    <span class="font-mono text-xs font-semibold">TK-{{ $model->task_id }}</span>
                                                    <span class="ml-1">{{ $model->task->name }}</span>
                                                </a>
                                            @elseif($model->project)
                                                <a href="{{ route('projects.show', $model->project) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                    <span class="font-mono text-xs font-semibold">PJ-{{ $model->project_id }}</span>
                                                    <span class="ml-1">{{ $model->project->name }}</span>
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        @if($model->task)
                                            <a href="{{ route('tasks.show', $model->task) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                <span class="font-mono text-xs font-semibold">TK-{{ $model->task_id }}</span>
                                                <span class="ml-1">{{ $model->task->name }}</span>
                                            </a>
                                        @elseif($model->project)
                                            <a href="{{ route('projects.show', $model->project) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                <span class="font-mono text-xs font-semibold">PJ-{{ $model->project_id }}</span>
                                                <span class="ml-1">{{ $model->project->name }}</span>
                                            </a>
                                        @else
                                            <span class="text-gray-400 text-xs">Khác</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-sm truncate">
                                    {{ $model->description ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold whitespace-nowrap {{ $isOt ? 'text-orange-600 dark:text-orange-400' : 'text-gray-800 dark:text-gray-200' }}">
                                    @if($isOt)
                                        {{ \App\Models\TimeLog::formatTime($model->hours) }}
                                        <span class="ml-1 text-xs font-medium text-orange-500 dark:text-orange-400">{{ $model->type }}</span>
                                    @else
                                        {{ $model->formatted_time }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($isOt)
                                            <a href="{{ route('overtime-requests.show', $model) }}" title="Xem OT"
                                                class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-orange-600 hover:border-orange-400 bg-white dark:bg-gray-700 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xem OT</span>
                                            </a>
                                        @else
                                            <a href="{{ route('time-logs.show', $model) }}" title="Xem"
                                                class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-blue-600 hover:border-blue-400 bg-white dark:bg-gray-700 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xem</span>
                                            </a>
                                            <a href="{{ route('time-logs.edit', $model) }}" title="Chỉnh sửa"
                                                class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Chỉnh sửa</span>
                                            </a>
                                            <form method="POST" action="{{ route('time-logs.destroy', $model) }}" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Xóa" onclick="return confirm('Delete this log?')"
                                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xóa</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $users ? 6 : 5 }}" class="px-6 py-6 text-center text-gray-500">Không tìm thấy nhật ký giờ.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $logs->links() }}</div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function setDateRange(range) {
            const now   = new Date();
            const year  = now.getFullYear();
            const month = now.getMonth(); // 0-indexed

            let from, to;
            if (range === 'this_month') {
                from = new Date(year, month, 1);
                to   = new Date(year, month + 1, 0);
            } else {
                from = new Date(year, month - 1, 1);
                to   = new Date(year, month, 0);
            }

            const fmt = d => d.toISOString().split('T')[0];
            document.getElementById('date_from').value = fmt(from);
            document.getElementById('date_to').value   = fmt(to);
        }
    </script>
    @endpush
</x-app-layout>
