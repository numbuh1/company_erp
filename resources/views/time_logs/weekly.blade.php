@php
    $filterParams = array_filter([
        'user_id' => $selectedUserId,
        'team_id' => $selectedTeamId,
    ]);
    $prevParams     = array_merge(['offset' => $offset - 1], $filterParams);
    $nextParams     = array_merge(['offset' => $offset + 1], $filterParams);
    $thisWeekParams = $filterParams;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Weekly Timesheet</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('timesheets.weekly', $prevParams) }}"
                    class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                    ← Prev
                </a>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}
                </span>
                <a href="{{ route('timesheets.weekly', $nextParams) }}"
                    class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                    Next →
                </a>
                @if($offset !== 0)
                    <a href="{{ route('timesheets.weekly', $thisWeekParams) }}"
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">This week</a>
                @endif
                <a href="{{ route('time-logs.create') }}"><x-primary-button>Log Time</x-primary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- User / Team filter --}}
            @if($filterUsers || $filterTeams)
                <form method="GET" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="offset" value="{{ $offset }}">
                    @if($filterUsers)
                        <div>
                            <x-input-label value="User" />
                            <select name="user_id" class="mt-1 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                @foreach($filterUsers as $u)
                                    <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($filterTeams)
                        <div>
                            <x-input-label value="Team" />
                            <select name="team_id" class="mt-1 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                <option value="">— Individual —</option>
                                @foreach($filterTeams as $team)
                                    <option value="{{ $team->id }}" {{ $selectedTeamId == $team->id ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <x-primary-button type="submit">Apply</x-primary-button>
                    <a href="{{ route('timesheets.weekly', ['offset' => $offset]) }}"><x-secondary-button type="button">Reset</x-secondary-button></a>
                </form>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-64">Context</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Total</th>
                            @foreach($days as $day)
                                <th class="px-3 py-3 text-center text-xs font-medium uppercase w-24
                                    {{ $day->isToday() ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">
                                    {{ $day->format('D') }}<br>
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
                                        $dayKey = $day->format('Y-m-d');
                                        $cell   = $row['days'][$dayKey] ?? null;
                                    @endphp
                                    <td class="px-2 py-1 text-center {{ $day->isToday() ? 'bg-indigo-50 dark:bg-indigo-950' : '' }}">
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
                                                    else                                $params['no_context'] = 1;
                                                    if ($selectedTeamId)      $params['team_id'] = $selectedTeamId;
                                                    elseif ($selectedUserId)  $params['user_id'] = $selectedUserId;
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
                                    No time logged this week.
                                    <a href="{{ route('time-logs.create') }}" class="text-indigo-600 hover:underline ml-1">Log time →</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($rows) > 0)
                        <tfoot class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td class="px-4 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Total</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200 bg-indigo-100 dark:bg-indigo-900 px-2 py-0.5 rounded">
                                        {{ \App\Models\TimeLog::formatTime($weekTotal) }}
                                    </span>
                                </td>
                                @foreach($days as $day)
                                    @php $dk = $day->format('Y-m-d'); @endphp
                                    <td class="px-2 py-3 text-center {{ $day->isToday() ? 'bg-indigo-50 dark:bg-indigo-950' : '' }}">
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

            <div class="mt-4 text-right">
                <a href="{{ route('time-logs.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View all logs →</a>
            </div>
        </div>
    </div>
</x-app-layout>
