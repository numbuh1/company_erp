<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Bảng điểm danh — {{ $month->isoFormat('MMMM YYYY') }}
        </h2>
    </x-slot>

    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet"/>
    <style>
        .ts-wrapper .ts-control { border-color: #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; min-height: 2.25rem; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
        .dark .ts-wrapper .ts-control { background: #111827; border-color: #374151; color: #d1d5db; }
        .dark .ts-dropdown { background: #1f2937; border-color: #374151; color: #d1d5db; }
        .dark .ts-dropdown .option:hover, .dark .ts-dropdown .option.active { background: #374151; }
        .att-cell .att-tooltip { opacity: 0; pointer-events: none; transition: opacity 0.15s; }
        .att-cell:hover .att-tooltip { opacity: 1; }
        .att-cell .att-del { opacity: 0; transition: opacity 0.15s; }
        .att-cell:hover .att-del { opacity: 1; }
        .att-row .att-row-del { opacity: 0; transition: opacity 0.15s; }
        .att-row:hover .att-row-del { opacity: 1; }
    </style>
    @endpush

    <div class="py-6" x-data="{
        modalOpen: false,
        editId: null,
        editUserId: '',
        editCheckInType: 'on_site',
        editCheckOutType: 'on_site',
        editDate: '{{ now()->toDateString() }}',
        editCheckIn: '{{ now()->format('H:i') }}',
        editCheckOut: '',
        openModal() {
            var n = new Date(), p = s => String(s).padStart(2, '0');
            this.editId          = null;
            this.editUserId      = '';
            this.editCheckInType = 'on_site';
            this.editCheckOutType= 'on_site';
            this.editDate        = n.getFullYear() + '-' + p(n.getMonth()+1) + '-' + p(n.getDate());
            this.editCheckIn     = p(n.getHours()) + ':' + p(n.getMinutes());
            this.editCheckOut    = '';
            this.modalOpen       = true;
            this.$nextTick(() => { if (window.fabTomSelect) window.fabTomSelect.clear(); });
        },
        openEdit(d) {
            this.editId          = d.id;
            this.editUserId      = String(d.userId);
            this.editCheckInType = d.checkInType;
            this.editCheckOutType= d.checkOutType;
            this.editDate        = d.date;
            this.editCheckIn     = d.checkIn;
            this.editCheckOut    = d.checkOut;
            this.modalOpen       = true;
            this.$nextTick(() => { if (window.fabTomSelect) window.fabTomSelect.setValue(String(d.userId)); });
        },
        closeModal() { this.modalOpen = false; this.editId = null; }
    }" @keydown.escape.window="closeModal()">

        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

            {{-- ── Flash messages ──────────────────────────────────────────── --}}
            @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 rounded-lg text-sm">
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 rounded-lg text-sm">
                {{ session('error') }}
            </div>
            @endif

            {{-- ── Controls bar ─────────────────────────────────────────────── --}}
            <div class="flex flex-wrap items-end gap-3 mb-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 shadow-sm">

                <form method="GET" action="{{ route('attendance.list') }}"
                      class="flex flex-wrap items-end gap-3">

                    {{-- Month --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tháng</label>
                        <input type="month" name="month" value="{{ $monthStr }}"
                               class="border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    {{-- Team --}}
                    @if($teams->isNotEmpty())
                    <div class="min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Nhóm</label>
                        <select name="team_id"
                                class="mt-0 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">— Tất cả —</option>
                            @foreach($teams as $team)
                            <option value="{{ $team->id }}" @selected($selectedTeamId == $team->id)>{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- User --}}
                    <div class="min-w-[180px]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Thành viên</label>
                        <select id="user-filter-select" name="user_id">
                            <option value="">— Tất cả —</option>
                            @foreach($allUsers as $u)
                            <option value="{{ $u->id }}" @selected($selectedUserId == $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit"
                        class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition">
                        Lọc
                    </button>
                </form>

                {{-- Month navigation --}}
                @php
                    $prevMonth = $month->copy()->subMonth()->format('Y-m');
                    $nextMonth = $month->copy()->addMonth()->format('Y-m');
                @endphp
                <div class="flex items-center gap-1">
                    <a href="{{ route('attendance.list', array_filter(['month' => $prevMonth, 'team_id' => $selectedTeamId, 'user_id' => $selectedUserId])) }}"
                       class="inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition">
                        ‹
                    </a>
                    <a href="{{ route('attendance.list', array_filter(['month' => now()->format('Y-m'), 'team_id' => $selectedTeamId, 'user_id' => $selectedUserId])) }}"
                       class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-indigo-400 hover:text-indigo-600 bg-white dark:bg-gray-700 transition">
                        Hôm nay
                    </a>
                    <a href="{{ route('attendance.list', array_filter(['month' => $nextMonth, 'team_id' => $selectedTeamId, 'user_id' => $selectedUserId])) }}"
                       class="inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition">
                        ›
                    </a>
                </div>

                {{-- Add button --}}
                @if($canCheckinForOther)
                <div class="ml-auto">
                    <button type="button" @click="openModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Thêm chấm công
                    </button>
                </div>
                @endif
            </div>

            {{-- ── Legend ───────────────────────────────────────────────────── --}}
            <div class="flex flex-wrap gap-4 mb-4 text-xs text-gray-600 dark:text-gray-400">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600"></span>On Site
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-emerald-200 dark:bg-emerald-800/40 border border-emerald-500 dark:border-emerald-600"></span>WFH
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-teal-100 dark:bg-teal-900/30 border border-teal-400 dark:border-teal-600"></span>WFH nửa ngày
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-blue-100 dark:bg-blue-900/30 border border-blue-400 dark:border-blue-600"></span>Chưa check out
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-sky-50 dark:bg-sky-900/10 border border-sky-300 dark:border-sky-700"></span>WFH chờ duyệt
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-purple-100 dark:bg-purple-900/30 border border-purple-400 dark:border-purple-600"></span>Nhiều lần (xong)
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-orange-200 dark:bg-orange-800/30 border border-orange-400 dark:border-orange-600"></span>Nhiều lần (chưa xong)
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-orange-100 dark:bg-orange-900/40 border border-orange-300 dark:border-orange-600"></span>Nghỉ phép cả ngày
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-yellow-100 dark:bg-yellow-900/40 border border-yellow-300 dark:border-yellow-600"></span>Nghỉ phép nửa ngày
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-600"></span>Vắng mặt
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600"></span>Cuối tuần / Nghỉ lễ
                </span>
            </div>

            {{-- ── Grid ─────────────────────────────────────────────────────── --}}
            @if($members->isEmpty())
            <div class="text-center py-12 text-gray-400 dark:text-gray-500 text-sm">Không có thành viên nào.</div>
            @else
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <table class="border-separate border-spacing-0 text-xs">
                    {{-- Column headers --}}
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/60">
                            <th class="sticky left-0 z-20 bg-gray-50 dark:bg-gray-900 border-b border-r border-gray-200 dark:border-gray-700
                                       px-3 py-2 text-left font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap min-w-[160px]">
                                Thành viên
                            </th>
                            @for ($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $hdr  = $month->copy()->setDay($d);
                                    $dow  = $hdr->dayOfWeek; // 0=Sun, 6=Sat
                                    $dk   = $hdr->toDateString();
                                    $isWe = $dow === 0 || $dow === 6;
                                    $isHo = in_array($dk, $holidayDates);
                                    $isTd = $dk === $today;
                                    $hdrCls = ($isWe || $isHo)
                                        ? 'text-gray-400 dark:text-gray-600 bg-gray-50 dark:bg-gray-900'
                                        : ($isTd
                                            ? 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50 dark:bg-indigo-900/20'
                                            : 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900');
                                @endphp
                                <th class="border-b border-r border-gray-200 dark:border-gray-700 px-0 py-1.5 text-center font-medium w-12 min-w-[3rem] {{ $hdrCls }}">
                                    <div>{{ $d }}</div>
                                    <div class="text-[10px] font-normal opacity-70">{{ $hdr->format('D') }}</div>
                                </th>
                            @endfor
                        </tr>
                    </thead>

                    {{-- Rows --}}
                    <tbody>
                        @foreach($members as $memberRow)
                        <tr class="group/row">
                            {{-- Sticky name cell --}}
                            <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 group-hover/row:bg-gray-50 dark:group-hover/row:bg-gray-700/40
                                       border-b border-r border-gray-200 dark:border-gray-700 px-3 py-1.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($memberRow->profile_picture)
                                    <img src="{{ asset('storage/profile_pictures/' . $memberRow->profile_picture) }}"
                                         class="w-6 h-6 rounded-full object-cover shrink-0" alt="">
                                    @else
                                    <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center shrink-0">
                                        <span class="text-indigo-600 dark:text-indigo-400 font-semibold text-[10px]">
                                            {{ mb_strtoupper(mb_substr($memberRow->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    @endif
                                    <a href="{{ route('users.show', $memberRow) }}" class="text-gray-800 dark:text-gray-200 font-medium text-xs leading-tight hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline transition-colors">{{ $memberRow->name }}</a>
                                </div>
                            </td>

                            {{-- Day cells --}}
                            @for ($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $cellDate = $month->copy()->setDay($d);
                                    $dk       = $cellDate->toDateString();
                                    $dow      = $cellDate->dayOfWeek;
                                    $isWe     = $dow === 0 || $dow === 6;
                                    $isHo     = in_array($dk, $holidayDates);
                                    $isPast   = $dk < $today;
                                    $isTd     = $dk === $today;
                                    $cellKey  = $memberRow->id . '_' . $dk;

                                    $attList  = ($attendances->get($cellKey) ?? collect())
                                                    ->filter(fn($a) => $a->status !== 'rejected')
                                                    ->values();
                                    $attCount = $attList->count();
                                    $leave    = $leavesByDay[$cellKey] ?? null;

                                    $isPartialLeave = $leave && $leave->hours !== null && $leave->hours < 8;

                                    // Determine state
                                    $att = null;
                                    if ($attCount >= 1) {
                                        if ($attCount === 1) {
                                            $att = $attList->first();
                                            if ($att->status === 'approved') {
                                                if ($att->check_out_time) {
                                                    $coType = $att->check_out_type ?? $att->type;
                                                    $state  = ($coType !== $att->type) ? 'wfh_half_day' : $att->type;
                                                } else {
                                                    $state = 'checked_in';
                                                }
                                            } elseif ($att->status === 'pending') {
                                                $state = 'pending';
                                            } else {
                                                $state = 'future';
                                            }
                                        } else {
                                            $anyNotOut = $attList->contains(fn($a) => $a->status === 'approved' && !$a->check_out_time);
                                            $state = $anyNotOut ? 'multi_in_progress' : 'multi_done';
                                        }
                                    } elseif ($isPartialLeave) {
                                        $state = 'partial_leave';
                                    } elseif ($leave) {
                                        $state = 'leave';
                                    } elseif (!$isWe && !$isHo && $isPast) {
                                        $state = 'absent';
                                    } elseif ($isWe || $isHo) {
                                        $state = 'off';
                                    } else {
                                        $state = 'future';
                                    }

                                    $bg = match($state) {
                                        'on_site'           => 'bg-green-100 dark:bg-green-900/30',
                                        'wfh'               => 'bg-emerald-200 dark:bg-emerald-800/40',
                                        'wfh_half_day'      => 'bg-teal-100 dark:bg-teal-900/30',
                                        'checked_in'        => 'bg-blue-100 dark:bg-blue-900/30',
                                        'pending'           => 'bg-sky-50 dark:bg-sky-900/10',
                                        'leave'             => 'bg-orange-100 dark:bg-orange-900/20',
                                        'partial_leave'     => 'bg-yellow-100 dark:bg-yellow-900/20',
                                        'absent'            => 'bg-red-50 dark:bg-red-900/20',
                                        'off'               => 'bg-gray-50 dark:bg-gray-700/30',
                                        'multi_done'        => 'bg-purple-100 dark:bg-purple-900/30',
                                        'multi_in_progress' => 'bg-orange-200 dark:bg-orange-800/30',
                                        default             => 'bg-white dark:bg-gray-800',
                                    };

                                    $borderCls = $isTd
                                        ? 'border-indigo-200 dark:border-indigo-700'
                                        : 'border-gray-200 dark:border-gray-700';

                                    // Single-att time strings
                                    $checkInStr  = null;
                                    $checkOutStr = null;
                                    $timeStr     = null;
                                    if ($att) {
                                        $checkInStr = $att->check_in_time
                                            ? substr($att->check_in_time, 0, 5)
                                            : ($att->created_at ? $att->created_at->format('H:i') : null);
                                        $checkOutStr = $att->check_out_time ? substr($att->check_out_time, 0, 5) : null;
                                        if ($checkOutStr) {
                                            if ($att->actual_work_hours !== null) {
                                                $timeStr = $att->actual_work_hours . 'h';
                                            } elseif ($checkInStr) {
                                                // compute from HH:MM strings
                                                [$ih, $im] = array_map('intval', explode(':', $checkInStr));
                                                [$oh, $om] = array_map('intval', explode(':', $checkOutStr));
                                                $diffH = round(max(0, ($oh * 60 + $om) - ($ih * 60 + $im)) / 60, 1);
                                                $timeStr = $diffH > 0 ? $diffH . 'h' : $checkOutStr;
                                            } else {
                                                $timeStr = $checkOutStr;
                                            }
                                        } else {
                                            $timeStr = $checkInStr;
                                        }
                                    }
                                @endphp
                                <td class="att-cell relative border-b border-r {{ $borderCls }} {{ $bg }} px-0 py-0 {{ $attCount > 1 ? 'h-auto align-top' : 'h-10 align-middle' }} text-center w-12 min-w-[3rem]
                                           {{ ($canCheckinForOther && $attCount === 1) ? 'cursor-pointer hover:ring-1 hover:ring-inset hover:ring-indigo-400' : '' }}"
                                    @if($canCheckinForOther && $attCount === 1)
                                    @click="openEdit({id: {{ $att->id }}, userId: {{ $att->user_id }}, checkInType: '{{ $att->type }}', checkOutType: '{{ $att->check_out_type ?? $att->type }}', date: '{{ $dk }}', checkIn: '{{ $checkInStr ?? '' }}', checkOut: '{{ $checkOutStr ?? '' }}'})"
                                    @endif
                                    >

                                    {{-- ── Hover tooltip ──────────────────────── --}}
                                    @if($attCount > 0 || $isPartialLeave)
                                    <div class="att-tooltip absolute bottom-full left-1/2 -translate-x-1/2 mb-1 z-30
                                                bg-gray-800 dark:bg-gray-700 text-white text-xs rounded px-2 py-1.5 whitespace-nowrap shadow-lg min-w-max">
                                        @if($attCount === 1)
                                            @if($checkInStr)<div>Vào: {{ $checkInStr }}</div>@endif
                                            @if($checkOutStr)<div>Ra: {{ $checkOutStr }}</div>@endif
                                        @elseif($attCount > 1)
                                            @foreach($attList as $ttIdx => $ttRec)
                                                @if($ttIdx > 0)<div class="border-t border-gray-600 my-0.5"></div>@endif
                                                @php
                                                    $ttIn  = $ttRec->check_in_time  ? substr($ttRec->check_in_time,  0, 5) : null;
                                                    $ttOut = $ttRec->check_out_time ? substr($ttRec->check_out_time, 0, 5) : null;
                                                    $ttLbl = $ttRec->type === 'wfh' ? 'WFH' : 'OS';
                                                    $ttCls = $ttRec->type === 'wfh' ? 'text-emerald-300' : 'text-green-300';
                                                @endphp
                                                <div class="text-[10px] font-semibold {{ $ttCls }}">{{ $ttLbl }}</div>
                                                @if($ttIn)<div>Vào: {{ $ttIn }}</div>@endif
                                                @if($ttOut)<div>Ra: {{ $ttOut }}</div>@endif
                                            @endforeach
                                        @endif
                                        @if($isPartialLeave)
                                        <div class="text-yellow-300">Nghỉ½: {{ $leave->hours }}h</div>
                                        @endif
                                    </div>
                                    @endif

                                    {{-- ── Single attendance ──────────────────── --}}
                                    @if($attCount === 1)
                                        {{-- Delete button --}}
                                        @if($canCheckinForOther)
                                        <form method="POST" action="{{ route('attendance.destroy', $att) }}"
                                              class="att-del absolute top-0 right-0"
                                              @click.stop
                                              onsubmit="return confirm('Xóa chấm công ngày {{ $dk }} của {{ addslashes($memberRow->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-4 h-4 flex items-center justify-center text-red-500 hover:text-red-700 bg-white/80 dark:bg-gray-800/80 rounded-bl text-xs leading-none font-bold">
                                                ×
                                            </button>
                                        </form>
                                        @endif
                                        {{-- Label --}}
                                        @if($state === 'on_site')
                                            <div class="flex flex-col items-center justify-center leading-tight">
                                                <span class="text-green-700 dark:text-green-400 font-semibold text-[10px]">OS</span>
                                                @if($timeStr)<span class="text-green-600 dark:text-green-500 text-[10px] opacity-80">{{ $timeStr }}</span>@endif
                                            </div>
                                        @elseif($state === 'wfh')
                                            <div class="flex flex-col items-center justify-center leading-tight">
                                                <span class="text-emerald-700 dark:text-emerald-400 font-semibold text-[10px]">WFH</span>
                                                @if($timeStr)<span class="text-emerald-600 dark:text-emerald-500 text-[10px] opacity-80">{{ $timeStr }}</span>@endif
                                            </div>
                                        @elseif($state === 'wfh_half_day')
                                            <div class="flex flex-col items-center justify-center leading-tight">
                                                <span class="text-teal-700 dark:text-teal-400 font-semibold text-[10px]">½WFH</span>
                                                @if($timeStr)<span class="text-teal-600 dark:text-teal-500 text-[10px] opacity-80">{{ $timeStr }}</span>@endif
                                            </div>
                                        @elseif($state === 'checked_in')
                                            <div class="flex flex-col items-center justify-center leading-tight">
                                                <span class="text-blue-600 dark:text-blue-400 font-semibold text-[10px]">{{ $att->type === 'wfh' ? 'WFH' : 'OS' }}</span>
                                                @if($timeStr)<span class="text-blue-500 dark:text-blue-400 text-[10px] opacity-80">{{ $timeStr }}</span>@endif
                                            </div>
                                        @elseif($state === 'pending')
                                            <div class="flex flex-col items-center justify-center leading-tight">
                                                <span class="text-sky-500 dark:text-sky-400 font-semibold text-[10px]">WFH?</span>
                                                @if($timeStr)<span class="text-sky-400 text-[10px] opacity-70">{{ $timeStr }}</span>@endif
                                            </div>
                                        @endif

                                    {{-- ── Multiple attendances ────────────────── --}}
                                    @elseif($attCount > 1)
                                        @php $multiTotalH = $attList->sum('actual_work_hours'); @endphp
                                        @foreach($attList as $atRec)
                                            @php
                                                $atIn    = $atRec->check_in_time  ? substr($atRec->check_in_time,  0, 5) : null;
                                                $atOut   = $atRec->check_out_time ? substr($atRec->check_out_time, 0, 5) : null;
                                                $atLbl   = $atRec->type === 'wfh' ? 'WFH' : 'OS';
                                                $atNotOut= $atRec->status === 'approved' && !$atRec->check_out_time;
                                                $atTxt   = $atNotOut
                                                    ? 'text-orange-700 dark:text-orange-400'
                                                    : ($atRec->type === 'wfh'
                                                        ? 'text-emerald-700 dark:text-emerald-400'
                                                        : 'text-purple-700 dark:text-purple-400');
                                            @endphp
                                            <div class="att-row relative flex items-center justify-center py-0.5
                                                        {{ !$loop->last ? 'border-b border-purple-200 dark:border-purple-800' : '' }}"
                                                 @if($canCheckinForOther)
                                                 @click.stop="openEdit({id: {{ $atRec->id }}, userId: {{ $atRec->user_id }}, checkInType: '{{ $atRec->type }}', checkOutType: '{{ $atRec->check_out_type ?? $atRec->type }}', date: '{{ $dk }}', checkIn: '{{ $atIn ?? '' }}', checkOut: '{{ $atOut ?? '' }}'})"
                                                 @endif>
                                                <div class="flex flex-col items-center leading-none">
                                                    <span class="{{ $atTxt }} font-semibold text-[10px]">{{ $atLbl }}</span>
                                                    @if($atOut)
                                                        <span class="{{ $atTxt }} text-[9px] opacity-70">{{ $atRec->actual_work_hours ?? '' }}h</span>
                                                    @elseif($atIn)
                                                        <span class="{{ $atTxt }} text-[9px] opacity-70">{{ $atIn }}</span>
                                                    @endif
                                                </div>
                                                @if($canCheckinForOther)
                                                <form method="POST" action="{{ route('attendance.destroy', $atRec) }}"
                                                      class="att-row-del absolute top-0 right-0"
                                                      @click.stop
                                                      onsubmit="return confirm('Xóa chấm công {{ $atLbl }} ngày {{ $dk }} của {{ addslashes($memberRow->name) }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="w-3.5 h-3.5 flex items-center justify-center text-red-500 hover:text-red-700 bg-white/80 dark:bg-gray-800/80 rounded-bl text-[10px] leading-none font-bold">
                                                        ×
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if($state === 'multi_done' && $multiTotalH > 0)
                                        <div class="text-[9px] text-purple-600 dark:text-purple-400 text-center pb-0.5 border-t border-purple-200 dark:border-purple-800">
                                            {{ $multiTotalH }}h
                                        </div>
                                        @endif

                                    {{-- ── Leave / absent / off ────────────────── --}}
                                    @else
                                        @if($state === 'partial_leave')
                                            <div class="flex flex-col items-center justify-center leading-tight">
                                                <span class="text-yellow-700 dark:text-yellow-400 font-semibold text-[10px]">Nghỉ½</span>
                                                @if($leave->hours)<span class="text-yellow-600 dark:text-yellow-500 text-[10px] opacity-80">{{ $leave->hours }}h</span>@endif
                                            </div>
                                        @elseif($state === 'leave')
                                            <span class="text-orange-700 dark:text-orange-400 font-semibold text-[10px]">Nghỉ</span>
                                        @elseif($state === 'absent')
                                            <span class="text-red-400 dark:text-red-500 text-[11px] font-medium">–</span>
                                        @endif
                                        {{-- off / future: empty --}}
                                    @endif

                                </td>
                            @endfor
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── Month summary ────────────────────────────────────────────── --}}
            @php
                $sumOnSite = 0; $sumWfh = 0; $sumLeave = 0; $sumAbsent = 0; $sumPartialLeave = 0;
                foreach ($members as $m) {
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $cellDate = $month->copy()->setDay($d);
                        $dk  = $cellDate->toDateString();
                        $dow = $cellDate->dayOfWeek;
                        if ($dow === 0 || $dow === 6 || in_array($dk, $holidayDates)) continue;
                        $sumKey  = $m->id . '_' . $dk;
                        $attColl = ($attendances->get($sumKey) ?? collect())
                                       ->filter(fn($a) => $a->status === 'approved');
                        $lv      = $leavesByDay[$sumKey] ?? null;
                        $isPartialLv = $lv && $lv->hours !== null && $lv->hours < 8;
                        if ($attColl->isNotEmpty()) {
                            $sumOnSite += $attColl->where('type', 'on_site')->count();
                            $sumWfh    += $attColl->where('type', 'wfh')->count();
                        } elseif ($isPartialLv) {
                            $sumPartialLeave++;
                        } elseif ($lv) {
                            $sumLeave++;
                        } elseif ($dk < $today) {
                            $sumAbsent++;
                        }
                    }
                }
            @endphp
            <!-- <div class="mt-4 flex flex-wrap gap-5 text-sm text-gray-600 dark:text-gray-400">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-green-400"></span>
                    On Site: <strong class="ml-1 text-gray-800 dark:text-gray-200">{{ $sumOnSite }}</strong>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                    WFH: <strong class="ml-1 text-gray-800 dark:text-gray-200">{{ $sumWfh }}</strong>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-orange-400"></span>
                    Nghỉ phép cả ngày: <strong class="ml-1 text-gray-800 dark:text-gray-200">{{ $sumLeave }}</strong>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                    Nghỉ phép nửa ngày: <strong class="ml-1 text-gray-800 dark:text-gray-200">{{ $sumPartialLeave }}</strong>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-red-400"></span>
                    Vắng mặt: <strong class="ml-1 text-gray-800 dark:text-gray-200">{{ $sumAbsent }}</strong>
                </span>
            </div> -->
            @endif

        </div>{{-- /max-w-full --}}

        {{-- ══════════════════════════════════════════════════════════════════
             Check-in for other user modal
        ══════════════════════════════════════════════════════════════════ --}}
        @if($canCheckinForOther)
        <div x-show="modalOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click.self="closeModal()"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">

            <div x-show="modalOpen" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                 class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md border border-gray-200 dark:border-gray-700">

                {{-- Modal header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white"
                        x-text="editId ? 'Sửa chấm công' : 'Thêm chấm công'">Thêm chấm công</h2>
                    <button type="button" @click="closeModal()"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Modal form --}}
                <form method="POST" action="{{ route('attendance.checkin-for-user') }}" class="px-5 py-4 space-y-4">
                    @csrf
                    {{-- Hidden: attendance_id for edit mode --}}
                    <input type="hidden" name="attendance_id" :value="editId ?? ''">

                    {{-- User --}}
                    <div>
                        <label for="checkin-user-select"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nhân viên <span class="text-red-500">*</span>
                        </label>
                        <select id="checkin-user-select" name="user_id" required>
                            <option value="">— Chọn nhân viên —</option>
                            @foreach($allUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}{{ $u->position ? ' · ' . $u->position : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date --}}
                    <div>
                        <label for="checkin-date"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Ngày <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="checkin-date" name="date"
                               x-model="editDate" required
                               class="block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    {{-- Check-in row: time + type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Giờ vào <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="time" id="checkin-time" name="check_in_time" lang="en-GB"
                                   x-model="editCheckIn" required
                                   class="block border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                            <div class="flex gap-3 shrink-0">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="check_in_type" value="on_site" x-model="editCheckInType" required
                                           class="text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">On Site</span>
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="check_in_type" value="wfh" x-model="editCheckInType"
                                           class="text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">WFH</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Check-out row: time + type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Giờ ra
                            <span class="text-xs font-normal text-gray-400">(tùy chọn)</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="time" id="checkout-time" name="check_out_time" lang="en-GB"
                                   x-model="editCheckOut"
                                   class="block border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                            <div class="flex gap-3 shrink-0">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="check_out_type" value="on_site" x-model="editCheckOutType"
                                           class="text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">On Site</span>
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="check_out_type" value="wfh" x-model="editCheckOutType"
                                           class="text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">WFH</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="closeModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                            Hủy
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition">
                            Lưu
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>{{-- /x-data --}}

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterEl = document.getElementById('user-filter-select');
        if (filterEl) {
            new TomSelect(filterEl, {
                placeholder: '— Tất cả —',
                allowEmptyOption: true,
                maxOptions: 300,
            });
        }

        const el = document.getElementById('checkin-user-select');
        if (el) {
            window.fabTomSelect = new TomSelect(el, {
                placeholder: '— Chọn nhân viên —',
                allowEmptyOption: true,
                maxOptions: 200,
            });
        }
    });
    </script>
    @endpush

</x-app-layout>
