<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Bảng nhân sự</h2>
    </x-slot>

    @push('styles')
    <style>
        [x-cloak] { display: none !important; }

        /* ── Sticky name column ─────────────────────────────────────────── */
        .atts-c1        { position: sticky; left: 0; z-index: 3; }
        thead .atts-c1  { position: sticky; top: 0; z-index: 6; }
        thead th        { position: sticky; top: 0; z-index: 4; }

        /* ── Scrollbars ─────────────────────────────────────────────────── */
        .atts-scroll { scrollbar-width: thin; scrollbar-color: #94a3b8 #e2e8f0; }
        .dark .atts-scroll { scrollbar-color: #4b5563 #1e293b; }
        .atts-scroll::-webkit-scrollbar        { width: 8px; height: 8px; }
        .atts-scroll::-webkit-scrollbar-track  { background: #e2e8f0; border-radius: 4px; }
        .atts-scroll::-webkit-scrollbar-thumb  { background: #94a3b8; border-radius: 4px; }
        .atts-scroll::-webkit-scrollbar-thumb:hover { background: #64748b; }
        .atts-scroll::-webkit-scrollbar-corner { background: #e2e8f0; }
        .dark .atts-scroll::-webkit-scrollbar-track  { background: #1e293b; }
        .dark .atts-scroll::-webkit-scrollbar-thumb  { background: #4b5563; }
        .dark .atts-scroll::-webkit-scrollbar-thumb:hover { background: #6b7280; }
        .dark .atts-scroll::-webkit-scrollbar-corner { background: #1e293b; }
    </style>
    @endpush

    <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-3 py-4">

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
                        border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                    Theo dự án
                </a>
                <a href="{{ route('timesheets.attendance') }}"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                        border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400">
                    Điểm danh
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
            <form method="GET" action="{{ route('timesheets.attendance') }}"
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
                    $qDates = [
                        ['label' => 'Tuần này',    'f' => now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'),           't' => now()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d')],
                        ['label' => 'Tuần trước',  'f' => now()->subWeek()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d'), 't' => now()->subWeek()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d')],
                        ['label' => 'Tháng này',   'f' => now()->startOfMonth()->format('Y-m-d'),                                  't' => now()->endOfMonth()->format('Y-m-d')],
                        ['label' => 'Tháng trước', 'f' => now()->subMonth()->startOfMonth()->format('Y-m-d'),                      't' => now()->subMonth()->endOfMonth()->format('Y-m-d')],
                    ];
                @endphp
                <div class="flex gap-1 self-end pb-0.5">
                    @foreach($qDates as $q)
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

                {{-- Team multi-select --}}
                @if($availableTeams->isNotEmpty())
                <div class="min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Nhóm</label>
                    <select name="team_ids[]" multiple data-multi-select data-placeholder="Tất cả nhóm"
                        class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md text-sm">
                        @foreach($availableTeams as $t)
                            <option value="{{ $t->id }}" {{ in_array($t->id, $filterTeamIds) ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- User multi-select --}}
                @if($availableUsers->isNotEmpty())
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
                    <a href="{{ route('timesheets.attendance') }}"
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Đặt lại
                    </a>
                </div>
            </form>
        </div>

        {{-- ── Legend ──────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap gap-3 text-xs text-gray-600 dark:text-gray-400">
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700"></span>
                Không có giờ (đến hôm nay)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-pink-100 dark:bg-pink-900/30 border border-pink-300 dark:border-pink-700"></span>
                Giờ &lt; 8h (đến hôm nay)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-700"></span>
                Chỉ nghỉ phép
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-orange-100 dark:bg-orange-900/30 border border-orange-300 dark:border-orange-700"></span>
                Chỉ tăng ca
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600"></span>
                Cuối tuần / Nghỉ lễ
            </span>
        </div>

        {{-- ── Table ──────────────────────────────────────────────────── --}}
        @if($members->isEmpty())
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-10 text-center text-gray-400 text-sm">
                Không có thành viên nào trong bộ lọc đã chọn.
            </div>
        @else
        <div class="atts-scroll bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-auto max-h-[calc(100vh-10rem)]">
            <table class="text-xs border-separate border-spacing-0" style="width: max-content; min-width: 100%">

                {{-- ── Column headers ── --}}
                <thead>
                    <tr>
                        <th class="atts-c1 bg-gray-50 dark:bg-gray-700 border-b border-r border-gray-200 dark:border-gray-600
                                   px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap min-w-[160px] w-40">
                            Thành viên
                        </th>
                        @foreach($days as $day)
                            @php
                                $dk      = $day->format('Y-m-d');
                                $isHol   = in_array($dk, $holidayDates);
                                $isWknd  = $day->isWeekend();
                                $isToday = $dk === $today;
                                $hCls    = $isToday
                                    ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400'
                                    : ($isHol || $isWknd
                                        ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500'
                                        : 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400');
                            @endphp
                            <th class="border-b border-r border-gray-200 dark:border-gray-600 px-0 py-1.5 text-center font-medium w-12 min-w-[3rem] {{ $hCls }}">
                                <div class="font-semibold">{{ $day->format('d') }}</div>
                                <div class="text-[10px] font-normal opacity-70">{{ $day->translatedFormat('D') }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                {{-- ── Member rows ── --}}
                <tbody>
                    @foreach($members as $member)
                    <tr class="group/row hover:brightness-95 transition">

                        {{-- Sticky name cell --}}
                        <td class="atts-c1 bg-white dark:bg-gray-800 group-hover/row:bg-gray-50 dark:group-hover/row:bg-gray-700/40
                                   border-b border-r border-gray-200 dark:border-gray-700 px-3 py-1.5 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                @if($member->profile_picture)
                                    <img src="{{ asset('storage/profile_pictures/' . $member->profile_picture) }}"
                                         class="w-6 h-6 rounded-full object-cover shrink-0 border border-gray-200 dark:border-gray-600">
                                @else
                                    <div class="w-6 h-6 rounded-full bg-pink-100 dark:bg-pink-900/40 flex items-center justify-center shrink-0">
                                        <span class="text-pink-600 dark:text-pink-300 font-semibold text-[10px]">
                                            {{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}
                                        </span>
                                    </div>
                                @endif
                                <a href="{{ route('users.show', $member) }}"
                                   class="text-gray-800 dark:text-gray-200 font-medium text-xs leading-tight hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline transition-colors">
                                    {{ $member->name }}
                                </a>
                            </div>
                        </td>

                        {{-- Day cells --}}
                        @foreach($days as $day)
                            @php
                                $dk      = $day->format('Y-m-d');
                                $isHol   = in_array($dk, $holidayDates);
                                $isWknd  = $day->isWeekend();
                                $isOff   = $isWknd || $isHol;
                                $isToday = $dk === $today;
                                $isPast  = $dk <= $today;

                                $work  = (float) ($tlByUserDay[$member->id][$dk] ?? 0);
                                $leave = (float) ($lvByUserDay[$member->id][$dk] ?? 0);
                                $ot    = (float) ($otByUserDay[$member->id][$dk] ?? 0);
                                $total = $work + $leave;
                                $hasAny = $work > 0 || $leave > 0 || $ot > 0;

                                // ── Cell background priority ───────────────────
                                // 1. Off day with no data          → gray
                                // 2. Only leave (no work, no OT)   → yellow (any day)
                                // 3. Only OT   (no work, no leave) → orange (any day)
                                // 4. Past weekday, total = 0       → red
                                // 5. Past weekday, 0 < total < 8   → pink
                                // 6. Otherwise                     → white
                                if ($isOff && !$hasAny) {
                                    $cellBg = 'bg-gray-100 dark:bg-gray-700/40';
                                } elseif ($leave > 0 && $work == 0 && $ot == 0) {
                                    $cellBg = 'bg-yellow-100 dark:bg-yellow-900/30';
                                } elseif ($ot > 0 && $work == 0 && $leave == 0) {
                                    $cellBg = 'bg-orange-100 dark:bg-orange-900/30';
                                } elseif ($isPast && !$isOff) {
                                    if ($total == 0) {
                                        $cellBg = 'bg-red-100 dark:bg-red-900/30';
                                    } elseif ($total < 8) {
                                        $cellBg = 'bg-pink-100 dark:bg-pink-900/30';
                                    } else {
                                        $cellBg = '';
                                    }
                                } else {
                                    $cellBg = '';
                                }

                                $borderCls = $isToday
                                    ? 'border-indigo-200 dark:border-indigo-700'
                                    : 'border-gray-200 dark:border-gray-700';
                            @endphp
                            <td class="border-b border-r {{ $borderCls }} {{ $cellBg }}
                                       px-0.5 py-1 text-center align-top w-12 min-w-[3rem]">
                                @if($hasAny)
                                    @php
                                        $cellUrl = route('time-logs.index', [
                                            'user_id'   => $member->id,
                                            'date_from' => $dk,
                                            'date_to'   => $dk,
                                        ]);
                                    @endphp
                                    <a href="{{ $cellUrl }}" class="block px-0.5 hover:opacity-80 transition">
                                        @if($work > 0)
                                            <div class="text-[10px] font-semibold text-gray-700 dark:text-gray-300 leading-tight">
                                                {{ \App\Models\TimeLog::formatTimeShort($work) }}
                                            </div>
                                        @endif
                                        @if($leave > 0)
                                            <div class="text-[10px] text-amber-600 dark:text-amber-400 leading-tight">
                                                🏖 {{ \App\Models\TimeLog::formatTimeShort($leave) }}
                                            </div>
                                        @endif
                                        @if($ot > 0)
                                            <div class="text-[10px] text-orange-500 dark:text-orange-400 leading-tight">
                                                ⏱ {{ \App\Models\TimeLog::formatTimeShort($ot) }}
                                            </div>
                                        @endif
                                    </a>
                                @endif
                            </td>
                        @endforeach

                    </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
        @endif

    </div>
</x-app-layout>
