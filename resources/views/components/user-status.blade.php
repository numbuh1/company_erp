@php
    $today = now()->toDateString();

    // Use eager-loaded data from cache service (see Feature 4)
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

    $displayName = $user->name;
@endphp

<div class="flex items-center gap-2 min-w-0">

    {{-- Avatar with status dot inside --}}
    <div class="relative shrink-0 w-8 h-8" title="{{ $statusLabel }}">

        @if($user->profile_picture)
            <img src="{{ asset('storage/profile_pictures/' . $user->profile_picture) }}"
                 class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-600">
        @else
            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-300 text-sm font-bold">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
        @endif

        {{-- Dot overlapping bottom-left corner of avatar --}}
        <span class="absolute bottom-0 left-0 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-gray-800 {{ $dotColor }}"></span>
    </div>

    {{-- Name --}}
    <span class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $displayName }}</span>

</div>
