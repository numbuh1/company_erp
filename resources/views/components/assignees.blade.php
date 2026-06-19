@props([
    'users' => collect(),
    'max'   => 5,
])

@php
    $list  = collect($users);
    $shown = $list->take($max);
    $extra = max(0, $list->count() - $max);
@endphp

@if($list->isEmpty())
    <span class="text-gray-400 text-xs">—</span>
@else
    <div class="flex items-center -space-x-2">
        @foreach($shown as $user)
            <a href="{{ route('users.show', $user) }}"
               class="relative group z-10 hover:z-20 inline-block"
               onclick="event.stopPropagation()">
                @if($user->profile_picture)
                    <img src="{{ asset('storage/profile_pictures/' . $user->profile_picture) }}"
                         class="w-7 h-7 rounded-full object-cover ring-2 ring-white dark:ring-gray-800 block"
                         alt="{{ $user->name }}">
                @else
                    <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center ring-2 ring-white dark:ring-gray-800">
                        <span class="text-indigo-600 dark:text-indigo-400 font-semibold text-[10px]">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    </div>
                @endif
                <div class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-50
                            opacity-0 group-hover:opacity-100 transition-opacity duration-150
                            bg-gray-900 dark:bg-gray-700 text-white rounded-lg px-3 py-2 text-xs
                            shadow-lg whitespace-nowrap">
                    <p class="font-semibold">{{ $user->name }}</p>
                    @if($user->position)
                        <p class="text-gray-300 mt-0.5">{{ $user->position }}</p>
                    @endif
                    @if($user->grade ?? null)
                        <p class="text-gray-400 mt-0.5">{{ $user->grade }}</p>
                    @endif
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </a>
        @endforeach
        @if($extra > 0)
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-600 ring-2 ring-white dark:ring-gray-800 text-xs font-medium text-gray-600 dark:text-gray-300 shrink-0">
                +{{ $extra }}
            </span>
        @endif
    </div>
@endif
