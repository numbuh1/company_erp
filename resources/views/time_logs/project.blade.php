<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Timesheet Dự án</h2>
            <a href="{{ route('time-logs.create') }}"><x-primary-button>Chấm công</x-primary-button></a>
        </div>
    </x-slot>

    @push('styles')
    <style>
        [x-cloak] { display: none !important; }

        /* ── Sticky columns ─────────────────────────────────────────── */
        .pj-c1 { position: sticky; left: 0;      z-index: 3; }
        .pj-c2 { position: sticky; left: 200px;  z-index: 3; border-right: 1px solid; }
        thead .pj-c1, thead .pj-c2 { z-index: 5; }
        .pj-c2,       thead .pj-c2 { border-right-color: #d1d5db; }
        .dark .pj-c2, .dark thead .pj-c2 { border-right-color: #4b5563; }

        /* ── Sticky header row ──────────────────────────────────────── */
        thead th { position: sticky; top: 0; z-index: 4; }
        thead .pj-c1, thead .pj-c2 { z-index: 6; }

        /* ── Always-visible scrollbars ──────────────────────────────── */
        .pj-scroll { scrollbar-width: thin; scrollbar-color: #94a3b8 #e2e8f0; }
        .dark .pj-scroll { scrollbar-color: #4b5563 #1e293b; }
        .pj-scroll::-webkit-scrollbar        { width: 8px; height: 8px; }
        .pj-scroll::-webkit-scrollbar-track  { background: #e2e8f0; border-radius: 4px; }
        .pj-scroll::-webkit-scrollbar-thumb  { background: #94a3b8; border-radius: 4px; }
        .pj-scroll::-webkit-scrollbar-thumb:hover { background: #64748b; }
        .pj-scroll::-webkit-scrollbar-corner { background: #e2e8f0; }
        .dark .pj-scroll::-webkit-scrollbar-track  { background: #1e293b; }
        .dark .pj-scroll::-webkit-scrollbar-thumb  { background: #4b5563; }
        .dark .pj-scroll::-webkit-scrollbar-thumb:hover { background: #6b7280; }
        .dark .pj-scroll::-webkit-scrollbar-corner { background: #1e293b; }
    </style>
    @endpush

    <div x-data="initPjView()" class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-3 py-4">

        {{-- ── Tabs ──────────────────────────────────────────────────── --}}
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex gap-1">
                <a href="{{ route('time-logs.index') }}"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                        border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                    Danh sách
                </a>
                <a href="{{ route('timesheets.timeline') }}"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                        border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                    Theo ngày
                </a>
                <a href="{{ route('timesheets.project') }}"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                        border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400">
                    Theo dự án
                </a>
                <a href="{{ route('timesheets.calendar') }}"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                        border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                    Lịch
                </a>
            </nav>
        </div>

        {{-- ── Filter bar ──────────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg px-4 py-3">
            <form method="GET" action="{{ route('timesheets.project') }}"
                  class="flex flex-wrap gap-3 items-end">

                {{-- Date range --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Từ ngày</label>
                    <input type="date" name="from_date" value="{{ $fromDate }}"
                        class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded text-sm px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Đến ngày</label>
                    <input type="date" name="to_date" value="{{ $toDate }}"
                        class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded text-sm px-2 py-1.5">
                </div>

                {{-- Quick date buttons --}}
                @php
                    $thisWeek  = ['from' => now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'), 'to' => now()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d')];
                    $lastWeek  = ['from' => now()->subWeek()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'), 'to' => now()->subWeek()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d')];
                    $thisMonth = ['from' => now()->startOfMonth()->format('Y-m-d'), 'to' => now()->endOfMonth()->format('Y-m-d')];
                    $lastMonth = ['from' => now()->subMonth()->startOfMonth()->format('Y-m-d'), 'to' => now()->subMonth()->endOfMonth()->format('Y-m-d')];
                @endphp
                <div class="flex gap-1 self-end pb-0.5">
                    @foreach([
                        ['label' => 'Tuần này',   'f' => $thisWeek['from'],  't' => $thisWeek['to']],
                        ['label' => 'Tuần trước', 'f' => $lastWeek['from'],  't' => $lastWeek['to']],
                        ['label' => 'Tháng này',  'f' => $thisMonth['from'], 't' => $thisMonth['to']],
                        ['label' => 'Tháng trước','f' => $lastMonth['from'], 't' => $lastMonth['to']],
                    ] as $q)
                        @php $isActive = $fromDate === $q['f'] && $toDate === $q['t']; @endphp
                        <button type="button"
                            onclick="document.querySelector('[name=from_date]').value='{{ $q['f'] }}'; document.querySelector('[name=to_date]').value='{{ $q['t'] }}';"
                            class="px-2 py-1 text-xs rounded border transition
                                {{ $isActive
                                    ? 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-300 text-indigo-700 dark:text-indigo-300'
                                    : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            {{ $q['label'] }}
                        </button>
                    @endforeach
                </div>

                {{-- Project multi-select --}}
                @if($availableProjects->isNotEmpty())
                <div class="min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Dự án</label>
                    <select name="project_ids[]" multiple data-multi-select data-placeholder="Tất cả dự án"
                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md text-sm">
                        @foreach($availableProjects as $p)
                            <option value="{{ $p->id }}" {{ in_array($p->id, $filterProjectIds) ? 'selected' : '' }}>
                                PJ-{{ $p->id }} · {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Task multi-select --}}
                @if($availableTasks->isNotEmpty())
                <div class="min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Công việc</label>
                    <select name="task_ids[]" multiple data-multi-select data-placeholder="Tất cả công việc"
                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md text-sm">
                        @foreach($availableTasks as $t)
                            <option value="{{ $t->id }}" {{ in_array($t->id, $filterTaskIds) ? 'selected' : '' }}>
                                TK-{{ $t->id }} · {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- User multi-select --}}
                @if($availableUsers && $availableUsers->isNotEmpty())
                <div class="min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Thành viên</label>
                    <select name="user_ids[]" multiple data-multi-select data-placeholder="Tất cả thành viên"
                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md text-sm">
                        @foreach($availableUsers as $u)
                            <option value="{{ $u->id }}" {{ in_array($u->id, $filterUserIds) ? 'selected' : '' }}>
                                {{ $u->name }}{{ $u->position ? ' · ' . $u->position : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="self-end flex gap-2">
                    <button type="submit"
                        class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        Áp dụng
                    </button>
                    <a href="{{ route('timesheets.project') }}"
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Đặt lại
                    </a>
                </div>
            </form>
        </div>

        {{-- ── Expand / Collapse controls ──────────────────────────────── --}}
        @if(!empty($projectGroups))
        <div class="flex items-center gap-3">
            <button type="button" @click="expandAll()"
                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                Mở rộng tất cả
            </button>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <button type="button" @click="collapseAll()"
                class="text-xs text-gray-500 dark:text-gray-400 hover:underline">
                Thu gọn tất cả
            </button>
            <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">
                {{ count($projectGroups) }} dự án
                · {{ $days->count() }} ngày
                @if($grandTotalHours > 0)
                    · <span class="font-medium text-gray-600 dark:text-gray-300">{{ number_format($grandTotalHours, 1) }}h</span>
                @endif
                @if($grandTotalOt > 0)
                    + <span class="text-orange-500">{{ number_format($grandTotalOt, 1) }}h OT</span>
                @endif
            </span>
        </div>
        @endif

        {{-- ── Table ──────────────────────────────────────────────────── --}}
        @if(empty($projectGroups))
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-10 text-center text-gray-400 text-sm">
                Không có dữ liệu trong khoảng thời gian được chọn.
            </div>
        @else
        <div class="pj-scroll bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-auto max-h-[calc(100vh-10rem)]">
            <table class="text-xs border-collapse" style="table-layout:fixed; width:max-content; min-width:100%">
                <colgroup>
                    <col style="width:200px">
                    <col style="width:88px">
                    @foreach($days as $__)
                        <col style="width:56px">
                    @endforeach
                </colgroup>

                {{-- ── Header ── --}}
                <thead>
                    <tr>
                        <th class="pj-c1 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Mục
                        </th>
                        <th class="pj-c2 bg-gray-200 dark:bg-gray-600 px-2 py-2 text-center text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">
                            Tổng
                        </th>
                        @foreach($days as $day)
                            @php
                                $dk     = $day->format('Y-m-d');
                                $isHol  = in_array($dk, $holidayDates);
                                $isWknd = $day->isWeekend();
                                $hCls   = $day->isToday()
                                    ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                                    : ($isHol || $isWknd
                                        ? 'bg-red-50 dark:bg-red-900/20 text-red-500 dark:text-red-400'
                                        : 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400');
                            @endphp
                            <th class="px-0.5 py-2 text-center font-medium {{ $hCls }}">
                                <div class="font-semibold">{{ $day->format('d') }}</div>
                                <div class="text-[10px] font-normal opacity-70">{{ $day->translatedFormat('D') }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>

                @foreach($projectGroups as $pk => $pg)
                    @php
                        $pId    = $pg['project_id'];
                        $pName  = $pg['project']?->name ?? 'PJ-' . $pId;
                        $pLink  = $pg['project'] ? route('projects.show', $pId) : null;
                    @endphp

                    {{-- ── Project row ── --}}
                    <tr class="border-t-2 border-gray-300 dark:border-gray-500">
                        <td class="pj-c1 bg-indigo-50 dark:bg-indigo-900/25 px-2 py-1.5">
                            <div class="flex items-center gap-1.5 min-w-0">
                                {{-- Expand/collapse toggle --}}
                                <button type="button" @click="toggleProject('{{ $pk }}')"
                                    class="shrink-0 w-4 h-4 text-indigo-400 dark:text-indigo-400 hover:text-indigo-600 transition">
                                    <svg x-show="openProjects['{{ $pk }}']" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                    <svg x-show="!openProjects['{{ $pk }}']" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                                <span class="font-mono text-[10px] font-bold text-indigo-500 dark:text-indigo-400 shrink-0">PJ-{{ $pId }}</span>
                                @if($pLink)
                                    <a href="{{ $pLink }}" class="font-semibold text-indigo-700 dark:text-indigo-300 hover:underline truncate">{{ $pName }}</a>
                                @else
                                    <span class="font-semibold text-indigo-700 dark:text-indigo-300 truncate">{{ $pName }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="pj-c2 bg-indigo-100 dark:bg-indigo-900/40 px-2 py-1.5 text-center font-bold">
                            @if($pg['total_hours'] > 0)
                                <div class="text-indigo-700 dark:text-indigo-300">{{ number_format($pg['total_hours'], 1) }}h</div>
                            @endif
                            @if($pg['total_ot'] > 0)
                                <div class="text-orange-500 font-semibold">+{{ number_format($pg['total_ot'], 1) }}h</div>
                            @endif
                        </td>
                        @foreach($days as $day)
                            @php
                                $dk    = $day->format('Y-m-d');
                                $dCell = $pg['days'][$dk] ?? null;
                                $wkBg  = (in_array($dk, $holidayDates) || $day->isWeekend()) ? 'bg-red-50/50 dark:bg-red-900/10' : 'bg-indigo-50/40 dark:bg-indigo-900/10';
                            @endphp
                            <td class="px-0.5 py-1.5 text-center {{ $wkBg }}">
                                @if($dCell && ($dCell['hours'] + $dCell['ot_hours'] > 0))
                                    @if($dCell['hours'] > 0)<div class="font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format($dCell['hours'], 1) }}h</div>@endif
                                    @if($dCell['ot_hours'] > 0)<div class="text-orange-500">+{{ number_format($dCell['ot_hours'], 1) }}h</div>@endif
                                @endif
                            </td>
                        @endforeach
                    </tr>

                    {{-- ── Task rows (under this project) ── --}}
                    @foreach($pg['tasks'] as $tk => $tg)
                        @php
                            $openKey = $pk . '_' . $tk;
                            $tId     = $tg['task_id'];
                            $tLink   = $tg['task'] ? route('tasks.show', $tId) : null;
                            $tLabel  = $tg['task']?->name ?? '(Không có công việc)';
                        @endphp

                        <tr x-show="openProjects['{{ $pk }}']" x-cloak
                            class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="pj-c1 bg-white dark:bg-gray-800 px-2 py-1.5 pl-7">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    {{-- Task toggle --}}
                                    <button type="button" @click="toggleTask('{{ $openKey }}')"
                                        class="shrink-0 w-3.5 h-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                                        <svg x-show="openTasks['{{ $openKey }}']" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                        <svg x-show="!openTasks['{{ $openKey }}']" x-cloak class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                    @if($tId)
                                        <span class="font-mono text-[10px] font-semibold text-gray-400 shrink-0">TK-{{ $tId }}</span>
                                    @endif
                                    @if($tLink)
                                        <a href="{{ $tLink }}" class="text-gray-700 dark:text-gray-300 hover:underline truncate">{{ $tLabel }}</a>
                                    @else
                                        <span class="text-gray-400 italic truncate">{{ $tLabel }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="pj-c2 bg-gray-50 dark:bg-gray-750 px-2 py-1.5 text-center">
                                @if($tg['total_hours'] > 0)
                                    <div class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($tg['total_hours'], 1) }}h</div>
                                @endif
                                @if($tg['total_ot'] > 0)
                                    <div class="text-orange-500">+{{ number_format($tg['total_ot'], 1) }}h</div>
                                @endif
                            </td>
                            @foreach($days as $day)
                                @php
                                    $dk    = $day->format('Y-m-d');
                                    $dCell = $tg['days'][$dk] ?? null;
                                    $wkBg  = (in_array($dk, $holidayDates) || $day->isWeekend()) ? 'bg-red-50/40 dark:bg-red-900/10' : '';
                                    $url   = route('time-logs.index', array_filter([
                                        'project_id' => $pId,
                                        'task_id'    => $tId ?: null,
                                        'date_from'  => $dk, 'date_to' => $dk,
                                    ]));
                                @endphp
                                <td class="px-0.5 py-1.5 text-center {{ $wkBg }}">
                                    @if($dCell && ($dCell['hours'] + $dCell['ot_hours'] > 0))
                                        <a href="{{ $url }}" class="block rounded hover:bg-indigo-50 dark:hover:bg-indigo-900/20 px-0.5 transition">
                                            @if($dCell['hours'] > 0)<div class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($dCell['hours'], 1) }}h</div>@endif
                                            @if($dCell['ot_hours'] > 0)<div class="text-orange-500">+{{ number_format($dCell['ot_hours'], 1) }}h</div>@endif
                                        </a>
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        {{-- ── User rows (under this task) ── --}}
                        @foreach($tg['users'] as $uk => $ug)
                            @php
                                $uId   = $ug['user_id'];
                                $uName = $ug['user']?->name ?? '#' . $uId;
                                $uLink = $ug['user'] ? route('users.show', $uId) : null;
                            @endphp
                            <tr x-show="openProjects['{{ $pk }}'] && openTasks['{{ $openKey }}']" x-cloak
                                class="border-t border-gray-100 dark:border-gray-700/50 hover:bg-gray-50/80 dark:hover:bg-gray-700/20 transition">
                                <td class="pj-c1 bg-white dark:bg-gray-800 px-2 py-1 pl-14">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="text-gray-400 dark:text-gray-600 shrink-0 text-[10px]">↳</span>
                                        @if($uLink)
                                            <a href="{{ $uLink }}" class="text-gray-600 dark:text-gray-400 hover:underline truncate">{{ $uName }}</a>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-500 truncate">{{ $uName }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="pj-c2 bg-gray-50/50 dark:bg-gray-800 px-2 py-1 text-center">
                                    @if($ug['total_hours'] > 0)
                                        <div class="text-gray-600 dark:text-gray-400">{{ number_format($ug['total_hours'], 1) }}h</div>
                                    @endif
                                    @if($ug['total_ot'] > 0)
                                        <div class="text-orange-500 text-[11px]">+{{ number_format($ug['total_ot'], 1) }}h</div>
                                    @endif
                                </td>
                                @foreach($days as $day)
                                    @php
                                        $dk    = $day->format('Y-m-d');
                                        $dCell = $ug['days'][$dk] ?? null;
                                        $wkBg  = (in_array($dk, $holidayDates) || $day->isWeekend()) ? 'bg-red-50/30 dark:bg-red-900/10' : '';
                                        $url   = route('time-logs.index', array_filter([
                                            'project_id' => $pId,
                                            'task_id'    => $tId ?: null,
                                            'user_id'    => $uId,
                                            'date_from'  => $dk, 'date_to' => $dk,
                                        ]));
                                    @endphp
                                    <td class="px-0.5 py-1 text-center {{ $wkBg }}">
                                        @if($dCell && ($dCell['hours'] + $dCell['ot_hours'] > 0))
                                            <a href="{{ $url }}" class="block rounded hover:bg-indigo-50 dark:hover:bg-indigo-900/20 px-0.5 transition">
                                                @if($dCell['hours'] > 0)<div class="text-gray-600 dark:text-gray-400">{{ number_format($dCell['hours'], 1) }}h</div>@endif
                                                @if($dCell['ot_hours'] > 0)<div class="text-orange-500 text-[11px]">+{{ number_format($dCell['ot_hours'], 1) }}h</div>@endif
                                            </a>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                    @endforeach {{-- tasks --}}

                @endforeach {{-- projects --}}

                {{-- ── Grand total row ── --}}
                @if($grandTotalHours > 0 || $grandTotalOt > 0)
                <tr class="border-t-2 border-gray-300 dark:border-gray-500 bg-gray-100 dark:bg-gray-700">
                    <td class="pj-c1 bg-gray-100 dark:bg-gray-700 px-3 py-2 font-bold text-gray-700 dark:text-gray-200 uppercase text-[11px] tracking-wide">
                        Tổng cộng
                    </td>
                    <td class="pj-c2 bg-gray-200 dark:bg-gray-600 px-2 py-2 text-center font-bold">
                        @if($grandTotalHours > 0)
                            <div class="text-gray-800 dark:text-gray-100">{{ number_format($grandTotalHours, 1) }}h</div>
                        @endif
                        @if($grandTotalOt > 0)
                            <div class="text-orange-500">+{{ number_format($grandTotalOt, 1) }}h</div>
                        @endif
                    </td>
                    @foreach($days as $day)
                        @php
                            $dk  = $day->format('Y-m-d');
                            $tot = $dayTotals[$dk] ?? ['hours' => 0, 'ot_hours' => 0];
                            $wkBg = (in_array($dk, $holidayDates) || $day->isWeekend()) ? 'bg-red-50/40 dark:bg-red-900/10' : '';
                        @endphp
                        <td class="px-0.5 py-2 text-center {{ $wkBg }}">
                            @if($tot['hours'] > 0)
                                <div class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($tot['hours'], 1) }}h</div>
                            @endif
                            @if($tot['ot_hours'] > 0)
                                <div class="text-orange-500">+{{ number_format($tot['ot_hours'], 1) }}h</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @endif

                </tbody>
            </table>
        </div>
        @endif

    </div>{{-- /x-data --}}

    @push('scripts')
    <script>
        function initPjView() {
            return {
                openProjects: @json($initOpenProjects),
                openTasks:    @json($initOpenTasks),
                toggleProject(k) { this.openProjects[k] = !this.openProjects[k]; },
                toggleTask(k)    { this.openTasks[k]    = !this.openTasks[k]; },
                expandAll() {
                    for (const k in this.openProjects) this.openProjects[k] = true;
                    for (const k in this.openTasks)    this.openTasks[k]    = true;
                },
                collapseAll() {
                    for (const k in this.openProjects) this.openProjects[k] = false;
                    for (const k in this.openTasks)    this.openTasks[k]    = false;
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
