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
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition">←</a>
                    <input type="month" name="month" value="{{ $monthStr }}" onchange="this.form.submit()"
                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm px-3 py-2">
                    <a href="{{ route('timesheets.project', array_filter(['project_id' => $selectedProjectId, 'month' => $nextMonth])) }}"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition">→</a>
                    @if($monthStr !== now()->format('Y-m'))
                        <a href="{{ route('timesheets.project', array_filter(['project_id' => $selectedProjectId])) }}"
                            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline px-1">Tháng này</a>
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

                    $nDays = $days->count();

                    // ── Column geometry ─────────────────────────────────────────
                    // col 1: 180px label   col 2: 88px total   rest: 56px per day
                    // The left-[180px] on col-2 sticky must exactly match col-1 width.

                    // ── Opaque backgrounds (sticky cells MUST be opaque) ─────────
                    // Data rows
                    $bgData1 = 'bg-white dark:bg-gray-800';          // col 1, data rows
                    $bgData2 = 'bg-gray-50 dark:bg-gray-800';        // col 2, data rows

                    // thead
                    $bgHead  = 'bg-gray-50 dark:bg-gray-700';

                    // Section-header rows — SAME style for both sections
                    $bgSec   = 'bg-gray-200 dark:bg-gray-600';

                    // Total rows — SAME style for both total rows
                    $bgTot   = 'bg-gray-100 dark:bg-gray-700';

                    // Right-edge shadow on col-2 to mark the frozen boundary
                    $edge = 'shadow-[2px_0_5px_-1px_rgba(0,0,0,0.12)] dark:shadow-[2px_0_5px_-1px_rgba(0,0,0,0.40)]';

                    // ── Reusable cell class strings ──────────────────────────────
                    // thead
                    $thC1 = "sticky left-0 z-20 {$bgHead} w-[180px] min-w-[180px] max-w-[180px] px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap";
                    $thC2 = "sticky left-[180px] z-20 {$bgHead} w-[88px] min-w-[88px] {$edge} px-2 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase border-r border-gray-200 dark:border-gray-600";

                    // data rows
                    $tdC1 = "sticky left-0 z-10 {$bgData1} w-[180px] min-w-[180px] max-w-[180px] px-3 py-2 whitespace-nowrap overflow-hidden text-ellipsis";
                    $tdC2 = "sticky left-[180px] z-10 {$bgData2} w-[88px] min-w-[88px] {$edge} px-2 py-1.5 text-center border-r border-gray-200 dark:border-gray-600";

                    // section-header rows (same for Công việc AND Thành viên)
                    $secC1  = "sticky left-0 z-10 {$bgSec} w-[180px] min-w-[180px] max-w-[180px] px-3 py-1.5 text-[11px] font-bold text-gray-600 dark:text-gray-200 uppercase tracking-widest";
                    $secC2  = "sticky left-[180px] z-10 {$bgSec} w-[88px] min-w-[88px] {$edge} border-r border-gray-300 dark:border-gray-500";

                    // total rows (same for Tasks total AND Grand total)
                    $totC1 = "sticky left-0 z-10 {$bgTot} w-[180px] min-w-[180px] max-w-[180px] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300";
                    $totC2 = "sticky left-[180px] z-10 {$bgTot} w-[88px] min-w-[88px] {$edge} px-2 py-1.5 text-center font-semibold border-r border-gray-200 dark:border-gray-600";
                @endphp

                {{-- ─────────────────────────────────────────────────────────────
                     NOTE: NO overflow-hidden on the card wrapper.
                     overflow-hidden on any ancestor traps position:sticky so the
                     frozen columns stop working. The scroll is handled solely by
                     the inner overflow-x-auto div.
                ───────────────────────────────────────────────────────────────── --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="px-4 pt-4 pb-1">
                        <span class="text-xs text-gray-400">{{ $monthDate->translatedFormat('F Y') }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="text-xs border-collapse" style="table-layout:fixed; width:max-content; min-width:100%">
                            <colgroup>
                                <col style="width:180px">
                                <col style="width:88px">
                                @foreach($days as $__)
                                    <col style="width:56px">
                                @endforeach
                            </colgroup>

                            {{-- ── Column headers ── --}}
                            <thead>
                                <tr>
                                    <th class="{{ $thC1 }}">Mục</th>
                                    <th class="{{ $thC2 }}">Tổng</th>
                                    @foreach($days as $day)
                                        @php
                                            $dk      = $day->format('Y-m-d');
                                            $isHol   = in_array($dk, $holidayDates);
                                            $isWknd  = $day->isWeekend();
                                            $isToday = $day->isToday();
                                            $dayCls  = $isToday
                                                ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-bold'
                                                : ($isHol || $isWknd ? 'text-red-400 dark:text-red-400' : 'text-gray-500 dark:text-gray-400');
                                        @endphp
                                        <th class="px-1 py-2 text-center font-medium whitespace-nowrap {{ $dayCls }}">
                                            <div>{{ $day->format('d') }}</div>
                                            <div class="text-[10px] font-normal opacity-60">{{ $day->translatedFormat('D') }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

                                {{-- ══════════════════════════════════════════════
                                     SECTION 1 — CÔNG VIỆC
                                ══════════════════════════════════════════════ --}}
                                <tr>
                                    <td class="{{ $secC1 }}">Công việc</td>
                                    <td class="{{ $secC2 }}"></td>
                                    <td colspan="{{ $nDays }}" class="{{ $bgSec }}"></td>
                                </tr>

                                @forelse($taskRows as $key => $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                        <td class="{{ $tdC1 }}" title="{{ $row['task']?->name ?? $row['label'] }}">
                                            @if($row['task'])
                                                <a href="{{ route('tasks.show', $row['task_id']) }}"
                                                    class="font-mono text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">TK-{{ $row['task_id'] }}</a>
                                                <span class="ml-1 text-gray-700 dark:text-gray-300">{{ $row['task']->name }}</span>
                                            @else
                                                <span class="text-gray-400 italic">{{ $row['label'] }}</span>
                                            @endif
                                        </td>
                                        <td class="{{ $tdC2 }}">
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
                                                $wkBg = (in_array($dk, $holidayDates) || $day->isWeekend()) ? 'bg-red-50/40 dark:bg-red-900/10' : '';
                                                $url  = route('time-logs.index', array_filter([
                                                    'project_id' => $selectedProjectId,
                                                    'task_id'    => $row['task_id'] ?: null,
                                                    'date_from'  => $dk, 'date_to' => $dk,
                                                ]));
                                            @endphp
                                            <td class="px-0.5 py-1.5 text-center align-top {{ $wkBg }}">
                                                @if($cell && ($cell['hours'] > 0 || $cell['ot_hours'] > 0))
                                                    <a href="{{ $url }}" class="block rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/30 px-0.5 py-0.5 transition">
                                                        @if($cell['hours'] > 0)<div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($cell['hours']) }}</div>@endif
                                                        @if($cell['ot_hours'] > 0)<div class="text-orange-500">+{{ $fmtHours($cell['ot_hours']) }}</div>@endif
                                                        @if($cell['cost'] > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($cell['cost']) }}</div>@endif
                                                    </a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="{{ $tdC1 }} text-gray-400 italic">Không có dữ liệu</td>
                                        <td class="{{ $tdC2 }}"></td>
                                        <td colspan="{{ $nDays }}"></td>
                                    </tr>
                                @endforelse

                                {{-- Total row — Công việc section --}}
                                @if(count($taskRows) > 0)
                                <tr class="border-t border-gray-200 dark:border-gray-600">
                                    <td class="{{ $totC1 }}">Tổng công việc</td>
                                    <td class="{{ $totC2 }}">
                                        <div class="text-gray-800 dark:text-gray-200">{{ $fmtHours($grandTotalHours) }}</div>
                                        @if($grandTotalOt > 0)<div class="text-orange-500">+{{ $fmtHours($grandTotalOt) }}</div>@endif
                                        @if($grandTotalCost > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($grandTotalCost) }}</div>@endif
                                    </td>
                                    @foreach($days as $day)
                                        @php $dk = $day->format('Y-m-d'); $tot = $dayTotals[$dk] ?? ['hours'=>0,'ot_hours'=>0,'cost'=>0]; @endphp
                                        <td class="px-0.5 py-1.5 text-center {{ $bgTot }}">
                                            @if($tot['hours'] > 0 || $tot['ot_hours'] > 0)
                                                <div class="text-gray-700 dark:text-gray-300">{{ $fmtHours($tot['hours']) }}</div>
                                                @if($tot['ot_hours'] > 0)<div class="text-orange-500">+{{ $fmtHours($tot['ot_hours']) }}</div>@endif
                                                @if($tot['cost'] > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($tot['cost']) }}</div>@endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endif

                                {{-- ══════════════════════════════════════════════
                                     SECTION 2 — THÀNH VIÊN
                                     border-t-[3px] on each <td> is the thick rule.
                                     Applied to <td> (not <tr>) so it works under
                                     border-collapse: collapse.
                                ══════════════════════════════════════════════ --}}
                                <tr>
                                    <td class="{{ $secC1 }} border-t-[3px] border-gray-400 dark:border-gray-500">Thành viên</td>
                                    <td class="{{ $secC2 }} border-t-[3px] border-gray-400 dark:border-gray-500"></td>
                                    <td colspan="{{ $nDays }}" class="{{ $bgSec }} border-t-[3px] border-gray-400 dark:border-gray-500"></td>
                                </tr>

                                @forelse($userRows as $key => $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                        <td class="{{ $tdC1 }}">
                                            @if($row['user'])
                                                <a href="{{ route('users.show', $row['user_id']) }}"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                                    {{ $row['user']->name }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">#{{ $row['user_id'] }}</span>
                                            @endif
                                        </td>
                                        <td class="{{ $tdC2 }}">
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
                                                $wkBg = (in_array($dk, $holidayDates) || $day->isWeekend()) ? 'bg-red-50/40 dark:bg-red-900/10' : '';
                                                $url  = route('time-logs.index', array_filter([
                                                    'project_id' => $selectedProjectId,
                                                    'user_id'    => $row['user_id'],
                                                    'date_from'  => $dk, 'date_to' => $dk,
                                                ]));
                                            @endphp
                                            <td class="px-0.5 py-1.5 text-center align-top {{ $wkBg }}">
                                                @if($cell && ($cell['hours'] > 0 || $cell['ot_hours'] > 0))
                                                    <a href="{{ $url }}" class="block rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/30 px-0.5 py-0.5 transition">
                                                        @if($cell['hours'] > 0)<div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($cell['hours']) }}</div>@endif
                                                        @if($cell['ot_hours'] > 0)<div class="text-orange-500">+{{ $fmtHours($cell['ot_hours']) }}</div>@endif
                                                        @if($cell['cost'] > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($cell['cost']) }}</div>@endif
                                                    </a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="{{ $tdC1 }} text-gray-400 italic">Không có dữ liệu</td>
                                        <td class="{{ $tdC2 }}"></td>
                                        <td colspan="{{ $nDays }}"></td>
                                    </tr>
                                @endforelse

                                {{-- Grand total row (same style as tasks total) --}}
                                @if(count($userRows) > 0)
                                <tr class="border-t border-gray-200 dark:border-gray-600">
                                    <td class="{{ $totC1 }}">Tổng cộng</td>
                                    <td class="{{ $totC2 }}">
                                        <div class="text-gray-800 dark:text-gray-200">{{ $fmtHours($grandTotalHours) }}</div>
                                        @if($grandTotalOt > 0)<div class="text-orange-500">+{{ $fmtHours($grandTotalOt) }}</div>@endif
                                        @if($grandTotalCost > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($grandTotalCost) }}</div>@endif
                                    </td>
                                    @foreach($days as $day)
                                        @php $dk = $day->format('Y-m-d'); $tot = $dayTotals[$dk] ?? ['hours'=>0,'ot_hours'=>0,'cost'=>0]; @endphp
                                        <td class="px-0.5 py-1.5 text-center {{ $bgTot }}">
                                            @if($tot['hours'] > 0 || $tot['ot_hours'] > 0)
                                                <div class="text-gray-700 dark:text-gray-300">{{ $fmtHours($tot['hours']) }}</div>
                                                @if($tot['ot_hours'] > 0)<div class="text-orange-500">+{{ $fmtHours($tot['ot_hours']) }}</div>@endif
                                                @if($tot['cost'] > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($tot['cost']) }}</div>@endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endif

                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Footer stats ──────────────────────────────────────────── --}}
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
