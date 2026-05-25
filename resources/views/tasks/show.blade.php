<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <span class="font-mono text-sm font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">TK-{{ $task->id }}</span>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $task->name }}</h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('time-logs.create', ['task_id' => $task->id]) }}"><x-secondary-button>Chấm công</x-secondary-button></a>
                @canany(['edit tasks', 'edit assigned tasks'])
                    <a href="{{ route('tasks.edit', $task) }}"><x-secondary-button>Chỉnh sửa</x-secondary-button></a>
                @endcanany
                <a href="{{ route('tasks.index') }}"><x-secondary-button>Quay lại</x-secondary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="{
        activeTab: '{{ $tsInitialTab }}',
        init() {
            this.$watch('activeTab', (tab) => {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                if (tab !== 'timesheet') url.searchParams.delete('tsmonth');
                window.history.replaceState({}, '', url.toString());
            });
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            {{-- Task Details --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Chi tiết công việc</h3>

                @php
                    $statusClass = match($task->status) {
                        'Đang tiến hành' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                        'Đã xong'        => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                        default          => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                    };
                @endphp

                <div class="mb-4">
                    <x-input-label value="Trạng thái" />
                    <div class="mt-1">
                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ $statusClass }}">{{ $task->status }}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <x-input-label value="Linked Project" />
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        @if($task->project)
                            <span class="font-mono text-xs font-semibold text-gray-500 dark:text-gray-400">PJ-{{ $task->project->id }}</span>
                            <a href="{{ route('projects.show', $task->project) }}" class="ml-1 text-blue-600 hover:underline">{{ $task->project->name }}</a>
                        @else
                            <span class="text-gray-400">— (standalone task)</span>
                        @endif
                    </p>
                </div>

                <div class="mb-4">
                    <x-input-label value="Mô tả" />
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $task->description ?? '—' }}</p>
                </div>

                {{-- Budget & Time Stats --}}
                @if($task->budget_hours !== null)
                @php
                    $totalUsed    = $taskTotalSpent + $taskTotalOt;
                    $budgetPct    = $task->budget_hours > 0 ? round($totalUsed / $task->budget_hours * 100) : 0;
                    $isOverBudget = $totalUsed > $task->budget_hours;
                @endphp
                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700/40 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Budget & Time</span>
                        <span class="text-xs font-semibold {{ $isOverBudget ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-300' }}">{{ $budgetPct }}%</span>
                    </div>
                    <div class="bg-white dark:bg-gray-900 rounded h-2 border border-gray-300 dark:border-gray-600 overflow-hidden mb-3">
                        <div class="{{ $isOverBudget ? 'bg-red-500' : 'bg-gray-800 dark:bg-gray-100' }} h-2" style="width: {{ min($budgetPct, 100) }}%"></div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">Budget</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format($task->budget_hours, 1) }}h</p>
                        </div>
                        <div>
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">Normal</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format($taskTotalSpent, 1) }}h</p>
                        </div>
                        <div>
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">OT</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format($taskTotalOt, 1) }}h</p>
                        </div>
                        <div>
                            <p class="text-gray-400 dark:text-gray-500 mb-0.5">Còn lại</p>
                            <p class="font-semibold {{ $taskRemaining < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ number_format($taskRemaining, 1) }}h
                            </p>
                        </div>
                    </div>
                </div>
                @elseif($taskTotalSpent > 0 || $taskTotalOt > 0)
                <div class="mb-4 flex gap-4 text-xs text-gray-500 dark:text-gray-400">
                    @if($taskTotalSpent > 0)<span>Normal: <strong class="text-gray-700 dark:text-gray-200">{{ number_format($taskTotalSpent, 1) }}h</strong></span>@endif
                    @if($taskTotalOt > 0)<span>OT: <strong class="text-orange-500">{{ number_format($taskTotalOt, 1) }}h</strong></span>@endif
                </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label value="Ngày bắt đầu" />
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $task->start_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <x-input-label value="Ngày kết thúc dự kiến" />
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $task->expected_end_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <x-input-label value="Ngày kết thúc thực tế" />
                        <p class="mt-1 text-sm {{ $task->actual_end_date ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $task->actual_end_date?->format('d/m/Y') ?? '—' }}
                        </p>
                    </div>
                </div>

                <div>
                    <x-input-label value="Người được phân công" />
                    <div class="mt-2 space-y-1">
                        @forelse($task->assignees as $assignee)
                            <a href="{{ route('users.show', $assignee) }}" class="flex items-center gap-2 hover:opacity-80 transition rounded px-1 py-0.5">
                                <x-user-status :user="$assignee" />
                            </a>
                        @empty
                            <span class="text-sm text-gray-400">Không có</span>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <div>
                {{-- Tab Bar --}}
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button @click="activeTab = 'comments'"
                        :class="activeTab === 'comments'
                            ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="px-5 py-3 text-sm font-medium -mb-px transition">
                        Bình luận
                        @if($task->comments->isNotEmpty())
                            <span class="ml-1.5 text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 px-1.5 py-0.5 rounded-full">{{ $task->comments->count() }}</span>
                        @endif
                    </button>
                    <button @click="activeTab = 'activity'"
                        :class="activeTab === 'activity'
                            ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="px-5 py-3 text-sm font-medium -mb-px transition">
                        Nhật ký hoạt động
                    </button>
                    @canany(['view own timesheet', 'view team timesheet', 'view all timesheet'])
                    <button @click="activeTab = 'timesheet'"
                        :class="activeTab === 'timesheet'
                            ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="px-5 py-3 text-sm font-medium -mb-px transition">
                        Timesheet
                    </button>
                    @endcanany
                </div>

                {{-- Comments Panel --}}
                <div x-show="activeTab === 'comments'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-b-lg sm:rounded-tr-lg p-6">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-5">Bình luận</h3>
                    @include('partials.comments', [
                        'commentable'     => $task,
                        'commentableType' => 'task',
                    ])
                </div>

                {{-- Activity Log Panel --}}
                <div x-show="activeTab === 'activity'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-b-lg sm:rounded-tr-lg p-6">
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Nhật ký hoạt động</h3>

                    @if($activities->isEmpty())
                        <p class="text-sm text-gray-400">Chưa có hoạt động nào.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($activities as $activity)
                                <div class="flex gap-4 text-sm border-l-2 border-indigo-300 pl-4 py-1">
                                    <div class="text-gray-400 whitespace-nowrap w-32 shrink-0">
                                        {{ $activity->created_at->format('d/m/y H:i') }}
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $activity->causer?->name ?? 'System' }}
                                        </span>
                                        <span class="text-gray-500 ml-1">{{ $activity->description }}</span>

                                        @php $changes = $activity->properties['attributes'] ?? []; @endphp
                                        @if(count($changes))
                                            <div class="mt-1 space-y-0.5">
                                                @foreach($changes as $key => $newVal)
                                                    @php $oldVal = $activity->properties['old'][$key] ?? null; @endphp
                                                    <div class="text-xs text-gray-500">
                                                        <span class="font-medium">{{ str_replace('_', ' ', $key) }}</span>:
                                                        @if($oldVal !== null)
                                                            <span class="line-through text-red-400">{{ $oldVal }}</span> →
                                                        @endif
                                                        <span class="text-green-600">{{ $newVal }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Timesheet Panel --}}
                @canany(['view own timesheet', 'view team timesheet', 'view all timesheet'])
                <div x-show="activeTab === 'timesheet'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-b-lg sm:rounded-tr-lg">
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
                        $nDays = $tsDays->count();

                        $bgData1 = 'bg-white dark:bg-gray-800';
                        $bgData2 = 'bg-gray-50 dark:bg-gray-800';
                        $bgHead  = 'bg-gray-50 dark:bg-gray-700';
                        $bgTot   = 'bg-gray-100 dark:bg-gray-700';
                        $edge    = 'shadow-[2px_0_5px_-1px_rgba(0,0,0,0.12)] dark:shadow-[2px_0_5px_-1px_rgba(0,0,0,0.40)]';

                        $thC1 = "sticky left-0 z-20 {$bgHead} w-[180px] min-w-[180px] max-w-[180px] px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap";
                        $thC2 = "sticky left-[180px] z-20 {$bgHead} w-[88px] min-w-[88px] {$edge} px-2 py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase border-r border-gray-200 dark:border-gray-600";
                        $tdC1 = "sticky left-0 z-10 {$bgData1} w-[180px] min-w-[180px] max-w-[180px] px-3 py-2 whitespace-nowrap overflow-hidden text-ellipsis";
                        $tdC2 = "sticky left-[180px] z-10 {$bgData2} w-[88px] min-w-[88px] {$edge} px-2 py-1.5 text-center border-r border-gray-200 dark:border-gray-600";
                        $totC1 = "sticky left-0 z-10 {$bgTot} w-[180px] min-w-[180px] max-w-[180px] px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300";
                        $totC2 = "sticky left-[180px] z-10 {$bgTot} w-[88px] min-w-[88px] {$edge} px-2 py-1.5 text-center font-semibold border-r border-gray-200 dark:border-gray-600";
                    @endphp

                    {{-- Month nav --}}
                    <div class="px-4 pt-4 pb-2 flex items-center gap-2">
                        <a href="{{ route('tasks.show', ['task' => $task->id, 'tab' => 'timesheet', 'tsmonth' => $tsPrevMonth]) }}"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition">←</a>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $tsMonthDate->translatedFormat('F Y') }}</span>
                        <a href="{{ route('tasks.show', ['task' => $task->id, 'tab' => 'timesheet', 'tsmonth' => $tsNextMonth]) }}"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition">→</a>
                        @if($tsMonthStr !== now()->format('Y-m'))
                            <a href="{{ route('tasks.show', ['task' => $task->id, 'tab' => 'timesheet']) }}"
                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline px-1">Tháng này</a>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="text-xs border-collapse" style="table-layout:fixed; width:max-content; min-width:100%">
                            <colgroup>
                                <col style="width:180px">
                                <col style="width:88px">
                                @foreach($tsDays as $__)
                                    <col style="width:56px">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="{{ $thC1 }}">Thành viên</th>
                                    <th class="{{ $thC2 }}">Tổng</th>
                                    @foreach($tsDays as $day)
                                        @php
                                            $dk      = $day->format('Y-m-d');
                                            $isHol   = in_array($dk, $tsHolidayDates);
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

                                @forelse($tsUserRows as $row)
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
                                            @if($tsCanViewSalary && $row['total_cost'] > 0)
                                                <div class="text-gray-400 text-[10px]">{{ $fmtCost($row['total_cost']) }}</div>
                                            @endif
                                            @if($tsCanViewSalary && ($row['total_ot_cost'] ?? 0) > 0)
                                                <div class="text-orange-500 text-[10px]">+{{ $fmtCost($row['total_ot_cost']) }}</div>
                                            @endif
                                        </td>
                                        @foreach($tsDays as $day)
                                            @php
                                                $dk   = $day->format('Y-m-d');
                                                $cell = $row['days'][$dk] ?? null;
                                                $wkBg = (in_array($dk, $tsHolidayDates) || $day->isWeekend()) ? 'bg-red-50/40 dark:bg-red-900/10' : '';
                                                $url  = route('time-logs.index', array_filter([
                                                    'task_id'   => $task->id,
                                                    'user_id'   => $row['user_id'],
                                                    'date_from' => $dk, 'date_to' => $dk,
                                                ]));
                                            @endphp
                                            <td class="px-0.5 py-1.5 text-center align-top {{ $wkBg }}">
                                                @if($cell && ($cell['hours'] > 0 || $cell['ot_hours'] > 0))
                                                    <a href="{{ $url }}" class="block rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/30 px-0.5 py-0.5 transition">
                                                        @if($cell['hours'] > 0)<div class="font-semibold text-gray-800 dark:text-gray-200">{{ $fmtHours($cell['hours']) }}</div>@endif
                                                        @if($cell['ot_hours'] > 0)<div class="text-orange-500">+{{ $fmtHours($cell['ot_hours']) }}</div>@endif
                                                        @if($tsCanViewSalary && $cell['cost'] > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($cell['cost']) }}</div>@endif
                                                        @if($tsCanViewSalary && ($cell['ot_cost'] ?? 0) > 0)<div class="text-orange-500 text-[10px]">+{{ $fmtCost($cell['ot_cost']) }}</div>@endif
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

                                {{-- Grand total --}}
                                @if(count($tsUserRows) > 0)
                                <tr class="border-t border-gray-200 dark:border-gray-600">
                                    <td class="{{ $totC1 }}">Tổng cộng</td>
                                    <td class="{{ $totC2 }}">
                                        <div class="text-gray-800 dark:text-gray-200">{{ $fmtHours($tsGrandTotalHours) }}</div>
                                        @if($tsGrandTotalOt > 0)<div class="text-orange-500">+{{ $fmtHours($tsGrandTotalOt) }}</div>@endif
                                        @if($tsCanViewSalary && $tsGrandTotalCost > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($tsGrandTotalCost) }}</div>@endif
                                        @if($tsCanViewSalary && $tsGrandTotalOtCost > 0)<div class="text-orange-500 text-[10px]">+{{ $fmtCost($tsGrandTotalOtCost) }}</div>@endif
                                    </td>
                                    @foreach($tsDays as $day)
                                        @php $dk = $day->format('Y-m-d'); $tot = $tsDayTotals[$dk] ?? ['hours'=>0,'ot_hours'=>0,'cost'=>0,'ot_cost'=>0]; @endphp
                                        <td class="px-0.5 py-1.5 text-center {{ $bgTot }}">
                                            @if($tot['hours'] > 0 || $tot['ot_hours'] > 0)
                                                <div class="text-gray-700 dark:text-gray-300">{{ $fmtHours($tot['hours']) }}</div>
                                                @if($tot['ot_hours'] > 0)<div class="text-orange-500">+{{ $fmtHours($tot['ot_hours']) }}</div>@endif
                                                @if($tsCanViewSalary && $tot['cost'] > 0)<div class="text-gray-400 text-[10px]">{{ $fmtCost($tot['cost']) }}</div>@endif
                                                @if($tsCanViewSalary && ($tot['ot_cost'] ?? 0) > 0)<div class="text-orange-500 text-[10px]">+{{ $fmtCost($tot['ot_cost']) }}</div>@endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endif

                            </tbody>
                        </table>
                    </div>

                    {{-- Footer summary --}}
                    @if($tsGrandTotalHours > 0 || $tsGrandTotalOt > 0)
                    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex flex-wrap gap-6 text-sm">
                            <div>
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-0.5">Tổng giờ</div>
                                <div class="font-bold text-gray-800 dark:text-gray-100">{{ number_format($tsGrandTotalHours + $tsGrandTotalOt, 1) }}h</div>
                                @if($tsGrandTotalOt > 0)
                                    <div class="text-xs text-orange-500">OT: {{ number_format($tsGrandTotalOt, 1) }}h</div>
                                @endif
                            </div>
                            @if($tsCanViewSalary && ($tsGrandTotalCost + $tsGrandTotalOtCost) > 0)
                            <div>
                                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-0.5">Tổng chi phí</div>
                                <div class="font-bold text-gray-800 dark:text-gray-100">{{ number_format($tsGrandTotalCost + $tsGrandTotalOtCost, 0, '.', ',') }} ₫</div>
                                @if($tsGrandTotalOtCost > 0)
                                    <div class="text-xs text-orange-500">OT: {{ number_format($tsGrandTotalOtCost, 0, '.', ',') }} ₫</div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @endcanany

            </div>{{-- /tabs --}}

        </div>
    </div>
</x-app-layout>
