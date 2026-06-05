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

        /* ── Sticky columns (horizontal) ───────────────────────────────── */
        .ts-col-label { position: sticky; left: 0; z-index: 3; }
        .ts-col-total { position: sticky; left: 14rem; z-index: 3; border-right: 1px solid; }

        /* ── Sticky header row (vertical) ──────────────────────────────── */
        thead th { position: sticky; top: 0; z-index: 4; }

        /* Corner cells: sticky in both directions — highest z-index */
        thead .ts-col-label,
        thead .ts-col-total { z-index: 6; }

        /* ── Total separator border colour ─────────────────────────────── */
        .ts-col-total       { border-right-color: #d1d5db; }
        .dark .ts-col-total { border-right-color: #4b5563; }

        /* ── Always-visible styled scrollbars ──────────────────────────── *
         * Setting a non-zero ::-webkit-scrollbar size on macOS switches    *
         * the element from overlay scrollbars to classic (always visible)  *
         * scrollbars, matching Windows/Linux behaviour.                    */
        .ts-scroll {
            /* Firefox */
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #e2e8f0;
        }
        .dark .ts-scroll {
            scrollbar-color: #4b5563 #1e293b;
        }

        /* Chrome / Safari / Edge */
        .ts-scroll::-webkit-scrollbar        { width: 8px; height: 8px; }
        .ts-scroll::-webkit-scrollbar-track  { background: #e2e8f0; border-radius: 4px; }
        .ts-scroll::-webkit-scrollbar-thumb  { background: #94a3b8; border-radius: 4px; }
        .ts-scroll::-webkit-scrollbar-thumb:hover { background: #64748b; }
        .ts-scroll::-webkit-scrollbar-corner { background: #e2e8f0; }

        /* Dark variants */
        .dark .ts-scroll::-webkit-scrollbar-track  { background: #1e293b; }
        .dark .ts-scroll::-webkit-scrollbar-thumb  { background: #4b5563; }
        .dark .ts-scroll::-webkit-scrollbar-thumb:hover { background: #6b7280; }
        .dark .ts-scroll::-webkit-scrollbar-corner { background: #1e293b; }
    </style>
    @endpush

    <div x-data="{
        showContext: {{ $showContext ? 'true' : 'false' }},
        showUser:    {{ $showUser    ? 'true' : 'false' }},
        showProject: {{ $showProject ? 'true' : 'false' }},
        showNT:      {{ $showNT     ? 'true' : 'false' }},
        showLeaves:  {{ $showLeaves ? 'true' : 'false' }},
        showOT:      {{ $showOT     ? 'true' : 'false' }},
    }">

        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-3 py-4">

            {{-- ── Tabs ─────────────────────────────────────────────────── --}}
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex gap-1">
                    @php $tabBase = 'px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition'; $tabOn = 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'; $tabOff = 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'; @endphp
                    <a href="{{ route('time-logs.index') }}"       class="{{ $tabBase }} {{ $tabOff }}">Danh sách</a>
                    <a href="{{ route('timesheets.project') }}"    class="{{ $tabBase }} {{ $tabOff }}">Dự án</a>
                    <a href="{{ route('timesheets.attendance') }}" class="{{ $tabBase }} {{ $tabOff }}">Chấm công</a>
                    <a href="{{ route('timesheets.calendar') }}"   class="{{ $tabBase }} {{ $tabOff }}">Lịch</a>
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

                    {{-- Project multi-select (search by PJ-xxx or name) --}}
                    @if($availableProjects->isNotEmpty())
                    <div class="min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Dự án</label>
                        <select name="project_ids[]" multiple data-multi-select data-placeholder="Tất cả dự án"
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md text-sm">
                            @foreach($availableProjects as $ap)
                                <option value="{{ $ap->id }}"
                                    {{ in_array($ap->id, $filterProjectIds) ? 'selected' : '' }}>
                                    PJ-{{ $ap->id }} · {{ $ap->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Task multi-select (search by TK-xxx or name) --}}
                    @if($availableTasks->isNotEmpty())
                    <div class="min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Công việc</label>
                        <select name="task_ids[]" multiple data-multi-select data-placeholder="Tất cả công việc"
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md text-sm">
                            @foreach($availableTasks as $at)
                                <option value="{{ $at->id }}"
                                    {{ in_array($at->id, $filterTaskIds) ? 'selected' : '' }}>
                                    TK-{{ $at->id }} · {{ $at->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Hidden inputs carry group-view + view-options state so they're saved on form submit --}}
                    <input type="hidden" name="show_context" x-bind:value="showContext ? '1' : '0'">
                    <input type="hidden" name="show_user"    x-bind:value="showUser    ? '1' : '0'">
                    <input type="hidden" name="show_project" x-bind:value="showProject ? '1' : '0'">
                    <input type="hidden" name="show_nt"      x-bind:value="showNT      ? '1' : '0'">
                    <input type="hidden" name="show_leaves"  x-bind:value="showLeaves  ? '1' : '0'">
                    <input type="hidden" name="show_ot"      x-bind:value="showOT      ? '1' : '0'">

                    <div class="self-end flex gap-2">
                        <button type="submit"
                            class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                            Áp dụng
                        </button>
                        <a href="{{ route('timesheets.timeline', ['reset' => 1]) }}"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Đặt lại
                        </a>
                    </div>
                </form>
            </div>

            {{-- ── Group View + View Options (two connected panels) ─────── --}}
            <div class="flex flex-wrap gap-2">
                {{-- Group View --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg px-4 py-2.5 flex items-center gap-5 flex-1 min-w-fit">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap">
                        Group View
                    </span>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="showContext"
                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Theo công việc</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="showProject"
                            class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Theo dự án</span>
                    </label>
                    @if($isMultiUser)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="showUser"
                            class="rounded border-gray-300 dark:border-gray-600 text-violet-600 focus:ring-violet-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Theo từng người</span>
                    </label>
                    @endif
                </div>

                {{-- View Options --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg px-4 py-2.5 flex items-center gap-5 flex-1 min-w-fit">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap">
                        View Options
                    </span>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="showNT"
                            class="rounded border-gray-300 dark:border-gray-600 text-gray-600 focus:ring-gray-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">View NT</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="showLeaves"
                            class="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-400">
                        <span class="text-sm text-amber-600 dark:text-amber-400">🏖 Leaves</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="showOT"
                            class="rounded border-gray-300 dark:border-gray-600 text-orange-500 focus:ring-orange-400">
                        <span class="text-sm text-orange-600 dark:text-orange-400">⏱ OT</span>
                    </label>
                </div>
            </div>

            {{-- ── Main grid (single table, sticky first 2 cols + sticky header) ──
                 overflow-auto + max-h creates the scroll container that makes
                 both position:sticky left (cols) and position:sticky top (row) work --}}
            <div class="ts-scroll bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-auto max-h-[calc(100vh-10rem)]">
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
                            $hasAnyRows = !empty($rowsByContext) || !empty($rowsByProject) || !empty($rowsByUser);
                        @endphp

                        @if(!$hasAnyRows)
                            <tr>
                                <td colspan="{{ $colCount }}" class="px-6 py-10 text-center text-gray-400 bg-white dark:bg-gray-800">
                                    Chưa có giờ làm việc nào trong khoảng thời gian được chọn.
                                    <a href="{{ route('time-logs.create') }}" class="text-indigo-600 hover:underline ml-1">Chấm công →</a>
                                </td>
                            </tr>
                        @else

                            {{-- ══ SECTION 1: By Context ══════════════════ --}}
                            <tr x-show="showContext" x-cloak>
                                <td class="ts-col-label bg-indigo-50 dark:bg-indigo-950 px-3 py-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                                    📋 Theo công việc
                                </td>
                                <td class="ts-col-total bg-indigo-50 dark:bg-indigo-950 px-2 py-1.5"></td>
                                <td colspan="{{ $colCount - 2 }}" class="bg-indigo-50 dark:bg-indigo-950"></td>
                            </tr>

                            @foreach($rowsByContext as $row)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-750 transition"
                                x-show="showContext" x-cloak>

                                <td class="ts-col-label bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-750 px-3 py-2">
                                    @if($row['link'])
                                        <a href="{{ $row['link'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium text-sm">
                                            {{ $row['label'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-600 dark:text-gray-400 font-medium text-sm">{{ $row['label'] }}</span>
                                    @endif
                                </td>

                                <td class="ts-col-total bg-gray-100 dark:bg-gray-700 group-hover:bg-gray-200 dark:group-hover:bg-gray-600 px-2 py-2 text-center">
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-100">
                                        {{ \App\Models\TimeLog::formatTimeShort($row['total']) }}
                                    </span>
                                </td>

                                @foreach($days as $day)
                                    @php
                                        $dayKey       = $day->format('Y-m-d');
                                        $cell         = $row['days'][$dayKey] ?? null;
                                        $isHolidayDay = in_array($dayKey, $holidayDates);
                                        $isWeekendDay = $day->isWeekend();
                                        // OT for this context row on this day
                                        $ctxKey   = ($row['type'] === 'task') ? 'task_' . $row['task_id']
                                                  : (($row['type'] === 'project') ? 'project_' . $row['project_id'] : 'other');
                                        $ctxOtH   = $otHoursByContextDay[$ctxKey][$dayKey] ?? 0;
                                    @endphp
                                    <td class="px-1 py-1 text-center
                                        {{ $day->isToday() ? 'bg-indigo-50 dark:bg-indigo-950'
                                            : ($isHolidayDay ? $calHolidayBg
                                                : ($isWeekendDay ? $calWeekendBg : '')) }}">
                                        @if($cell || $ctxOtH > 0)
                                            @php
                                                $tooltip = $cell ? implode("\n", array_filter($cell['descriptions'])) : '';
                                                $params  = ['date_from' => $dayKey, 'date_to' => $dayKey];
                                                if ($row['type'] === 'task')        $params['task_id']    = $row['task_id'];
                                                elseif ($row['type'] === 'project') $params['project_id'] = $row['project_id'];
                                                else                                $params['no_context'] = 1;
                                                $cellUrl = $cell ? route('time-logs.index', $params) : null;
                                                $hasTip  = $tooltip || $ctxOtH > 0;
                                            @endphp
                                            <div x-data="{ open: false }" class="relative inline-block"
                                                @mouseenter="open = true" @mouseleave="open = false">
                                                {{-- NT (work hours) --}}
                                                @if($cell)
                                                <div x-show="showNT">
                                                    <a href="{{ $cellUrl }}"
                                                        class="block text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline px-1 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900 transition">
                                                        {{ \App\Models\TimeLog::formatTimeShort($cell['total']) }}
                                                    </a>
                                                </div>
                                                @endif
                                                {{-- OT badge --}}
                                                @if($ctxOtH > 0)
                                                <div x-show="showOT" class="text-xs text-orange-500 dark:text-orange-400 leading-tight mt-0.5 whitespace-nowrap">
                                                    ⏱ {{ \App\Models\TimeLog::formatTimeShort($ctxOtH) }}
                                                </div>
                                                @endif
                                                {{-- Tooltip --}}
                                                @if($hasTip)
                                                <div x-show="open" x-cloak
                                                    class="absolute z-30 bottom-full left-1/2 -translate-x-1/2 mb-2 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 shadow-xl pointer-events-none min-w-max max-w-xs text-left">
                                                    @if($tooltip)
                                                        <div style="white-space: pre-wrap;"
                                                            class="{{ $ctxOtH > 0 ? 'pb-1 mb-1 border-b border-gray-700' : '' }}">{{ $tooltip }}</div>
                                                    @endif
                                                    @if($ctxOtH > 0)
                                                        <div class="text-orange-300 font-medium">⏱ OT: {{ \App\Models\TimeLog::formatTimeShort($ctxOtH) }}</div>
                                                    @endif
                                                </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @endforeach

                            {{-- ══ SECTION 2: By Project ═══════════════════ --}}
                            <tr x-show="showProject" x-cloak>
                                <td class="ts-col-label bg-emerald-50 dark:bg-emerald-950 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                                    📁 Theo dự án
                                </td>
                                <td class="ts-col-total bg-emerald-50 dark:bg-emerald-950 px-2 py-1.5"></td>
                                <td colspan="{{ $colCount - 2 }}" class="bg-emerald-50 dark:bg-emerald-950"></td>
                            </tr>

                            @foreach($rowsByProject as $row)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-750 transition"
                                x-show="showProject" x-cloak>

                                <td class="ts-col-label bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-750 px-3 py-2">
                                    @if($row['link'])
                                        <a href="{{ $row['link'] }}" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium text-sm">
                                            {{ $row['label'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400 font-medium text-sm italic">{{ $row['label'] }}</span>
                                    @endif
                                </td>

                                <td class="ts-col-total bg-gray-100 dark:bg-gray-700 group-hover:bg-gray-200 dark:group-hover:bg-gray-600 px-2 py-2 text-center">
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-100">
                                        {{ \App\Models\TimeLog::formatTimeShort($row['total']) }}
                                    </span>
                                </td>

                                @foreach($days as $day)
                                    @php
                                        $dayKey       = $day->format('Y-m-d');
                                        $cell         = $row['days'][$dayKey] ?? null;
                                        $isHolidayDay = in_array($dayKey, $holidayDates);
                                        $isWeekendDay = $day->isWeekend();
                                        // OT for this project row on this day
                                        $prjKey  = $row['project_id'] ? 'project_' . $row['project_id'] : 'no_project';
                                        $prjOtH  = $otHoursByProjectDay[$prjKey][$dayKey] ?? 0;
                                    @endphp
                                    <td class="px-1 py-1 text-center
                                        {{ $day->isToday() ? 'bg-indigo-50 dark:bg-indigo-950'
                                            : ($isHolidayDay ? $calHolidayBg
                                                : ($isWeekendDay ? $calWeekendBg : '')) }}">
                                        @if($cell || $prjOtH > 0)
                                            @php
                                                $tooltip = $cell ? implode("\n", array_filter($cell['descriptions'])) : '';
                                                $params  = ['date_from' => $dayKey, 'date_to' => $dayKey];
                                                if ($row['project_id']) $params['project_id'] = $row['project_id'];
                                                $cellUrl = $cell ? route('time-logs.index', $params) : null;
                                                $hasTip  = $tooltip || $prjOtH > 0;
                                            @endphp
                                            <div x-data="{ open: false }" class="relative inline-block"
                                                @mouseenter="open = true" @mouseleave="open = false">
                                                {{-- NT --}}
                                                @if($cell)
                                                <div x-show="showNT">
                                                    <a href="{{ $cellUrl }}"
                                                        class="block text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline px-1 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900 transition">
                                                        {{ \App\Models\TimeLog::formatTimeShort($cell['total']) }}
                                                    </a>
                                                </div>
                                                @endif
                                                {{-- OT badge --}}
                                                @if($prjOtH > 0)
                                                <div x-show="showOT" class="text-xs text-orange-500 dark:text-orange-400 leading-tight mt-0.5 whitespace-nowrap">
                                                    ⏱ {{ \App\Models\TimeLog::formatTimeShort($prjOtH) }}
                                                </div>
                                                @endif
                                                {{-- Tooltip --}}
                                                @if($hasTip)
                                                <div x-show="open" x-cloak
                                                    class="absolute z-30 bottom-full left-1/2 -translate-x-1/2 mb-2 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 shadow-xl pointer-events-none min-w-max max-w-xs text-left">
                                                    @if($tooltip)
                                                        <div style="white-space: pre-wrap;"
                                                            class="{{ $prjOtH > 0 ? 'pb-1 mb-1 border-b border-gray-700' : '' }}">{{ $tooltip }}</div>
                                                    @endif
                                                    @if($prjOtH > 0)
                                                        <div class="text-orange-300 font-medium">⏱ OT: {{ \App\Models\TimeLog::formatTimeShort($prjOtH) }}</div>
                                                    @endif
                                                </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @endforeach

                            {{-- ══ SECTION 3: By Individual (multi-user only) ══ --}}
                            @if($isMultiUser)
                            <tr x-show="showUser" x-cloak>
                                <td class="ts-col-label bg-violet-50 dark:bg-violet-950 px-3 py-1.5 text-xs font-semibold text-violet-700 dark:text-violet-400 uppercase tracking-wider">
                                    👤 Theo từng người
                                </td>
                                <td class="ts-col-total bg-violet-50 dark:bg-violet-950 px-2 py-1.5"></td>
                                <td colspan="{{ $colCount - 2 }}" class="bg-violet-50 dark:bg-violet-950"></td>
                            </tr>

                            @foreach($rowsByUser as $row)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-750 transition"
                                x-show="showUser" x-cloak>

                                <td class="ts-col-label bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-750 px-3 py-2">
                                    @if($row['link'])
                                        <a href="{{ $row['link'] }}" class="text-violet-600 dark:text-violet-400 hover:underline font-medium text-sm">
                                            {{ $row['label'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-600 dark:text-gray-400 font-medium text-sm">{{ $row['label'] }}</span>
                                    @endif
                                </td>

                                <td class="ts-col-total bg-gray-100 dark:bg-gray-700 group-hover:bg-gray-200 dark:group-hover:bg-gray-600 px-2 py-2 text-center">
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-100">
                                        {{ \App\Models\TimeLog::formatTimeShort($row['total']) }}
                                    </span>
                                </td>

                                @foreach($days as $day)
                                    @php
                                        $dayKey       = $day->format('Y-m-d');
                                        $cell         = $row['days'][$dayKey] ?? null;
                                        $isHolidayDay = in_array($dayKey, $holidayDates);
                                        $isWeekendDay = $day->isWeekend();

                                        // Leave hours
                                        $leavesForDay = $leaveHoursByUserDay[$row['user_id']][$dayKey] ?? [];
                                        $leaveHours   = array_sum(array_column($leavesForDay, 'hours'));

                                        // OT hours for this user on this day
                                        $otsForDay  = $otHoursByUserDay[$row['user_id']][$dayKey] ?? [];
                                        $otHoursDay = array_sum(array_column($otsForDay, 'hours'));

                                        // Red background = work + leave < 8h on a weekday (OT is extra, not counted)
                                        $workHours = $cell ? $cell['total'] : 0;
                                        $totalEff  = $workHours + $leaveHours;
                                        $isWeekday = !$isWeekendDay && !$isHolidayDay;
                                        $isShort   = $isWeekday && $totalEff < 8;

                                        // Cell background
                                        $cellBg = $isShort
                                            ? 'bg-red-50 dark:bg-red-950/50'
                                            : ($day->isToday()
                                                ? 'bg-indigo-50 dark:bg-indigo-950'
                                                : ($isHolidayDay ? $calHolidayBg
                                                    : ($isWeekendDay ? $calWeekendBg : '')));

                                        $workDescs = $cell ? array_filter($cell['descriptions']) : [];
                                    @endphp
                                    <td class="px-1 py-1 text-center {{ $cellBg }}">
                                        @if($cell || $leaveHours > 0 || $otHoursDay > 0)
                                            @php
                                                $cellUrl = $cell ? route('time-logs.index', [
                                                    'date_from' => $dayKey,
                                                    'date_to'   => $dayKey,
                                                    'user_id'   => $row['user_id'],
                                                ]) : null;
                                                $hasTip = !empty($workDescs) || $leaveHours > 0 || $otHoursDay > 0;
                                            @endphp
                                            <div x-data="{ open: false }" class="relative inline-block"
                                                @mouseenter="open = true" @mouseleave="open = false">

                                                {{-- NT (work hours) --}}
                                                @if($cell)
                                                    <div x-show="showNT">
                                                        <a href="{{ $cellUrl }}"
                                                            class="block text-xs font-semibold text-violet-600 dark:text-violet-400 hover:underline px-1 rounded hover:bg-violet-50 dark:hover:bg-violet-900 transition">
                                                            {{ \App\Models\TimeLog::formatTimeShort($cell['total']) }}
                                                        </a>
                                                    </div>
                                                @else
                                                    <span class="block text-xs text-gray-400 dark:text-gray-500 px-1" x-show="showNT">—</span>
                                                @endif

                                                {{-- Leave badge --}}
                                                @if($leaveHours > 0)
                                                    <div x-show="showLeaves"
                                                        class="text-xs text-amber-500 dark:text-amber-400 leading-tight mt-0.5 whitespace-nowrap">
                                                        🏖 {{ \App\Models\TimeLog::formatTimeShort($leaveHours) }}
                                                    </div>
                                                @endif

                                                {{-- OT badge --}}
                                                @if($otHoursDay > 0)
                                                    <div x-show="showOT"
                                                        class="text-xs text-orange-500 dark:text-orange-400 leading-tight mt-0.5 whitespace-nowrap">
                                                        ⏱ {{ \App\Models\TimeLog::formatTimeShort($otHoursDay) }}
                                                    </div>
                                                @endif

                                                {{-- Tooltip --}}
                                                @if($hasTip)
                                                <div x-show="open" x-cloak
                                                    class="absolute z-30 bottom-full left-1/2 -translate-x-1/2 mb-2 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 shadow-xl pointer-events-none min-w-max max-w-xs text-left">
                                                    @if(!empty($workDescs))
                                                        <div style="white-space: pre-wrap;"
                                                            class="{{ ($leaveHours > 0 || $otHoursDay > 0) ? 'pb-1 mb-1 border-b border-gray-700' : '' }}">{{ implode("\n", $workDescs) }}</div>
                                                    @endif
                                                    @if($leaveHours > 0)
                                                        <div class="text-yellow-300 font-medium {{ $otHoursDay > 0 ? 'pb-1 mb-1 border-b border-gray-700' : '' }}">
                                                            🏖 Nghỉ phép: {{ \App\Models\TimeLog::formatTimeShort($leaveHours) }}
                                                        </div>
                                                        @foreach($leavesForDay as $ld)
                                                            <div class="text-yellow-400 pl-3 {{ $otHoursDay > 0 ? 'pb-0.5' : '' }}">· {{ $ld['type'] }} ({{ \App\Models\TimeLog::formatTimeShort($ld['hours']) }})</div>
                                                        @endforeach
                                                    @endif
                                                    @if($otHoursDay > 0)
                                                        @if($leaveHours > 0)<div class="border-t border-gray-700 mt-1 pt-1"></div>@endif
                                                        <div class="text-orange-300 font-medium">
                                                            ⏱ Tăng ca: {{ \App\Models\TimeLog::formatTimeShort($otHoursDay) }}
                                                        </div>
                                                        @foreach($otsForDay as $od)
                                                            <div class="text-orange-400 pl-3">· {{ $od['type'] }} ({{ \App\Models\TimeLog::formatTimeShort($od['hours']) }})</div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                @endif
                                            </div>
                                        @else
                                            {{-- Empty weekday: show 0h in red, otherwise dash --}}
                                            @if($isShort)
                                                <span class="text-red-400 dark:text-red-500 text-xs font-medium">0h</span>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                            @endif
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
