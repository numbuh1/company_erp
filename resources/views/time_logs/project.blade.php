<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Timesheet Dự án</h2>
            <a href="{{ route('time-logs.create') }}"><x-primary-button>Ghi giờ</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-4">

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
                            border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        Tháng
                    </a>
                    <a href="{{ route('timesheets.project') }}"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                            border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400">
                        Dự án
                    </a>
                </nav>
            </div>

            {{-- Controls --}}
            <form method="GET" action="{{ route('timesheets.project') }}"
                  class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">

                <div class="flex-1 min-w-48">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Dự án</label>
                    <select name="project_id" onchange="this.form.submit()"
                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">— Chọn dự án —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ $selectedProjectId == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <a href="{{ route('timesheets.project', array_filter(['project_id' => $selectedProjectId, 'month' => $prevMonth])) }}"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition">
                        ←
                    </a>
                    <input type="month" name="month" value="{{ $monthStr }}" onchange="this.form.submit()"
                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-3 py-2">
                    <a href="{{ route('timesheets.project', array_filter(['project_id' => $selectedProjectId, 'month' => $nextMonth])) }}"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition">
                        →
                    </a>
                    @if($monthStr !== now()->format('Y-m'))
                        <a href="{{ route('timesheets.project', array_filter(['project_id' => $selectedProjectId])) }}"
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline px-1">
                            Tháng này
                        </a>
                    @endif
                </div>

            </form>

            @if(!$selectedProject)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-10 text-center text-gray-400 text-sm">
                    Chọn một dự án để xem bảng giờ.
                </div>
            @else
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

                    // Sticky column geometry — must match exactly between th and td
                    $c1w  = 'w-[180px] min-w-[180px] max-w-[180px]';
                    $c2w  = 'w-[88px]  min-w-[88px]';
                    // Backgrounds for each "zone" — must be opaque for sticky to work
                    $c1bg      = 'bg-white dark:bg-gray-800';
                    $c1bgHead  = 'bg-gray-50 dark:bg-gray-700';
                    $c1bgTotal = 'bg-gray-50 dark:bg-gray-700';
                    $c2bg      = 'bg-gray-50 dark:bg-gray-750';
                    $c2bgHead  = 'bg-gray-100 dark:bg-gray-700';
                    $c2bgTotal = 'bg-gray-100 dark:bg-gray-700';
                    // Shadow on the right edge of col-2 to indicate frozen area
                    $frozenEdge = 'shadow-[2px_0_4px_-1px_rgba(0,0,0,0.10)] dark:shadow-[2px_0_4px_-1px_rgba(0,0,0,0.35)]';
                    // Thick separator applied to individual <td> cells (works with border-collapse)
                    $sepBorder = 'border-t-[3px] border-gray-300 dark:border-gray-500';
                    $nDays = $days->count();
                @endphp

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-4 pt-4 pb-1 flex items-center justify-between">
                        <span class="text-xs text-gray-400">{{ $monthDate->translatedFormat('F Y') }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="text-xs border-collapse" style="table-layout:fixed; width:max-content; min-width:100%">
                            <colgroup>
                                <col style="width:180px">{{-- col 1: label --}}
                                <col style="width:88px"> {{-- col 2: total --}}
                                @foreach($days as $__)
                                    <col style="width:56px">
                                @endforeach
                            </colgroup>

                            {{-- ── Main header ── --}}
                            <thead>
                                <tr class="{{ $c1bgHead }}">
                                    <th class="sticky left-0 z-20 {{ $c1bgHead }} {{ $c1w }} px-3 py-2 text-left font-medium text-gray-500 uppercase whitespace-nowrap">
                                        Mục
                                    </th>
                                    <th class="sticky left-[180px] z-20 {{ $c2bgHead }} {{ $c2w }} {{ $frozenEdge }} px-2 py-2 text-center font-medium text-gray-500 uppercase border-r border-gray-200 dark:border-gray-600">
                                        Tổng
                                    </th>
                                    @foreach($days as $day)
                                        @php
                                            $dk      = $day->format('Y-m-d');
                                            $isHol   = in_array($dk, $holidayDates);
                                            $isWknd  = $day->isWeekend();
                                            $isToday = $day->isToday();
                                            $headCls = $isToday
                                                ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-bold'
                                                : ($isHol || $isWknd ? 'text-red-400' : 'text-gray-500 dark:text-gray-400');
                                        @endphp
                                        <th class="px-1 py-2 text-center font-medium whitespace-nowrap {{ $headCls }}">
                                            <div>{{ $day->format('d') }}</div>
                                            <div class="text-gray-400 dark:text-gray-500 font-normal text-[10px]">{{ $day->translatedFormat('D') }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                                {{-- ════════════════════════════════════════════════════
                                     SECTION 1 — Công việc
                                ════════════════════════════════════════════════════ --}}
                                <tr class="bg-indigo-50 dark:bg-indigo-950/30">
                                    <td class="sticky left-0 z-10 bg-indigo-50 dark:bg-indigo-950/30 {{ $c1w }} px-3 py-1.5
                                               text-[11px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-widest">
                                        Công việc
                                    </td>
                                    <td class="sticky left-[180px] z-10 bg-indigo-50 dark:bg-indigo-950/30 {{ $c2w }} {{ $frozenEdge }} border-r border-indigo-200 dark:border-indigo-800"></td>
                                    <td colspan="{{ $nDays }}" class="bg-indigo-50 dark:bg-indigo-950/30"></td>
                                </tr>

                                @forelse($taskRows as $key => $row)
                                    <tr class="hover:bg-indigo-50/40 dark:hover:bg-indigo-900/10 transition">
                                        <td class="sticky left-0 z-10 {{ $c1bg }} {{ $c1w }} px-3 py-2 whitespace-nowrap overflow-hidden text-ellipsis"
                                            title="{{ $row['task']?->name ?? $row['label'] }}">
                                            @if($row['task'])
                                                <a href="{{ route('tasks.show', $row['task_id']) }}"
                                                    class="font-mono text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">TK-{{ $row['task_id'] }}</a>
                                                <span class="ml-1 text-gray-700 dark:text-gray-300">{{ $row['task']->name }}</span>
                                            @else
                                                <span class="text-gray-400 italic">{{ $row['label'] }}</span>
                                            @endif
                                        </td>
                                        <td class="sticky left-[180px] z-10 {{ $c2bg }} {{ $c2w }} {{ $frozenEdge }} px-2 py-1.5 text-center border-r border-gray-200 dark:border-gray-600">
                                            @if($row['total_hours'] > 0)
                                                <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($row['total_hours']) }}</div>
                                            @endif
                                            @if($row['total_ot'] > 0)
                                                <div class="text-orange-500">+{{ $fmtHours($row['total_ot']) }}</div>
                                            @endif
                                            @if($row['total_cost'] > 0)
                                                <div class="text-gray-400 text-[10px]">{{ $fmtCost($row['total_cost']) }}</div>
                                            @endif
                                        </td>
                                        @foreach($days as $day)
                                            @php
                                                $dk   = $day->format('Y-m-d');
                                                $cell = $row['days'][$dk] ?? null;
                                                $bg   = (in_array($dk, $holidayDates) || $day->isWeekend())
                                                    ? 'bg-red-50/50 dark:bg-red-900/10' : '';
                                                $cellUrl = route('time-logs.index', array_filter([
                                                    'project_id' => $selectedProjectId,
                                                    'task_id'    => $row['task_id'] ?: null,
                                                    'date_from'  => $dk,
                                                    'date_to'    => $dk,
                                                ]));
                                            @endphp
                                            <td class="px-1 py-1.5 text-center align-top {{ $bg }}">
                                                @if($cell && ($cell['hours'] > 0 || $cell['ot_hours'] > 0))
                                                    <a href="{{ $cellUrl }}" class="block rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/30 px-0.5 py-0.5 transition">
                                                        @if($cell['hours'] > 0)
                                                            <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($cell['hours']) }}</div>
                                                        @endif
                                                        @if($cell['ot_hours'] > 0)
                                                            <div class="text-orange-500">+{{ $fmtHours($cell['ot_hours']) }}</div>
                                                        @endif
                                                        @if($cell['cost'] > 0)
                                                            <div class="text-gray-400 text-[10px]">{{ $fmtCost($cell['cost']) }}</div>
                                                        @endif
                                                    </a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="sticky left-0 z-10 {{ $c1bg }} {{ $c1w }} px-3 py-4 text-gray-400 italic">Không có dữ liệu</td>
                                        <td class="sticky left-[180px] z-10 {{ $c2bg }} {{ $c2w }} {{ $frozenEdge }} border-r border-gray-200 dark:border-gray-600"></td>
                                        <td colspan="{{ $nDays }}"></td>
                                    </tr>
                                @endforelse

                                {{-- ════════════════════════════════════════════════════
                                     SEPARATOR + SECTION 2 — Thành viên
                                     border-t-[3px] on each <td> creates the thick rule
                                ════════════════════════════════════════════════════ --}}
                                <tr class="bg-violet-50 dark:bg-violet-950/30">
                                    <td class="sticky left-0 z-10 bg-violet-50 dark:bg-violet-950/30 {{ $c1w }} px-3 py-1.5
                                               text-[11px] font-bold text-violet-700 dark:text-violet-400 uppercase tracking-widest
                                               border-t-[3px] border-gray-300 dark:border-gray-500">
                                        Thành viên
                                    </td>
                                    <td class="sticky left-[180px] z-10 bg-violet-50 dark:bg-violet-950/30 {{ $c2w }} {{ $frozenEdge }}
                                               border-r border-violet-200 dark:border-violet-800
                                               border-t-[3px] border-gray-300 dark:border-gray-500"></td>
                                    <td colspan="{{ $nDays }}" class="bg-violet-50 dark:bg-violet-950/30 border-t-[3px] border-gray-300 dark:border-gray-500"></td>
                                </tr>

                                @forelse($userRows as $key => $row)
                                    <tr class="hover:bg-violet-50/40 dark:hover:bg-violet-900/10 transition">
                                        <td class="sticky left-0 z-10 {{ $c1bg }} {{ $c1w }} px-3 py-2 whitespace-nowrap overflow-hidden text-ellipsis">
                                            @if($row['user'])
                                                <a href="{{ route('users.show', $row['user_id']) }}"
                                                    class="text-violet-700 dark:text-violet-400 hover:underline font-medium">
                                                    {{ $row['user']->name }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">#{{ $row['user_id'] }}</span>
                                            @endif
                                        </td>
                                        <td class="sticky left-[180px] z-10 {{ $c2bg }} {{ $c2w }} {{ $frozenEdge }} px-2 py-1.5 text-center border-r border-gray-200 dark:border-gray-600">
                                            @if($row['total_hours'] > 0)
                                                <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($row['total_hours']) }}</div>
                                            @endif
                                            @if($row['total_ot'] > 0)
                                                <div class="text-orange-500">+{{ $fmtHours($row['total_ot']) }}</div>
                                            @endif
                                            @if($row['total_cost'] > 0)
                                                <div class="text-gray-400 text-[10px]">{{ $fmtCost($row['total_cost']) }}</div>
                                            @endif
                                        </td>
                                        @foreach($days as $day)
                                            @php
                                                $dk   = $day->format('Y-m-d');
                                                $cell = $row['days'][$dk] ?? null;
                                                $bg   = (in_array($dk, $holidayDates) || $day->isWeekend())
                                                    ? 'bg-red-50/50 dark:bg-red-900/10' : '';
                                                $cellUrl = route('time-logs.index', array_filter([
                                                    'project_id' => $selectedProjectId,
                                                    'user_id'    => $row['user_id'],
                                                    'date_from'  => $dk,
                                                    'date_to'    => $dk,
                                                ]));
                                            @endphp
                                            <td class="px-1 py-1.5 text-center align-top {{ $bg }}">
                                                @if($cell && ($cell['hours'] > 0 || $cell['ot_hours'] > 0))
                                                    <a href="{{ $cellUrl }}" class="block rounded hover:bg-violet-100 dark:hover:bg-violet-900/30 px-0.5 py-0.5 transition">
                                                        @if($cell['hours'] > 0)
                                                            <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($cell['hours']) }}</div>
                                                        @endif
                                                        @if($cell['ot_hours'] > 0)
                                                            <div class="text-orange-500">+{{ $fmtHours($cell['ot_hours']) }}</div>
                                                        @endif
                                                        @if($cell['cost'] > 0)
                                                            <div class="text-gray-400 text-[10px]">{{ $fmtCost($cell['cost']) }}</div>
                                                        @endif
                                                    </a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="sticky left-0 z-10 {{ $c1bg }} {{ $c1w }} px-3 py-4 text-gray-400 italic">Không có dữ liệu</td>
                                        <td class="sticky left-[180px] z-10 {{ $c2bg }} {{ $c2w }} {{ $frozenEdge }} border-r border-gray-200 dark:border-gray-600"></td>
                                        <td colspan="{{ $nDays }}"></td>
                                    </tr>
                                @endforelse

                                {{-- ── Grand total row ── --}}
                                @if(count($taskRows) > 0 || count($userRows) > 0)
                                <tr class="border-t-2 border-gray-300 dark:border-gray-500 font-semibold bg-gray-50 dark:bg-gray-700/60">
                                    <td class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-700 {{ $c1w }} px-3 py-2 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Tổng cộng
                                    </td>
                                    <td class="sticky left-[180px] z-10 bg-gray-100 dark:bg-gray-700 {{ $c2w }} {{ $frozenEdge }} px-2 py-1.5 text-center border-r border-gray-200 dark:border-gray-600">
                                        <div class="text-gray-800 dark:text-gray-200">{{ $fmtHours($grandTotalHours) }}</div>
                                        @if($grandTotalOt > 0)
                                            <div class="text-orange-500">+{{ $fmtHours($grandTotalOt) }}</div>
                                        @endif
                                        @if($grandTotalCost > 0)
                                            <div class="text-gray-400 text-[10px]">{{ $fmtCost($grandTotalCost) }}</div>
                                        @endif
                                    </td>
                                    @foreach($days as $day)
                                        @php
                                            $dk  = $day->format('Y-m-d');
                                            $tot = $dayTotals[$dk] ?? ['hours' => 0, 'ot_hours' => 0, 'cost' => 0];
                                        @endphp
                                        <td class="px-1 py-1.5 text-center">
                                            @if($tot['hours'] > 0 || $tot['ot_hours'] > 0)
                                                <div class="text-gray-700 dark:text-gray-300">{{ $fmtHours($tot['hours']) }}</div>
                                                @if($tot['ot_hours'] > 0)
                                                    <div class="text-orange-500">+{{ $fmtHours($tot['ot_hours']) }}</div>
                                                @endif
                                                @if($tot['cost'] > 0)
                                                    <div class="text-gray-400 text-[10px]">{{ $fmtCost($tot['cost']) }}</div>
                                                @endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endif

                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Footer stats bar ─────────────────────────────────────── --}}
                @if($grandTotalHours > 0 || $grandTotalOt > 0)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Tổng giờ</div>
                            <div class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $fmtHours($grandTotalHours + $grandTotalOt) }}</div>
                            @if($grandTotalOt > 0)
                                <div class="text-xs text-orange-500">trong đó OT: +{{ $fmtHours($grandTotalOt) }}</div>
                            @endif
                        </div>
                        @if($grandTotalCost > 0)
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Tổng chi phí</div>
                            <div class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ number_format($grandTotalCost, 0, '.', ',') }} ₫</div>
                        </div>
                        @endif
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Giờ/ngày (ngày có log)</div>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-0.5">
                                <div>Max: <span class="font-semibold">{{ $fmtHours($maxHours) }}</span></div>
                                <div>Min: <span class="font-semibold">{{ $fmtHours($minHours) }}</span></div>
                                <div>Median: <span class="font-semibold">{{ $fmtHours($medianHours) }}</span></div>
                            </div>
                        </div>
                        @if($grandTotalCost > 0)
                        <div>
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Chi phí/ngày (ngày có log)</div>
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-0.5">
                                <div>Max: <span class="font-semibold">{{ $fmtCost($maxCost) }} ₫</span></div>
                                <div>Min: <span class="font-semibold">{{ $fmtCost($minCost) }} ₫</span></div>
                                <div>Median: <span class="font-semibold">{{ $fmtCost($medianCost) }} ₫</span></div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            @endif
        </div>
    </div>

</x-app-layout>
