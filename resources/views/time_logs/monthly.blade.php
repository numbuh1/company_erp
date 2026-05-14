<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Bảng chấm công</h2>
            <a href="{{ route('time-logs.create') }}"><x-primary-button>Ghi giờ</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

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
                            border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        Tuần
                    </a>
                    <a href="{{ route('timesheets.monthly') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400">
                        Tháng
                    </a>
                </nav>
            </div>

            {{-- Controls: filter + month nav --}}
            @php
                $navParams = ['mode' => $mode];
                if ($mode === 'team' && $selectedTeamId) {
                    $navParams['team_id'] = $selectedTeamId;
                } elseif ($selectedUserId !== auth()->id()) {
                    $navParams['user_id'] = $selectedUserId;
                }
            @endphp
            <div class="flex flex-wrap gap-3 items-center justify-between">

                {{-- Filter form --}}
                <div>
                    @if($filterUsers)
                        <form method="GET"
                              x-data="{ mode: '{{ $mode }}' }"
                              class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="month" value="{{ $monthStr }}">

                            {{-- Left: Individual / Team --}}
                            <select name="mode" x-model="mode"
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                <option value="individual">Cá nhân</option>
                                @if($filterTeams)
                                    <option value="team">Nhóm</option>
                                @endif
                            </select>

                            {{-- Right: individual list --}}
                            <div x-show="mode === 'individual'">
                                <select name="user_id"
                                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                    @foreach($filterUsers as $u)
                                        <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                                            {{ $u->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Right: team list --}}
                            @if($filterTeams)
                                <div x-show="mode === 'team'">
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
                        </form>
                    @else
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $selectedUser->name }}
                        </span>
                    @endif
                </div>

                {{-- Month navigation --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('timesheets.monthly', array_merge(['month' => $prevMonth], $navParams)) }}"
                        class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        ← Prev
                    </a>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 min-w-[110px] text-center">
                        {{ $monthDate->format('F Y') }}
                    </span>
                    <a href="{{ route('timesheets.monthly', array_merge(['month' => $nextMonth], $navParams)) }}"
                        class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        Next →
                    </a>
                    @if($monthStr !== now()->format('Y-m'))
                        <a href="{{ route('timesheets.monthly', $navParams) }}"
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            This month
                        </a>
                    @endif
                </div>
            </div>

            {{-- Info card + monthly stats --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                <div class="flex flex-wrap gap-4 items-center justify-between">

                    @if($mode === 'team' && $selectedTeam)
                        {{-- Team info --}}
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold text-lg shrink-0">
                                {{ strtoupper(mb_substr($selectedTeam->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-200 leading-tight">
                                    {{ $selectedTeam->name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $selectedTeam->users->count() }} member{{ $selectedTeam->users->count() === 1 ? '' : 's' }}
                                </p>
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @foreach($selectedTeam->users->take(10) as $member)
                                        <x-user-status :user="$member" :show-name="false" />
                                    @endforeach
                                    @if($selectedTeam->users->count() > 10)
                                        <span class="text-xs text-gray-400 self-center">+{{ $selectedTeam->users->count() - 10 }} more</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Individual user info --}}
                        <div class="flex items-center gap-3">
                            @if($selectedUser->profile_picture)
                                <img src="{{ asset('storage/profile_pictures/' . $selectedUser->profile_picture) }}"
                                    class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-gray-600 shrink-0">
                            @else
                                <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-700 dark:text-indigo-300 font-bold text-xl shrink-0">
                                    {{ strtoupper(mb_substr($selectedUser->name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-200 leading-tight">
                                    {{ $selectedUser->name }}
                                </p>
                                @if($selectedUser->position)
                                    <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5">
                                        {{ $selectedUser->position }}
                                    </p>
                                @endif
                                @if($selectedUser->teams->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach($selectedUser->teams as $team)
                                            <span class="text-xs px-2 py-0.5 rounded-full
                                                bg-gray-100 dark:bg-gray-700
                                                text-gray-600 dark:text-gray-300
                                                border border-gray-200 dark:border-gray-600">
                                                {{ $team->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Monthly stats (same for both modes) --}}
                    <div class="flex gap-3">
                        <div class="text-center px-5 py-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <p class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide mb-1">Công việc</p>
                            <p class="text-xl font-bold text-green-700 dark:text-green-300">
                                {{ $totalWork > 0 ? \App\Models\TimeLog::formatTime($totalWork) : '—' }}
                            </p>
                        </div>
                        <div class="text-center px-5 py-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <p class="text-xs font-semibold text-yellow-600 dark:text-yellow-400 uppercase tracking-wide mb-1">OT</p>
                            <p class="text-xl font-bold text-yellow-700 dark:text-yellow-300">
                                {{ $totalOt > 0 ? \App\Models\TimeLog::formatTime($totalOt) : '—' }}
                            </p>
                        </div>
                        <div class="text-center px-5 py-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <p class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide mb-1">Nghỉ phép</p>
                            <p class="text-xl font-bold text-red-700 dark:text-red-300">
                                {{ $totalLeave > 0 ? \App\Models\TimeLog::formatTime($totalLeave) : '—' }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Calendar grid --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">

                {{-- Day-of-week headers --}}
                <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700">
                    @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dh)
                        <div class="px-2 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide text-center">
                            {{ $dh }}
                        </div>
                    @endforeach
                </div>

                {{-- Weeks --}}
                @php $cursor = $calStart->copy(); $today = now()->toDateString(); @endphp
                @while($cursor->lte($calEnd))
                    <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700 last:border-0">
                        @for($d = 0; $d < 7; $d++)
                            @php
                                $dk         = $cursor->toDateString();
                                $isToday    = $dk === $today;
                                $isInMonth  = $cursor->month === $monthDate->month;
                                $isWeekend  = $cursor->isWeekend();
                                $isHoliday  = in_array($dk, $holidayDates);
                                $workHours  = $logsByDay[$dk]  ?? 0;
                                $otHours    = $otByDay[$dk]    ?? 0;
                                $leaveHours = $leaveByDay[$dk] ?? 0;
                            @endphp
                            <div class="min-h-[88px] px-2 py-1.5 border-r border-gray-100 dark:border-gray-700 last:border-0
                                {{ $isHoliday  ? $calHolidayBg
                                    : ($isWeekend ? $calWeekendBg
                                        : (!$isInMonth ? $calOutsideBg : '')) }}">

                                {{-- Day number --}}
                                <div class="text-xs font-semibold mb-1 w-6 h-6 flex items-center justify-center rounded-full
                                    {{ $isToday
                                        ? 'bg-indigo-600 text-white'
                                        : ($isInMonth ? 'text-gray-700 dark:text-gray-300' : 'text-gray-300 dark:text-gray-600') }}">
                                    {{ $cursor->day }}
                                </div>

                                {{-- Work hours (green) --}}
                                @if($workHours > 0)
                                    <div class="text-xs px-1.5 py-0.5 rounded font-medium mb-0.5 truncate
                                        bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400">
                                        {{ \App\Models\TimeLog::formatTime($workHours) }}
                                    </div>
                                @endif

                                {{-- OT hours (yellow) --}}
                                @if($otHours > 0)
                                    <div class="text-xs px-1.5 py-0.5 rounded font-medium mb-0.5 truncate
                                        bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400">
                                        OT {{ \App\Models\TimeLog::formatTime($otHours) }}
                                    </div>
                                @endif

                                {{-- Leave hours (red) --}}
                                @if($leaveHours > 0)
                                    <div class="text-xs px-1.5 py-0.5 rounded font-medium truncate
                                        bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">
                                        Leave {{ \App\Models\TimeLog::formatTime($leaveHours) }}
                                    </div>
                                @endif

                            </div>
                            @php $cursor->addDay(); @endphp
                        @endfor
                    </div>
                @endwhile
            </div>

        </div>
    </div>
</x-app-layout>
