{{-- Mobile overlay --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 z-20 bg-black/50 sm:hidden"
     x-cloak>
</div>

{{-- Sidebar panel --}}
<aside x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full sm:translate-x-0'"
    class="sm:sticky top-16 left-0 z-30 sm:z-auto
           h-[calc(100vh-4rem)] w-56 shrink-0 flex flex-col
           bg-white dark:bg-gray-800
           border-r border-gray-200 dark:border-gray-700
           overflow-y-auto
           transition-transform duration-200 ease-in-out">

    @php
        $navLink = fn(...$patterns) =>
            'flex items-center px-3 py-2 text-sm rounded-lg transition-colors ' .
            (collect($patterns)->contains(fn($p) => request()->routeIs($p))
                ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium'
                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700');
    @endphp

    <nav class="flex-1 p-3 space-y-0.5">

        <a href="{{ route('dashboard') }}" class="{{ $navLink('dashboard') }}">
            Dashboard
        </a>

        <div class="pt-4 pb-1">
            <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Work</p>
        </div>
        @can('module announcements')
            <a href="{{ route('announcements.index') }}" class="{{ $navLink('announcements.*') }}">Announcements</a>
        @endcan
        @can('module projects')
            <a href="{{ route('projects.index') }}" class="{{ $navLink('projects.*') }}">Projects</a>
        @endcan
        @can('module tasks')
            <a href="{{ route('tasks.index') }}" class="{{ $navLink('tasks.*') }}">Tasks</a>
        @endcan
        @can('module timesheet')
            <a href="{{ route('timesheets.weekly') }}" class="{{ $navLink('timesheets.*', 'time-logs.*') }}">Timesheet</a>
        @endcan

        <div class="pt-4 pb-1">
            <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">People</p>
        </div>
        @can('module teams')
            <a href="{{ route('teams.index') }}" class="{{ $navLink('teams.*') }}">Teams</a>
        @endcan
        @can('module user')
            <a href="{{ route('users.index') }}" class="{{ $navLink('users.*') }}">Users</a>
        @endcan

        <div class="pt-4 pb-1">
            <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Requests</p>
        </div>
        @can('module leaves')
            <a href="{{ route('leave-requests.index') }}" class="{{ $navLink('leave-requests.*') }}">Leave Requests</a>
        @endcan
        @can('module ot')
            <a href="{{ route('overtime-requests.index') }}" class="{{ $navLink('overtime-requests.*') }}">OT Requests</a>
        @endcan

        @can('module roles')
        <div class="pt-4 pb-1">
            <p class="px-3 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Admin</p>
        </div>
            <a href="{{ route('roles.index') }}" class="{{ $navLink('roles.*') }}">Roles</a>
        @endcan

    </nav>
</aside>
