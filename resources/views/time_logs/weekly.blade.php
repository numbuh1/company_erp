@php
    $colCount = 2 + count($days); // label + total + N days
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Bảng giờ theo ngày</h2>
            <a href="{{ route('time-logs.create') }}"><x-primary-button>Chấm công</x-primary-button></a>
        </div>
    </x-slot>

    @push('styles')
    <style>
        [x-cloak] { display: none !important; }
        /* Sticky columns */
        .ts-col-label { position: sticky; left: 0; z-index: 3; }
        .ts-col-total { position: sticky; left: 14rem; z-index: 3; border-right: 1px solid; }
        /* Header sticky columns */
        thead .ts-col-label { background-color: var(--tw-bg-opacity, 1); }
        /* Cell border colour for the total separator */
        .dark .ts-col-total { border-right-color: #4b5563; }
        .ts-col-total       { border-right-color: #d1d5db; }
    </style>
    @endpush

    <div x-data="{
        showContext: true,
        showUser:    true
    }">

        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-3 py-4">

            {{-- ── Tabs ─────────────────────────────────────────────────── --}}
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex gap-1">
                    <a href="{{ route('time-logs.index') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        Danh sách
                    </a>
                    <a href="{{ route('timesheets.timeline') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400">
                        Theo ngày
                    </a>
                    {{-- Temporarily hidden: Theo dự án --}}
                    <a href="{{ route('timesheets.calendar') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        Lịch
                    </a>
                </nav>
            </div>

            {{-- ── Filter bar ───────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg px-4 py-3">
                <form method="GET" action="{{ route('timesheets.timeline') }}"
                      class="flex flex-wrap gap-3 items-end">

                    {{-- Date range --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Từ ngày</label>
                        <input type="date" name="ts_from" value="{{ $tsFrom }}"
                            class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded text-sm px-2 py-1.5 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Đến ngày</label>
                        <input type="date" name="ts_to" value="{{ $tsTo }}"
                            class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded text-sm px-2 py-1.5 cursor-pointer">
                    </div>

                    {{-- User multi-select --}}
                    @if($filterUsers && $filterUsers->isNotEmpty())
                    <div class="min-w-[180px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Thành viên</label>
                        <select name="user_ids[]" multiple data-multi-select data-placeholder="Tất cả thành viên"
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md text-sm">
                            @foreach($filterUsers as $fu)
                                <option value="{{ $fu->id }}"
                                    {{ in_array($fu->id, $selectedUserIds) ? 'selected' : '' }}>
                                    {{ $fu->name }}{{ $fu->position ? ' · ' . $fu->position : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Team multi-select --}}
                    @if($filterTeams && $filterTeams->isNotEmpty())
                    <div class="min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Nhóm</label>
                        <select name="team_ids[]" multiple data-multi-select data-placeholder="Tất cả nhóm"
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md text-sm">
                            @foreach($filterTeams as $ft)
                                <option value="{{ $ft->id }}"
                                    {{ in_array($ft->id, $selectedTeamIds) ? 'selected' : '' }}>
                                    {{ $ft->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Project multi-select --}}
                    @if($availableProjects->isNotEmpty())
                    <div class="min-w-[180px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Dự án</label>
                        <select name="project_ids[]" multiple data-multi-select data-placeholder="Tất cả dự án"
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md text-sm">
                            @foreach($availableProjects as $ap)
                                <option value="{{ $ap->id }}"
                                    {{ in_array($ap->id, $filterProjectIds) ? 'selected' : '' }}>
                                    {{ $ap->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Task multi-select --}}
                    @if($availableTasks->isNotEmpty())
                    <div class="min-w-[180px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Công việc</label>
                        <select name="task_ids[]" multiple data-multi-select data-placeholder="Tất cả công việc"
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md text-sm">
                            @foreach($availableTasks as $at)
                                <option value="{{ $at->id }}"
                                    {{ in_array($at->id, $filterTaskIds) ? 'selected' : '' }}>
                                    {{ $at->name }}
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
                        <a href="{{ route('timesheets.timeline') }}"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Đặt lại
                        </a>
                    </div>
                </form>
            </div>

            {{-- ── Group View checkboxes (multi-user only) ──────────────── --}}
            @if($isMultiUser)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg px-4 py-2.5 flex items-center gap-5">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    Group View
                </span>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="showContext"
                        class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Theo công việc</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="showUser"
                        class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Theo từng người</span>
                </label>
            </div>
            @endif

            {{-- ── Main grid (single table, sticky first 2 cols) ────────── --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm border-separate border-spacing-0">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            {{-- Sticky col 1: Label --}}
                            <th class="ts-col-label bg-gray-50 dark:bg-gray-700 px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-56 min-w-[14rem]">
                                Công việc / Người
                            </th>
                            {{-- Sticky col 2: Total (darker) --}}
                            <th class="ts-col-total bg-gray-200 dark:bg-gray-600 px-2 py-2 text-center text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase w-16 min-w-[4rem]">
                                Tổng
                            </th>
                            {{-- Day columns --}}
                            @foreach($days as $day)
                                @php
                                    $isHolidayDay = in_array($day->format('Y-m-d'), $holidayDates);
                                    $isWeekendDay = $day->isWeekend();
                                @endphp
                                <th class="px-1 py-2 text-center text-xs font-medium uppercase w-20 min-w-[5rem]
                                    {{ $day->isToday()
                                        ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950'
                                        : ($isHolidayDay ? $calHolidayHeaderCls
                                            : ($isWeekendDay ? $calWeekendHeaderCls : 'text-gray-500 bg-gray-50 dark:bg-gray-700')) }}">
                                    {{ $day->translatedFormat('D') }}<br>
                                    <span class="font-normal normal-case">{{ $day->format('d/m') }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                        @php
                            $hasAnyRows = !empty($rowsByContext) || !empty($rowsByUser);
                        @endphp

                        @if(!$hasAnyRows)
                            <tr>
                                <td colspan="{{ $colCount }}" class="px-6 py-10 text-center text-gray-400 bg-white dark:bg-gray-800">
                                    Chưa có giờ làm việc nào trong khoảng thời gian được chọn.
                                    <a href="{{ route('time-logs.create') }}" class="text-indigo-600 hover:underline ml-1">Chấm công →</a>
                                </td>
                            </tr>
                        @else

                            {{-- ── Context section ─────────────────────── --}}
                            @if($isMultiUser)
                            <tr x-show="showContext" x-cloak>
                                <td colspan="{{ $colCount }}"
                                    class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                                    📋 Theo công việc
                                </td>
                            </tr>
                            @endif

                            @foreach($rowsByContext as $row)
                            <tr class="{{ $isMultiUser ? 'x-show-context' : '' }} group hover:bg-gray-50 dark:hover:bg-gray-750 transition"
                                @if($isMultiUser) x-show="showContext" x-cloak @endif>

                                {{-- Label (sticky col 1) --}}
                                <td class="ts-col-label bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-750 px-3 py-2">
                                    @if($row['link'])
                                        <a href="{{ $row['link'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium text-sm">
                                            {{ $row['label'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-600 dark:text-gray-400 font-medium text-sm">{{ $row['label'] }}</span>
                                    @endif
                                </td>

                                {{-- Total (sticky col 2, darker) --}}
                                <td class="ts-col-total bg-gray-100 dark:bg-gray-700 group-hover:bg-gray-200 dark:group-hover:bg-gray-600 px-2 py-2 text-center">
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-100">
                                        {{ \App\Models\TimeLog::formatTimeShort($row['total']) }}
                                    </span>
                                </td>

                                {{-- Day cells --}}
                                @foreach($days as $day)
                                    @php
                                        $dayKey       = $day->format('Y-m-d');
                                        $cell         = $row['days'][$dayKey] ?? null;
                                        $isHolidayDay = in_array($dayKey, $holidayDates);
                                        $isWeekendDay = $day->isWeekend();
                                    @endphp
                                    <td class="px-1 py-1 text-center
                                        {{ $day->isToday() ? 'bg-indigo-50 dark:bg-indigo-950'
                                            : ($isHolidayDay ? $calHolidayBg
                                                : ($isWeekendDay ? $calWeekendBg : '')) }}">
                                        @if($cell)
                                            @php
                                                $tooltip = implode("\n", array_filter($cell['descriptions']));
                                                $params  = ['date_from' => $dayKey, 'date_to' => $dayKey];
                                                if ($row['type'] === 'task')        $params['task_id']    = $row['task_id'];
                                                elseif ($row['type'] === 'project') $params['project_id'] = $row['project_id'];
                                                else                                $params['no_context'] = 1;
                                                $cellUrl = route('time-logs.index', $params);
                                            @endphp
                                            <div x-data="{ open: false }" class="relative inline-block"
                                                @mouseenter="open = true" @mouseleave="open = false">
                                                <a href="{{ $cellUrl }}"
                                                    class="block text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline px-1 py-1 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900 transition">
                                                    {{ \App\Models\TimeLog::formatTimeShort($cell['total']) }}
                                                </a>
                                                @if($tooltip)
                                                <div x-show="open" x-cloak
                                                    class="absolute z-30 bottom-full left-1/2 -translate-x-1/2 mb-2 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 shadow-xl pointer-events-none min-w-max max-w-xs text-left"
                                                    style="white-space: pre-wrap;">{{ $tooltip }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @endforeach

                            {{-- ── User section (only in multi-user view) ── --}}
                            @if($isMultiUser)
                            <tr x-show="showUser" x-cloak>
                                <td colspan="{{ $colCount }}"
                                    class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950 text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                                    👤 Theo từng người
                                </td>
                            </tr>

                            @foreach($rowsByUser as $row)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-750 transition"
                                x-show="showUser" x-cloak>

                                {{-- Label (sticky col 1) --}}
                                <td class="ts-col-label bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-750 px-3 py-2">
                                    @if($row['link'])
                                        <a href="{{ $row['link'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium text-sm">
                                            {{ $row['label'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-600 dark:text-gray-400 font-medium text-sm">{{ $row['label'] }}</span>
                                    @endif
                                </td>

                                {{-- Total (sticky col 2) --}}
                                <td class="ts-col-total bg-gray-100 dark:bg-gray-700 group-hover:bg-gray-200 dark:group-hover:bg-gray-600 px-2 py-2 text-center">
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-100">
                                        {{ \App\Models\TimeLog::formatTimeShort($row['total']) }}
                                    </span>
                                </td>

                                {{-- Day cells --}}
                                @foreach($days as $day)
                                    @php
                                        $dayKey       = $day->format('Y-m-d');
                                        $cell         = $row['days'][$dayKey] ?? null;
                                        $isHolidayDay = in_array($dayKey, $holidayDates);
                                        $isWeekendDay = $day->isWeekend();
                                    @endphp
                                    <td class="px-1 py-1 text-center
                                        {{ $day->isToday() ? 'bg-indigo-50 dark:bg-indigo-950'
                                            : ($isHolidayDay ? $calHolidayBg
                                                : ($isWeekendDay ? $calWeekendBg : '')) }}">
                                        @if($cell)
                                            @php
                                                $tooltip = implode("\n", array_filter($cell['descriptions']));
                                                $cellUrl = route('time-logs.index', [
                                                    'date_from' => $dayKey,
                                                    'date_to'   => $dayKey,
                                                    'user_id'   => $row['user_id'],
                                                ]);
                                            @endphp
                                            <div x-data="{ open: false }" class="relative inline-block"
                                                @mouseenter="open = true" @mouseleave="open = false">
                                                <a href="{{ $cellUrl }}"
                                                    class="block text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline px-1 py-1 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900 transition">
                                                    {{ \App\Models\TimeLog::formatTimeShort($cell['total']) }}
                                                </a>
                                                @if($tooltip)
                                                <div x-show="open" x-cloak
                                                    class="absolute z-30 bottom-full left-1/2 -translate-x-1/2 mb-2 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 shadow-xl pointer-events-none min-w-max max-w-xs text-left"
                                                    style="white-space: pre-wrap;">{{ $tooltip }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @endforeach
                            @endif {{-- isMultiUser --}}

                        @endif {{-- hasAnyRows --}}

                    </tbody>

                    {{-- Footer total row --}}
                    @if($hasAnyRows ?? false)
                    <tfoot class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <td class="ts-col-label bg-gray-50 dark:bg-gray-700 px-3 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">
                                Tổng cộng
                            </td>
                            <td class="ts-col-total bg-gray-200 dark:bg-gray-600 px-2 py-2 text-center">
                                <span class="text-xs font-bold text-gray-900 dark:text-white">
                                    {{ \App\Models\TimeLog::formatTimeShort($weekTotal) }}
                                </span>
                            </td>
                            @foreach($days as $day)
                                @php
                                    $dk           = $day->format('Y-m-d');
                                    $isHolidayDay = in_array($dk, $holidayDates);
                                    $isWeekendDay = $day->isWeekend();
                                @endphp
                                <td class="px-1 py-2 text-center
                                    {{ $day->isToday() ? 'bg-indigo-50 dark:bg-indigo-950'
                                        : ($isHolidayDay ? $calHolidayBg
                                            : ($isWeekendDay ? $calWeekendBg : '')) }}">
                                    @if(($dayTotals[$dk] ?? 0) > 0)
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            {{ \App\Models\TimeLog::formatTimeShort($dayTotals[$dk]) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>{{-- /overflow-x-auto --}}

        </div>
    </div>{{-- /x-data --}}
</x-app-layout>
