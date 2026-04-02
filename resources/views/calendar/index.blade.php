<x-app-layout>
    @push('scripts')
        <script>
        window.addEventListener('load', function () {
            const typeColors = {
                internal_meeting: '#6366f1',
                interview:        '#3b82f6',
                company_event:    '#10b981',
                leave:            '#eab308',
                ot:               '#f97316',
            };

            const dot = (color) =>
                `<span style="width:8px;height:8px;border-radius:50%;background:${color};display:inline-block;flex-shrink:0;margin-right:6px"></span>`;

            new TomSelect('#filter-types', {
                plugins: ['remove_button'],
                maxOptions: null,
                placeholder: 'All types…',
                render: {
                    option: (data, escape) =>
                        `<div style="display:flex;align-items:center">${dot(typeColors[data.value] || '#6366f1')}${escape(data.text)}</div>`,
                    item: (data, escape) =>
                        `<div style="display:flex;align-items:center;gap:4px">${dot(typeColors[data.value] || '#6366f1')}${escape(data.text)}</div>`,
                },
            });

            new TomSelect('#filter-location', {
                plugins: ['remove_button'],
                maxOptions: null,
                placeholder: 'All locations…',
                create: false,
            });
        });
        </script>
    @endpush

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Calendar
                <span class="text-base font-normal text-gray-500 dark:text-gray-400 ml-2">
                    @if($view === 'month')   {{ $date->format('F Y') }}
                    @elseif($view === 'week') {{ $calStart->format('d M') }} – {{ $calEnd->format('d M Y') }}
                    @else                    {{ $date->format('d F Y') }}
                    @endif
                </span>
            </h2>

            <div class="flex items-center gap-2 flex-wrap">
                @foreach(['month' => 'Month', 'week' => 'Week', 'day' => 'Day'] as $v => $label)
                    <a href="{{ route('calendar.index', array_merge(['view' => $v, 'date' => $date->toDateString()], $filterParams)) }}"
                        class="px-3 py-1.5 text-sm rounded-lg border transition
                            {{ $view === $v
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400' }}">
                        {{ $label }}
                    </a>
                @endforeach

                <a href="{{ route('calendar.index', array_merge(['view' => $view, 'date' => $prevDate], $filterParams)) }}"
                    class="px-3 py-1.5 text-sm rounded-lg border bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400 transition">
                    ‹ Prev
                </a>
                <a href="{{ route('calendar.index', array_merge(['view' => $view, 'date' => now()->toDateString()], $filterParams)) }}"
                    class="px-3 py-1.5 text-sm rounded-lg border bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400 transition">
                    Today
                </a>
                <a href="{{ route('calendar.index', array_merge(['view' => $view, 'date' => $nextDate], $filterParams)) }}"
                    class="px-3 py-1.5 text-sm rounded-lg border bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400 transition">
                    Next ›
                </a>

                @can('module calendar')
                    <button onclick="openEventModal()"
                        class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                        + New Event
                    </button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('success') }}</div>
            @endif

            {{-- ── Filter bar ────────────────────────────────────────── --}}
            <form method="GET" action="{{ route('calendar.index') }}"
                class="bg-white dark:bg-gray-800 shadow-sm rounded-xl px-4 py-3 flex flex-wrap gap-4 items-end">
                <input type="hidden" name="view" value="{{ $view }}">
                <input type="hidden" name="date" value="{{ $date->toDateString() }}">

                <div class="w-72">
                    <x-input-label value="Event Types" />
                    <select id="filter-types" name="filter_types[]" multiple
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                        @foreach([
                            'internal_meeting' => 'Meeting',
                            'interview'        => 'Interview',
                            'company_event'    => 'Company Event',
                            'leave'            => 'Leave',
                            'ot'               => 'OT',
                        ] as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" {{ in_array($typeKey, $filterTypes) ? 'selected' : '' }}>
                                {{ $typeLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-72">
                    <x-input-label value="Location" />
                    <select id="filter-location" name="filter_location[]" multiple
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                        @foreach($locationOptions as $loc)
                            <option value="{{ $loc }}" {{ in_array($loc, $filterLocations) ? 'selected' : '' }}>
                                {{ $loc }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2 pb-0.5">
                    <button type="submit"
                        class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                        Apply
                    </button>
                    <a href="{{ route('calendar.index', ['view' => $view, 'date' => $date->toDateString()]) }}"
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        Reset
                    </a>
                </div>
            </form>

            @php
                $typeColor = fn($t) => match($t) {
                    'interview'     => 'bg-blue-500',
                    'company_event' => 'bg-emerald-500',
                    'leave'         => 'bg-yellow-500',
                    'ot'            => 'bg-orange-500',
                    default         => 'bg-indigo-500',
                };
                $today = now()->toDateString();
            @endphp

            {{-- ── MONTH VIEW ───────────────────────────────────────── --}}
            @if($view === 'month')
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
                    <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700">
                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
                            <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide text-center">{{ $d }}</div>
                        @endforeach
                    </div>

                    @php $cursor = $calStart->copy(); @endphp
                    @while($cursor->lte($calEnd))
                        <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700 last:border-0">
                            @for($d = 0; $d < 7; $d++)
                                @php
                                    $dk        = $cursor->toDateString();
                                    $isToday   = $dk === $today;
                                    $isInMonth = $cursor->month === $date->month;
                                    $dayEvs    = $events->get($dk, collect());
                                    $dayLeaves = $leavesByDay->get($dk, collect());
                                    $dayOts    = $otsByDay->get($dk, collect());
                                    $allItems  = $dayEvs->count() + $dayLeaves->count() + $dayOts->count();
                                    $shown     = 0;
                                @endphp
                                <div class="min-h-[90px] px-2 py-1.5 border-r border-gray-100 dark:border-gray-700 last:border-0
                                    {{ !$isInMonth ? 'bg-gray-50 dark:bg-gray-900/40' : '' }}">

                                    <a href="{{ route('calendar.index', array_merge(['view' => 'day', 'date' => $dk], $filterParams)) }}"
                                        class="text-xs font-semibold mb-1 w-6 h-6 flex items-center justify-center rounded-full
                                            {{ $isToday ? 'bg-indigo-600 text-white' : ($isInMonth ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' : 'text-gray-300 dark:text-gray-600') }}">
                                        {{ $cursor->day }}
                                    </a>

                                    @foreach($dayEvs->take(3) as $ev)
                                        @php $shown++; @endphp
                                        <button type="button"
                                            data-event-id="{{ $ev->id }}"
                                            class="w-full text-left text-xs mb-0.5 px-1 py-0.5 rounded text-white {{ $typeColor($ev->event_type) }} hover:opacity-80 transition truncate block cursor-pointer">
                                            {{ $ev->start_at->format('H:i') }} {{ $ev->name }}
                                        </button>
                                    @endforeach

                                    @foreach($dayLeaves->take(max(0, 3 - $shown)) as $leave)
                                        @php $shown++; @endphp
                                        <div class="text-xs mb-0.5 px-1 py-0.5 rounded text-white bg-yellow-500 truncate"
                                            title="{{ $leave->user?->name }} · Leave · {{ $leave->hours }}h">
                                            {{ $leave->user?->name }} · Leave
                                        </div>
                                    @endforeach

                                    @foreach($dayOts->take(max(0, 3 - $shown)) as $ot)
                                        @php $shown++; @endphp
                                        <div class="text-xs mb-0.5 px-1 py-0.5 rounded text-white bg-orange-500 truncate"
                                            title="{{ $ot->user?->name }} · OT · {{ $ot->hours }}h">
                                            {{ $ot->user?->name }} · OT
                                        </div>
                                    @endforeach

                                    @if($allItems > 3)
                                        <a href="{{ route('calendar.index', array_merge(['view' => 'day', 'date' => $dk], $filterParams)) }}"
                                            class="text-xs text-gray-400 hover:underline">
                                            +{{ $allItems - 3 }} more
                                        </a>
                                    @endif
                                </div>
                                @php $cursor->addDay(); @endphp
                            @endfor
                        </div>
                    @endwhile
                </div>

            {{-- ── WEEK VIEW ────────────────────────────────────────── --}}
            @elseif($view === 'week')
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
                    <div class="grid grid-cols-7 divide-x divide-gray-200 dark:divide-gray-700">
                        @php $cursor = $calStart->copy(); @endphp
                        @for($d = 0; $d < 7; $d++)
                            @php
                                $dk        = $cursor->toDateString();
                                $isToday   = $dk === $today;
                                $dayEvs    = $events->get($dk, collect());
                                $dayLeaves = $leavesByDay->get($dk, collect());
                                $dayOts    = $otsByDay->get($dk, collect());
                            @endphp
                            <div class="flex flex-col">
                                <div class="px-3 py-3 border-b border-gray-200 dark:border-gray-700 text-center">
                                    <p class="text-xs text-gray-400 uppercase">{{ $cursor->format('D') }}</p>
                                    <p class="text-sm font-semibold mt-0.5 w-7 h-7 flex items-center justify-center rounded-full mx-auto
                                        {{ $isToday ? 'bg-indigo-600 text-white' : 'text-gray-700 dark:text-gray-200' }}">
                                        {{ $cursor->day }}
                                    </p>
                                </div>
                                <div class="flex-1 p-2 space-y-1 min-h-[200px]">
                                    @foreach($dayEvs as $ev)
                                        <button type="button"
                                            data-event-id="{{ $ev->id }}"
                                            class="w-full text-left text-xs px-1.5 py-1 rounded text-white {{ $typeColor($ev->event_type) }} hover:opacity-80 transition cursor-pointer">
                                            <div class="font-medium break-words">{{ $ev->name }}</div>
                                            <div class="opacity-80">{{ $ev->start_at->format('H:i') }}–{{ $ev->end_at->format('H:i') }}</div>
                                        </button>
                                    @endforeach

                                    @foreach($dayLeaves as $leave)
                                        <div class="text-xs px-1.5 py-1 rounded bg-yellow-500 text-white">
                                            <div class="font-medium break-words">{{ $leave->user?->name }}</div>
                                            <div class="opacity-80">Leave · {{ $leave->hours }}h</div>
                                        </div>
                                    @endforeach

                                    @foreach($dayOts as $ot)
                                        <div class="text-xs px-1.5 py-1 rounded bg-orange-500 text-white">
                                            <div class="font-medium break-words">{{ $ot->user?->name }}</div>
                                            <div class="opacity-80">OT · {{ $ot->start_at->format('H:i') }}–{{ $ot->end_at->format('H:i') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @php $cursor->addDay(); @endphp
                        @endfor
                    </div>
                </div>

            {{-- ── DAY VIEW ─────────────────────────────────────────── --}}
            @else
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-200">
                            {{ $date->format('l, d F Y') }}
                        </h3>
                    </div>
                    @php
                        $dayEvs    = $events->get($date->toDateString(), collect());
                        $dayLeaves = $leavesByDay->get($date->toDateString(), collect());
                        $dayOts    = $otsByDay->get($date->toDateString(), collect());
                    @endphp
                    @if($dayEvs->isEmpty() && $dayLeaves->isEmpty() && $dayOts->isEmpty())
                        <div class="px-5 py-12 text-center text-sm text-gray-400">No events today.</div>
                    @else
                        {{-- Events --}}
                        @foreach($dayEvs as $ev)
                            <div class="flex gap-4 px-5 py-4 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="w-24 text-sm text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">
                                    {{ $ev->start_at->format('H:i') }}<br>
                                    <span class="text-xs">{{ $ev->end_at->format('H:i') }}</span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="w-2 h-2 rounded-full {{ $typeColor($ev->event_type) }} shrink-0"></span>
                                        <button type="button"
                                            data-event-id="{{ $ev->id }}"
                                            class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 text-left cursor-pointer">
                                            {{ $ev->name }}
                                        </button>
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                            {{ \App\Models\Event::$types[$ev->event_type] ?? $ev->event_type }}
                                        </span>
                                    </div>
                                    @if($ev->location)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">📍 {{ $ev->location }}</p>
                                    @endif
                                    @if($ev->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $ev->description }}</p>
                                    @endif
                                    @if($ev->attendants->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach($ev->attendants as $att)
                                                <x-user-status :user="$att" :show-name="false" />
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- Leaves --}}
                        @foreach($dayLeaves as $leave)
                            <div class="flex gap-4 px-5 py-4 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="w-24 text-sm text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">All day</div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="w-2 h-2 rounded-full bg-yellow-500 shrink-0"></span>
                                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $leave->user?->name }}</span>
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">
                                            Leave · {{ $leave->type ?? '' }} · {{ $leave->hours }}h
                                        </span>
                                    </div>
                                    @if($leave->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $leave->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- OT --}}
                        @foreach($dayOts as $ot)
                            <div class="flex gap-4 px-5 py-4 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="w-24 text-sm text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">
                                    {{ $ot->start_at->format('H:i') }}<br>
                                    <span class="text-xs">{{ $ot->end_at->format('H:i') }}</span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="w-2 h-2 rounded-full bg-orange-500 shrink-0"></span>
                                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $ot->user?->name }}</span>
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300">
                                            OT · {{ $ot->hours }}h
                                        </span>
                                    </div>
                                    @if($ot->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $ot->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endif

        </div>
    </div>

    <x-event-modal />
</x-app-layout>
