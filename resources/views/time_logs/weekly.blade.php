@php
    $filterParams = array_filter([
        'mode'    => $mode !== 'individual' ? $mode : null,
        'user_id' => ($mode === 'individual' && $selectedUserId != auth()->id()) ? $selectedUserId : null,
        'team_id' => ($mode === 'team') ? $selectedTeamId : null,
        'group'   => $groupBy !== 'context' ? $groupBy : null,
    ]);
    $prevParams     = array_merge(['offset' => $offset - 1], $filterParams);
    $nextParams     = array_merge(['offset' => $offset + 1], $filterParams);
    $thisWeekParams = $filterParams;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Bảng giờ tuần</h2>
            <a href="{{ route('time-logs.create') }}"><x-primary-button>Chấm công</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Tabs --}}
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex gap-1">
                    <a href="{{ route('time-logs.index') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        Danh sách
                    </a>
                    <a href="{{ route('timesheets.weekly') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400">
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

            {{-- Combined: week nav + user/team filter --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg px-4 py-3 flex flex-wrap gap-3 items-center justify-between">

                {{-- Week navigation --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('timesheets.weekly', $prevParams) }}"
                        class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        ← Prev
                    </a>
                    <form method="GET" action="{{ route('timesheets.weekly') }}" class="inline-flex">
                        @foreach($filterParams as $fpk => $fpv)
                            <input type="hidden" name="{{ $fpk }}" value="{{ $fpv }}">
                        @endforeach
                        <input type="date" name="date"
                               value="{{ $weekStart->format('Y-m-d') }}"
                               onchange="this.form.submit()"
                               title="{{ $weekStart->translatedFormat('d M') }} – {{ $weekEnd->translatedFormat('d M Y') }}"
                               class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded text-sm px-2 py-1.5 cursor-pointer">
                    </form>
                    <a href="{{ route('timesheets.weekly', $nextParams) }}"
                        class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        Next →
                    </a>
                    @if($offset !== 0)
                        <a href="{{ route('timesheets.weekly', $thisWeekParams) }}"
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Tuần này</a>
                    @endif
                </div>

                {{-- User / Team filter --}}
                @if($filterUsers || $filterTeams)
                <form method="GET"
                      x-data="{ mode: '{{ $mode }}' }"
                      class="flex flex-wrap gap-2 items-end">
                    <input type="hidden" name="offset" value="{{ $offset }}">
                    <input type="hidden" name="group" value="{{ $groupBy }}">

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Xem</label>
                        <select name="mode" x-model="mode"
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="individual">Cá nhân</option>
                            @if($filterTeams && $filterTeams->isNotEmpty())
                                <option value="team">Nhóm</option>
                            @endif
                        </select>
                    </div>

                    <div x-show="mode === 'individual'">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Thành viên</label>
                        <select name="user_id"
                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            @foreach($filterUsers ?? [] as $u)
                                <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($filterTeams && $filterTeams->isNotEmpty())
                        <div x-show="mode === 'team'">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Nhóm</label>
                            <select name="team_id"
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                @foreach($filterTeams as $t)
                                    <option value="{{ $t->id }}" {{ $selectedTeamId == $t->id ? 'selected' : '' }}>
                                        {{ $t->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <x-primary-button type="submit">Áp dụng</x-primary-button>
                    <a href="{{ route('timesheets.weekly', ['offset' => $offset]) }}">
                        <x-secondary-button type="button">Đặt lại</x-secondary-button>
                    </a>
                </form>
                @endif

            </div>

            {{-- Group-by toggle (only visible when user can see multiple people) --}}
            @if($filterUsers)
                <div class="flex gap-0.5 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-1 w-fit">
                    <a href="{{ route('timesheets.weekly', array_merge($filterParams, ['offset' => $offset, 'group' => 'context'])) }}"
                        class="px-3 py-1.5 text-sm rounded transition font-medium
                            {{ $groupBy === 'context'
                                ? 'bg-indigo-600 text-white'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        Theo công việc
                    </a>
                    <a href="{{ route('timesheets.weekly', array_merge($filterParams, ['offset' => $offset, 'group' => 'user'])) }}"
                        class="px-3 py-1.5 text-sm rounded transition font-medium
                            {{ $groupBy === 'user'
                                ? 'bg-indigo-600 text-white'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        Theo từng người
                    </a>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-64">
                                {{ $groupBy === 'user' ? 'Nhân sự' : 'Công việc' }}
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Tổng</th>
                            @foreach($days as $day)
                                @php
                                    $isHolidayDay = in_array($day->format('Y-m-d'), $holidayDates);
                                    $isWeekendDay = $day->isWeekend();
                                @endphp
                                <th class="px-3 py-3 text-center text-xs font-medium uppercase w-24
                                    {{ $day->isToday()
                                        ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950'
                                        : ($isHolidayDay ? $calHolidayHeaderCls
                                            : ($isWeekendDay ? $calWeekendHeaderCls : 'text-gray-500')) }}">
                                    {{ $day->translatedFormat('D') }}<br>
                                    <span class="font-normal normal-case">{{ $day->format('d/m') }}</span>
                                </th>
                            @endforeach

                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($rows as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                {{-- Row label --}}
                                <td class="px-4 py-3">
                                    @if($row['link'])
                                        <a href="{{ $row['link'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium text-sm">
                                            {{ $row['label'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-600 dark:text-gray-400 font-medium text-sm">{{ $row['label'] }}</span>
                                    @endif
                                </td>
                                {{-- Row total --}}
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 px-2 py-0.5 rounded">
                                        {{ \App\Models\TimeLog::formatTime($row['total']) }}
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
                                    <td class="px-2 py-1 text-center
                                        {{ $day->isToday() ? 'bg-indigo-50 dark:bg-indigo-950'
                                            : ($isHolidayDay ? $calHolidayBg
                                                : ($isWeekendDay ? $calWeekendBg : '')) }}">
                                        @if($cell)
                                            @php
                                                $cellDescs = array_filter($cell['descriptions']);
                                                $tooltip   = implode("\n", $cellDescs);
                                                if (count($cell['logs']) === 1) {
                                                    $cellUrl = route('time-logs.show', $cell['logs'][0]->id);
                                                } else {
                                                    $params = ['date' => $dayKey];
                                                    if ($row['type'] === 'task')        $params['task_id']    = $row['task_id'];
                                                    elseif ($row['type'] === 'project') $params['project_id'] = $row['project_id'];
                                                    elseif ($row['type'] === 'user')    $params['user_id']    = $row['user_id'];
                                                    else                                $params['no_context'] = 1;
                                                    if ($row['type'] !== 'user') {
                                                        if ($selectedTeamId)     $params['team_id'] = $selectedTeamId;
                                                        elseif ($selectedUserId) $params['user_id'] = $selectedUserId;
                                                    }
                                                    $cellUrl = route('time-logs.index', $params);
                                                }
                                            @endphp
                                            <div x-data="{ open: false }" class="relative inline-block"
                                                @mouseenter="open = true" @mouseleave="open = false">
                                                <a href="{{ $cellUrl }}"
                                                    class="block text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline px-2 py-1.5 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900 transition">
                                                    {{ \App\Models\TimeLog::formatTime($cell['total']) }}
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
                        @empty
                            <tr>
                                <td colspan="{{ 9 }}" class="px-6 py-10 text-center text-gray-400">
                                    Chưa có giờ làm việc nào trong tuần này.
                                    <a href="{{ route('time-logs.create') }}" class="text-indigo-600 hover:underline ml-1">Chấm công →</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($rows) > 0)
                        <tfoot class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Tổng</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200 bg-indigo-100 dark:bg-indigo-900 px-2 py-0.5 rounded">
                                        {{ \App\Models\TimeLog::formatTime($weekTotal) }}
                                    </span>
                                </td>
                                @foreach($days as $day)
                                    @php
                                        $dk           = $day->format('Y-m-d');
                                        $isHolidayDay = in_array($dk, $holidayDates);
                                        $isWeekendDay = $day->isWeekend();
                                    @endphp
                                    <td class="px-2 py-3 text-center
                                        {{ $day->isToday() ? 'bg-indigo-50 dark:bg-indigo-950'
                                            : ($isHolidayDay ? $calHolidayBg
                                                : ($isWeekendDay ? $calWeekendBg : '')) }}">
                                        @if($dayTotals[$dk] > 0)
                                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                {{ \App\Models\TimeLog::formatTime($dayTotals[$dk]) }}
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
            </div>
        </div>
    </div>
</x-app-layout>
