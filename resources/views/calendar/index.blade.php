<x-app-layout>
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
                {{-- View switcher --}}
                @foreach(['month' => 'Month', 'week' => 'Week', 'day' => 'Day'] as $v => $label)
                    <a href="{{ route('calendar.index', ['view' => $v, 'date' => $date->toDateString()]) }}"
                        class="px-3 py-1.5 text-sm rounded-lg border transition
                            {{ $view === $v
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400' }}">
                        {{ $label }}
                    </a>
                @endforeach

                {{-- Navigation --}}
                <a href="{{ route('calendar.index', ['view' => $view, 'date' => $prevDate]) }}"
                    class="px-3 py-1.5 text-sm rounded-lg border bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400 transition">
                    ‹ Prev
                </a>
                <a href="{{ route('calendar.index', ['view' => $view, 'date' => now()->toDateString()]) }}"
                    class="px-3 py-1.5 text-sm rounded-lg border bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400 transition">
                    Today
                </a>
                <a href="{{ route('calendar.index', ['view' => $view, 'date' => $nextDate]) }}"
                    class="px-3 py-1.5 text-sm rounded-lg border bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400 transition">
                    Next ›
                </a>

                @can('module calendar')
                    <button onclick="openEventModal()" class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                        + New Event
                    </button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('success') }}</div>
            @endif

            @php
                $typeColor = fn($t) => match($t) {
                    'interview'      => 'bg-blue-500',
                    'company_event'  => 'bg-emerald-500',
                    default          => 'bg-indigo-500',
                };
                $today = now()->toDateString();
            @endphp

            {{-- MONTH VIEW --}}
            @if($view === 'month')
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
                    {{-- Day headers --}}
                    <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700">
                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
                            <div class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide text-center">{{ $d }}</div>
                        @endforeach
                    </div>

                    {{-- Weeks --}}
                    @php $cursor = $calStart->copy(); @endphp
                    @while($cursor->lte($calEnd))
                        <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700 last:border-0">
                            @for($d = 0; $d < 7; $d++)
                                @php
                                    $isToday   = $cursor->toDateString() === $today;
                                    $isInMonth = $cursor->month === $date->month;
                                    $dayEvents = $events->get($cursor->toDateString(), collect());
                                @endphp
                                <div class="min-h-[90px] px-2 py-1.5 border-r border-gray-100 dark:border-gray-700 last:border-0
                                    {{ !$isInMonth ? 'bg-gray-50 dark:bg-gray-900/40' : '' }}">

                                    <div class="text-xs font-semibold mb-1 w-6 h-6 flex items-center justify-center rounded-full
                                        {{ $isToday ? 'bg-indigo-600 text-white' : ($isInMonth ? 'text-gray-700 dark:text-gray-300' : 'text-gray-300 dark:text-gray-600') }}">
                                        {{ $cursor->day }}
                                    </div>

                                    @foreach($dayEvents->take(3) as $ev)
                                        <div class="text-xs mb-0.5 px-1 py-0.5 rounded truncate text-white {{ $typeColor($ev->event_type) }}"
                                            title="{{ $ev->name }} {{ $ev->start_at->format('H:i') }}">
                                            {{ $ev->start_at->format('H:i') }} {{ $ev->name }}
                                        </div>
                                    @endforeach
                                    @if($dayEvents->count() > 3)
                                        <div class="text-xs text-gray-400">+{{ $dayEvents->count() - 3 }} more</div>
                                    @endif
                                </div>
                                @php $cursor->addDay(); @endphp
                            @endfor
                        </div>
                    @endwhile
                </div>

            {{-- WEEK VIEW --}}
            @elseif($view === 'week')
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
                    <div class="grid grid-cols-7 divide-x divide-gray-200 dark:divide-gray-700">
                        @php $cursor = $calStart->copy(); @endphp
                        @for($d = 0; $d < 7; $d++)
                            @php
                                $isToday   = $cursor->toDateString() === $today;
                                $dayEvents = $events->get($cursor->toDateString(), collect());
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
                                    @foreach($dayEvents as $ev)
                                        <div class="text-xs px-1.5 py-1 rounded text-white {{ $typeColor($ev->event_type) }}"
                                            title="{{ $ev->name }}">
                                            <div class="font-medium truncate">{{ $ev->name }}</div>
                                            <div class="opacity-80">{{ $ev->start_at->format('H:i') }}–{{ $ev->end_at->format('H:i') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @php $cursor->addDay(); @endphp
                        @endfor
                    </div>
                </div>

            {{-- DAY VIEW --}}
            @else
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-200">
                            {{ $date->format('l, d F Y') }}
                        </h3>
                    </div>
                    @php $dayEvents = $events->get($date->toDateString(), collect()); @endphp
                    @forelse($dayEvents as $ev)
                        <div class="flex gap-4 px-5 py-4 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <div class="w-24 text-sm text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">
                                {{ $ev->start_at->format('H:i') }}<br>
                                <span class="text-xs">{{ $ev->end_at->format('H:i') }}</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="w-2 h-2 rounded-full {{ $typeColor($ev->event_type) }} shrink-0"></span>
                                    <span class="font-medium text-gray-800 dark:text-gray-100">{{ $ev->name }}</span>
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
                    @empty
                        <div class="px-5 py-12 text-center text-sm text-gray-400">No events today.</div>
                    @endforelse
                </div>
            @endif

        </div>
    </div>

    <x-event-modal />
</x-app-layout>
