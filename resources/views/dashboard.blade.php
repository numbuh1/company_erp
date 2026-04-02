@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet" />
    <style>
        .ql-editor { padding: 0; font-size: 0.875rem; }
        .ql-container.ql-snow { border: none; }
        .ql-editor img { max-width: 100%; border-radius: 0.375rem; }
        .ql-editor p, .ql-editor li { margin-bottom: 0.5rem; }
    </style>
@endpush

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ── Stats Bar ─────────────────────────────────────────────── --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase font-medium">Leave Balance</p>
                    <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-gray-100">
                        {{ rtrim(rtrim(number_format(auth()->user()->leave_balance ?? 0, 2), '0'), '.') }}h
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase font-medium">This Week</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                        {{ \App\Models\TimeLog::formatTime($weekTimeLogs) }}
                    </p>
                    <p class="text-xs text-gray-400">time logged</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase font-medium">This Month</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                        {{ \App\Models\TimeLog::formatTime($monthTimeLogs) }}
                    </p>
                    <p class="text-xs text-gray-400">time logged</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase font-medium">OT This Month</p>
                    <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ rtrim(rtrim(number_format($monthOTHours, 2), '0'), '.') }}h
                    </p>
                    <p class="text-xs text-gray-400">approved OT</p>
                </div>
            </div>

            {{-- ── Main content: Announcements (left) + Notifications (right) ── --}}
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                {{-- ── Left: Announcements ────────────────────────────────── --}}
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">
                                Latest Announcement
                            </h3>
                            @can('edit announcements')
                                <a href="{{ route('announcements.create') }}"
                                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">+ New</a>
                            @endcan
                        </div>

                        @if($latestAnnouncement)
                            <div>
                                <a href="{{ route('announcements.show', $latestAnnouncement) }}"
                                    class="font-semibold text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 text-base">
                                    {{ $latestAnnouncement->title }}
                                </a>
                                <p class="text-xs text-gray-400 mt-1 mb-3">
                                    {{ $latestAnnouncement->author?->name ?? 'System' }}
                                    · {{ $latestAnnouncement->created_at->format('d/m/Y') }}
                                </p>
                                <div class="ql-container ql-snow line-clamp-[8] overflow-hidden">
                                    <div class="ql-editor text-gray-700 dark:text-gray-300">
                                        {!! $latestAnnouncement->content !!}
                                    </div>
                                </div>
                                <a href="{{ route('announcements.show', $latestAnnouncement) }}"
                                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block">
                                    Read more →
                                </a>
                            </div>
                        @else
                            <p class="text-sm text-gray-400">No announcements yet.</p>
                        @endif
                    </div>

                    @if($previousAnnouncements->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-3">
                                Previous Announcements
                            </h3>
                            <ul class="space-y-2">
                                @foreach($previousAnnouncements as $prev)
                                    <li class="text-sm">
                                        <a href="{{ route('announcements.show', $prev) }}"
                                            class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline">
                                            {{ $prev->title }}
                                        </a>
                                        <span class="text-xs text-gray-400 ml-1">
                                            {{ $prev->created_at->format('d/m/Y') }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('announcements.index') }}"
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline mt-3 inline-block">
                                All announcements →
                            </a>
                        </div>
                    @else
                        <div class="text-right">
                            <a href="{{ route('announcements.index') }}"
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                All announcements →
                            </a>
                        </div>
                    @endif
                </div>

                {{-- ── Right: Notifications ───────────────────────────────── --}}
                <div class="lg:col-span-3 space-y-4">
                    {{-- Today's Attendance --}}
                    @if($attendanceStats)
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-4">
                                Today's Attendance
                                <span class="font-normal text-gray-400 normal-case">— {{ $attendanceStats['label'] }}</span>
                            </h3>

                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="text-center bg-gray-50 dark:bg-gray-700 rounded-lg py-3">
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $attendanceStats['present'] }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Present</p>
                                </div>
                                <!-- <div class="text-center bg-green-50 dark:bg-green-900/20 rounded-lg py-3">
                                    <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ $attendanceStats['total'] }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Total</p>
                                </div> -->
                                <div class="text-center bg-yellow-50 dark:bg-yellow-900/20 rounded-lg py-3">
                                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $attendanceStats['on_leave'] }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">On Leave</p>
                                </div>
                            </div>

                            @if($attendanceStats['on_leave_users']->isNotEmpty())
                                <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                                    <p class="text-xs font-semibold text-gray-400 uppercase mb-2">On leave today</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($attendanceStats['on_leave_users'] as $ou)
                                            <span class="inline-flex items-center gap-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 text-xs px-2 py-0.5 rounded">
                                                {{ $ou->name }}
                                                @if($ou->position)
                                                    <span class="text-yellow-600 dark:text-yellow-400 opacity-70">· {{ $ou->position }}</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Pending request counts (approvers only) --}}
                    @if($pendingLeavesCount !== null || $pendingOTCount !== null)
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-3">
                                Pending Approvals
                            </h3>
                            <div class="flex flex-wrap gap-3">
                                @if($pendingLeavesCount !== null)
                                    <a href="{{ route('leave-requests.index') }}"
                                        class="flex items-center gap-2 px-3 py-2 rounded-lg {{ $pendingLeavesCount > 0 ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700' : 'bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600' }} text-sm hover:opacity-80 transition">
                                        <span class="text-xl font-bold {{ $pendingLeavesCount > 0 ? 'text-yellow-600' : 'text-gray-400' }}">
                                            {{ $pendingLeavesCount }}
                                        </span>
                                        <span class="text-gray-600 dark:text-gray-300">Leave Requests Pending</span>
                                    </a>
                                @endif
                                @if($pendingOTCount !== null)
                                    <a href="{{ route('overtime-requests.index') }}"
                                        class="flex items-center gap-2 px-3 py-2 rounded-lg {{ $pendingOTCount > 0 ? 'bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700' : 'bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600' }} text-sm hover:opacity-80 transition">
                                        <span class="text-xl font-bold {{ $pendingOTCount > 0 ? 'text-orange-600' : 'text-gray-400' }}">
                                            {{ $pendingOTCount }}
                                        </span>
                                        <span class="text-gray-600 dark:text-gray-300">OT Requests Pending</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Upcoming Approved Leaves --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-3">
                            Upcoming Approved Leaves
                            <span class="font-normal text-gray-400 normal-case">(next 2 weeks)</span>
                        </h3>
                        @if($upcomingLeaves->isEmpty())
                            <p class="text-sm text-gray-400">No upcoming approved leaves.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($upcomingLeaves as $leave)
                                    @php
                                        $daysLeft = now()->startOfDay()->diffInDays($leave->start_at->startOfDay(), false);
                                    @endphp
                                    <div class="flex items-center gap-3 text-sm border-l-2 border-blue-300 pl-3 py-1">
                                        <div class="flex-1">
                                            <span class="font-medium text-gray-800 dark:text-gray-200">
                                                {{ $leave->user?->name }}
                                            </span>
                                            @if($leave->user?->position)
                                                <span class="text-xs text-gray-400 ml-1">{{ $leave->user->position }}</span>
                                            @endif
                                            <div class="text-xs text-gray-500">
                                                {{ $leave->start_at->format('d/m/Y') }}
                                                @if($leave->start_at->toDateString() !== $leave->end_at->toDateString())
                                                    – {{ $leave->end_at->format('d/m/Y') }}
                                                @endif
                                                · {{ rtrim(rtrim(number_format($leave->hours, 2), '0'), '.') }}h
                                            </div>
                                        </div>
                                        <span class="text-xs {{ $daysLeft <= 1 ? 'text-red-500' : ($daysLeft <= 3 ? 'text-yellow-600' : 'text-gray-400') }} shrink-0">
                                            {{ $daysLeft <= 0 ? 'Today' : ($daysLeft === 1 ? 'Tomorrow' : 'in ' . $daysLeft . 'd') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                                        {{-- Today's & This Week's Events --}}
                    @if($todayEvents->isNotEmpty() || $weekEvents->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">
                                    Events
                                </h3>
                                <a href="{{ route('calendar.index') }}"
                                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                    View calendar →
                                </a>
                            </div>

                            @if($todayEvents->isNotEmpty())
                                <p class="text-xs font-semibold text-gray-400 uppercase mb-2">Today</p>
                                <div class="space-y-2 mb-4">
                                    @foreach($todayEvents as $ev)
                                        @php
                                            $evColor = match($ev->event_type) {
                                                'interview'     => 'border-blue-400',
                                                'company_event' => 'border-emerald-400',
                                                default         => 'border-indigo-400',
                                            };
                                        @endphp
                                        <div class="flex items-start gap-3 text-sm border-l-2 {{ $evColor }} pl-3 py-1">
                                            <div class="w-20 shrink-0 text-xs text-gray-400">
                                                {{ $ev->start_at->format('H:i') }}<br>{{ $ev->end_at->format('H:i') }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-800 dark:text-gray-100 truncate">{{ $ev->name }}</p>
                                                @if($ev->location)
                                                    <p class="text-xs text-gray-400">📍 {{ $ev->location }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($weekEvents->isNotEmpty())
                                <p class="text-xs font-semibold text-gray-400 uppercase mb-2">Later this week</p>
                                <div class="space-y-2">
                                    @foreach($weekEvents as $ev)
                                        @php
                                            $evColor = match($ev->event_type) {
                                                'interview'     => 'border-blue-400',
                                                'company_event' => 'border-emerald-400',
                                                default         => 'border-indigo-400',
                                            };
                                        @endphp
                                        <div class="flex items-start gap-3 text-sm border-l-2 {{ $evColor }} pl-3 py-1">
                                            <div class="w-20 shrink-0 text-xs text-gray-400">
                                                {{ $ev->start_at->format('D d/m') }}<br>{{ $ev->start_at->format('H:i') }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-800 dark:text-gray-100 truncate">{{ $ev->name }}</p>
                                                @if($ev->location)
                                                    <p class="text-xs text-gray-400">📍 {{ $ev->location }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Tasks nearing deadline --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide mb-3">
                            Tasks Nearing Deadline
                            <span class="font-normal text-gray-400 normal-case">(due within 5 days)</span>
                        </h3>
                        @if($deadlineTasks->isEmpty())
                            <p class="text-sm text-gray-400">No urgent tasks.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($deadlineTasks as $task)
                                    @php
                                        $daysLeft = now()->startOfDay()->diffInDays($task->expected_end_date, false);
                                    @endphp
                                    <div class="flex items-center gap-3 p-2 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                        <a href="{{ route('tasks.show', $task) }}"
                                            class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400 shrink-0">
                                            TK-{{ $task->id }}
                                        </a>
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('tasks.show', $task) }}"
                                                class="text-sm font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 truncate block">
                                                {{ $task->name }}
                                            </a>
                                            @if($task->project)
                                                <span class="text-xs text-gray-400">
                                                    <span class="font-mono">PJ-{{ $task->project_id }}</span>
                                                    {{ $task->project->name }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <span class="text-xs font-semibold {{ $daysLeft === 0 ? 'text-red-600' : ($daysLeft === 1 ? 'text-orange-500' : 'text-yellow-600') }}">
                                                {{ $daysLeft === 0 ? 'Due today' : ($daysLeft === 1 ? 'Due tomorrow' : 'Due in ' . $daysLeft . 'd') }}
                                            </span>
                                            <div class="text-xs text-gray-400">{{ $task->expected_end_date->format('d/m/Y') }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
