@extends('layouts.app')

@section('title', 'Bảng điểm danh')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet"/>
<style>
    .ts-wrapper.single .ts-control { padding-right: 2rem; }
    .ts-wrapper .ts-control { border-color: #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; min-height: 2.25rem; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
    .dark .ts-wrapper .ts-control { background: #111827; border-color: #374151; color: #d1d5db; }
    .dark .ts-dropdown { background: #1f2937; border-color: #374151; color: #d1d5db; }
    .dark .ts-dropdown .option:hover, .dark .ts-dropdown .option.active { background: #374151; }
</style>
@endpush

@section('content')
<div class="py-6" x-data="{
    modalOpen: false,
    openModal() { this.modalOpen = true; this.$nextTick(() => { if (window._tsCheckin) window._tsCheckin.focus(); }); },
    closeModal() { this.modalOpen = false; }
}" @keydown.escape.window="closeModal()">

    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ── Header ──────────────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Bảng điểm danh</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $month->isoFormat('MMMM YYYY') }}
                    @if($selectedTeamId)
                        · {{ $teams->firstWhere('id', $selectedTeamId)?->name }}
                    @endif
                </p>
            </div>
            @if($canCheckinForOther)
            <button type="button" @click="openModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm chấm công
            </button>
            @endif
        </div>

        {{-- ── Flash messages ───────────────────────────────────────────────── --}}
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

        {{-- ── Filters ──────────────────────────────────────────────────────── --}}
        <form method="GET" action="{{ route('attendance.list') }}"
              class="flex flex-wrap gap-3 items-end mb-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 shadow-sm">

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

            <button type="submit"
                class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition">
                Lọc
            </button>

            {{-- Month navigation --}}
            @php
                $prevMonth = $month->copy()->subMonth()->format('Y-m');
                $nextMonth = $month->copy()->addMonth()->format('Y-m');
            @endphp
            <div class="flex items-center gap-1 ml-auto">
                <a href="{{ route('attendance.list', array_filter(['month' => $prevMonth, 'team_id' => $selectedTeamId])) }}"
                   class="inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition text-sm">
                    ‹
                </a>
                <a href="{{ route('attendance.list', array_filter(['month' => now()->format('Y-m'), 'team_id' => $selectedTeamId])) }}"
                   class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-indigo-400 hover:text-indigo-600 bg-white dark:bg-gray-700 transition">
                    Hôm nay
                </a>
                <a href="{{ route('attendance.list', array_filter(['month' => $nextMonth, 'team_id' => $selectedTeamId])) }}"
                   class="inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition text-sm">
                    ›
                </a>
            </div>
        </form>

        {{-- ── Legend ───────────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap gap-3 mb-4 text-xs">
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded bg-green-100 dark:bg-green-900/50 border border-green-300 dark:border-green-700"></div>
                <span class="text-gray-600 dark:text-gray-400">On Site</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded bg-blue-100 dark:bg-blue-900/50 border border-blue-300 dark:border-blue-700"></div>
                <span class="text-gray-600 dark:text-gray-400">WFH</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded bg-yellow-100 dark:bg-yellow-900/40 border border-yellow-300 dark:border-yellow-600"></div>
                <span class="text-gray-600 dark:text-gray-400">Nghỉ phép</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded bg-red-100 dark:bg-red-900/40 border border-red-300 dark:border-red-700"></div>
                <span class="text-gray-600 dark:text-gray-400">Vắng mặt</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-4 h-4 rounded bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600"></div>
                <span class="text-gray-600 dark:text-gray-400">Cuối tuần / Nghỉ lễ</span>
            </div>
        </div>

        {{-- ── Grid ─────────────────────────────────────────────────────────── --}}
        @if($members->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500 text-sm">Không có thành viên nào.</div>
        @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <table class="border-separate border-spacing-0 text-xs">
                {{-- ── Column headers ──────────────────────────────────── --}}
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/60">
                        {{-- Sticky name column --}}
                        <th class="sticky left-0 z-20 bg-gray-50 dark:bg-gray-900/80 border-b border-r border-gray-200 dark:border-gray-700
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
                                $hdrCls = $isWe || $isHo
                                    ? 'text-gray-400 dark:text-gray-600'
                                    : ($isTd ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400');
                            @endphp
                            <th class="border-b border-r border-gray-200 dark:border-gray-700 px-0 py-1.5 text-center font-medium w-12 min-w-[3rem] {{ $hdrCls }}">
                                <div>{{ $d }}</div>
                                <div class="text-[10px] font-normal opacity-70">
                                    {{ $hdr->format('D') }}
                                </div>
                            </th>
                        @endfor
                    </tr>
                </thead>

                {{-- ── Rows ─────────────────────────────────────────────── --}}
                <tbody>
                    @foreach($members as $memberRow)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20">

                        {{-- Sticky name cell --}}
                        <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 border-b border-r border-gray-200 dark:border-gray-700
                                   px-3 py-1.5 whitespace-nowrap">
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
                                <span class="text-gray-800 dark:text-gray-200 font-medium text-xs">{{ $memberRow->name }}</span>
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

                                $att   = $attendances->get($cellKey);   // single Attendance or null
                                $leave = $leavesByDay[$cellKey] ?? null;

                                // Determine state
                                if ($att && $att->status === 'approved') {
                                    $state = $att->type; // 'on_site' or 'wfh'
                                } elseif ($att && $att->status === 'pending') {
                                    $state = 'pending';
                                } elseif ($leave) {
                                    $state = 'leave';
                                } elseif (!$isWe && !$isHo && $isPast) {
                                    $state = 'absent';
                                } elseif ($isWe || $isHo) {
                                    $state = 'off';
                                } else {
                                    $state = 'future';
                                }

                                // Cell background
                                $bg = match($state) {
                                    'on_site' => 'bg-green-50 dark:bg-green-900/20',
                                    'wfh'     => 'bg-blue-50 dark:bg-blue-900/20',
                                    'pending' => 'bg-blue-50/50 dark:bg-blue-900/10',
                                    'leave'   => 'bg-yellow-50 dark:bg-yellow-900/20',
                                    'absent'  => 'bg-red-50 dark:bg-red-900/20',
                                    'off'     => 'bg-gray-50 dark:bg-gray-700/30',
                                    default   => 'bg-white dark:bg-gray-800',
                                };

                                // Today highlight on border
                                $borderCls = $isTd ? 'border-indigo-300 dark:border-indigo-600' : 'border-gray-200 dark:border-gray-700';

                                // Check-in time to show
                                $timeStr = null;
                                if ($att) {
                                    $timeStr = $att->check_in_time
                                        ? substr($att->check_in_time, 0, 5)
                                        : ($att->created_at ? $att->created_at->format('H:i') : null);
                                }
                            @endphp
                            <td class="border-b border-r {{ $borderCls }} {{ $bg }} px-0 py-0 h-9 text-center align-middle">
                                @if($state === 'on_site')
                                    <div class="flex flex-col items-center justify-center gap-0.5">
                                        <span class="text-green-600 dark:text-green-400 font-semibold text-[10px] leading-none">OS</span>
                                        @if($timeStr)
                                        <span class="text-green-500 dark:text-green-500 text-[10px] leading-none opacity-80">{{ $timeStr }}</span>
                                        @endif
                                    </div>
                                @elseif($state === 'wfh')
                                    <div class="flex flex-col items-center justify-center gap-0.5">
                                        <span class="text-blue-600 dark:text-blue-400 font-semibold text-[10px] leading-none">WFH</span>
                                        @if($timeStr)
                                        <span class="text-blue-500 dark:text-blue-400 text-[10px] leading-none opacity-80">{{ $timeStr }}</span>
                                        @endif
                                    </div>
                                @elseif($state === 'pending')
                                    <div class="flex flex-col items-center justify-center gap-0.5">
                                        <span class="text-blue-400 dark:text-blue-500 font-semibold text-[10px] leading-none">WFH?</span>
                                        @if($timeStr)
                                        <span class="text-blue-400 text-[10px] leading-none opacity-70">{{ $timeStr }}</span>
                                        @endif
                                    </div>
                                @elseif($state === 'leave')
                                    <span class="text-yellow-600 dark:text-yellow-400 font-semibold text-[10px]">Nghỉ</span>
                                @elseif($state === 'absent')
                                    <span class="text-red-400 dark:text-red-500 text-[11px]">–</span>
                                @else
                                    {{-- off / future: empty --}}
                                @endif
                            </td>
                        @endfor

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ── Summary row ─────────────────────────────────────────────────── --}}
        @php
            $sumOnSite = 0; $sumWfh = 0; $sumLeave = 0; $sumAbsent = 0;
            foreach ($members as $m) {
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $cellDate = $month->copy()->setDay($d);
                    $dk = $cellDate->toDateString();
                    $isWe = $cellDate->dayOfWeek === 0 || $cellDate->dayOfWeek === 6;
                    $isHo = in_array($dk, $holidayDates);
                    if ($isWe || $isHo) continue;
                    $att  = $attendances->get($m->id . '_' . $dk);
                    $lv   = $leavesByDay[$m->id . '_' . $dk] ?? null;
                    if ($att && $att->status === 'approved') {
                        if ($att->type === 'on_site') $sumOnSite++;
                        else $sumWfh++;
                    } elseif ($lv) {
                        $sumLeave++;
                    } elseif ($dk < $today) {
                        $sumAbsent++;
                    }
                }
            }
        @endphp
        <div class="mt-4 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-green-400"></span>
                On Site: <strong class="text-gray-800 dark:text-gray-200">{{ $sumOnSite }}</strong>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                WFH: <strong class="text-gray-800 dark:text-gray-200">{{ $sumWfh }}</strong>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                Nghỉ phép: <strong class="text-gray-800 dark:text-gray-200">{{ $sumLeave }}</strong>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-red-400"></span>
                Vắng mặt: <strong class="text-gray-800 dark:text-gray-200">{{ $sumAbsent }}</strong>
            </span>
        </div>
        @endif

    </div>{{-- /max-w-full --}}

    {{-- ════════════════════════════════════════════════════════════════════
         Check-in modal
    ════════════════════════════════════════════════════════════════════ --}}
    @if($canCheckinForOther)
    {{-- Backdrop --}}
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
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Thêm chấm công</h2>
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

                {{-- Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Loại <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="type" value="on_site" required
                                   class="text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">On Site</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="type" value="wfh"
                                   class="text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">WFH</span>
                        </label>
                    </div>
                </div>

                {{-- Date + Time row --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="checkin-date"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Ngày <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="checkin-date" name="date"
                               value="{{ now()->toDateString() }}" required
                               class="mt-0 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="checkin-time"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Giờ vào <span class="text-red-500">*</span>
                        </label>
                        <input type="time" id="checkin-time" name="check_in_time"
                               value="{{ now()->format('H:i') }}" required
                               class="mt-0 block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('checkin-user-select');
    if (el) {
        window._tsCheckin = new TomSelect(el, {
            placeholder: '— Chọn nhân viên —',
            allowEmptyOption: true,
            maxOptions: 200,
        });
    }
});
</script>
@endpush
