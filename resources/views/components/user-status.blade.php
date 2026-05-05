@props(['user', 'showName' => true])
@php
    $att = \App\Services\AttendanceStatusCache::getAttendance($user->id);
    $onLeave = !$att && \App\Services\AttendanceStatusCache::isOnLeave($user->id);

    $status = match(true) {
        $att && $att->status === 'approved' && $att->type === 'on_site' => 'on_site',
        $att && $att->status === 'approved' && $att->type === 'wfh'     => 'wfh',
        $att && $att->status === 'pending'                               => 'wfh_pending',
        $onLeave                                                         => 'on_leave',
        default                                                          => 'not_checked_in',
    };

    $dotColor = match($status) {
        'on_site'     => 'bg-green-500',
        'wfh'         => 'bg-blue-500',
        'wfh_pending' => 'bg-yellow-400',
        'on_leave'    => 'bg-red-400',
        default       => 'bg-gray-400',
    };

    $statusLabel = match($status) {
        'on_site'     => 'On Site',
        'wfh'         => 'WFH',
        'wfh_pending' => 'WFH Pending',
        'on_leave'    => 'On Leave',
        default       => 'Not Checked In',
    };
@endphp

@if($showName)
    {{-- Full avatar + name row --}}
    <div class="flex items-center gap-2 min-w-0">
        <div class="relative shrink-0 w-8 h-8" title="{{ $statusLabel }}">
            @if($user->profile_picture)
                <img src="{{ asset('storage/profile_pictures/' . $user->profile_picture) }}"
                     class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-600">
            @else
                <div class="w-8 h-8 rounded-full bg-pink-100 dark:bg-pink-900 flex items-center justify-center text-pink-600 dark:text-pink-300 text-sm font-bold">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <span class="absolute bottom-0 left-0 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-gray-800 {{ $dotColor }}"></span>
        </div>
        <span class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $user->name }}</span>
    </div>
@else
    {{-- Avatar only — name appears as tooltip on hover --}}
    <div class="relative group inline-block shrink-0">
        <div class="relative w-8 h-8">
            @if($user->profile_picture)
                <img src="{{ asset('storage/profile_pictures/' . $user->profile_picture) }}"
                     class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-600">
            @else
                <div class="w-8 h-8 rounded-full bg-pink-100 dark:bg-pink-900 flex items-center justify-center text-pink-600 dark:text-pink-300 text-sm font-bold">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <span class="absolute bottom-0 left-0 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-gray-800 {{ $dotColor }}"></span>
        </div>
        {{-- Name tooltip --}}
        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 px-2 py-1 text-xs bg-gray-800 dark:bg-gray-900 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none z-20 transition-opacity duration-150">
            {{ $user->name }}
            <span class="text-gray-400 ml-1">· {{ $statusLabel }}</span>
        </span>
    </div>
@endif