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
    class="fixed sm:sticky top-16 left-0 z-30 sm:z-40
           h-[calc(100vh-4rem)] w-14 shrink-0 flex flex-col
           bg-white dark:bg-gray-800
           border-r border-gray-200 dark:border-gray-700
           overflow-visible
           transition-transform duration-200 ease-in-out">

    @php
        $active = fn(...$patterns) =>
            collect($patterns)->contains(fn($p) => request()->routeIs($p));

        $catBtn = fn(bool $on) =>
            'flex items-center justify-center w-10 h-10 mx-auto rounded-lg transition-colors cursor-pointer ' .
            ($on
                ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                : 'text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300');

        $flyItem = fn(...$patterns) =>
            'flex items-center gap-2.5 px-3 py-2 text-sm rounded-md transition-colors whitespace-nowrap ' .
            (collect($patterns)->contains(fn($p) => request()->routeIs($p))
                ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-medium'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700');

        $flyout = 'absolute left-full top-0 z-50 w-56
                   bg-white dark:bg-gray-800 rounded-lg shadow-xl
                   border border-gray-200 dark:border-gray-700
                   py-1.5 overflow-hidden';
        $flyHead = 'px-3 pt-1.5 pb-2 text-xs font-semibold text-gray-400 dark:text-gray-500
                    uppercase tracking-wider border-b border-gray-100 dark:border-gray-700 mb-1';
    @endphp

    <nav class="flex-1 py-2 space-y-0.5" x-data="{ open: null }">

        {{-- ── Dashboard ─────────────────────────────────────── --}}
        <div class="relative px-2 py-0.5"
             @mouseenter="open = 'dash'" @mouseleave="open = null">
            <a href="{{ route('dashboard') }}" class="{{ $catBtn($active('dashboard')) }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </a>
            <div x-show="open === 'dash'" x-cloak
                 @mouseenter="open = 'dash'" @mouseleave="open = null"
                 class="{{ $flyout }}">
                <p class="{{ $flyHead }}">{{ __('Dashboard') }}</p>
                <div class="px-1.5 space-y-0.5">
                    <a href="{{ route('dashboard') }}" class="{{ $flyItem('dashboard') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        {{ __('Dashboard') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="mx-3 my-1 border-t border-gray-100 dark:border-gray-700"></div>

        {{-- ── Work ──────────────────────────────────────────── --}}
        <div class="relative px-2 py-0.5"
             @mouseenter="open = 'work'" @mouseleave="open = null">
            <button type="button"
                class="{{ $catBtn($active('attendance.*','announcements.*','projects.*','tasks.*','timesheets.*','time-logs.*','calendar.*')) }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </button>
            <div x-show="open === 'work'" x-cloak
                 @mouseenter="open = 'work'" @mouseleave="open = null"
                 class="{{ $flyout }}">
                <p class="{{ $flyHead }}">{{ __('Work') }}</p>
                <div class="px-1.5 space-y-0.5">
                    <a href="{{ route('attendance.index') }}" class="{{ $flyItem('attendance.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('Attendance') }}
                    </a>
                    @can('module announcements')
                    <a href="{{ route('announcements.index') }}" class="{{ $flyItem('announcements.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                        {{ __('Announcements') }}
                    </a>
                    @endcan
                    @can('module projects')
                    <a href="{{ route('projects.index') }}" class="{{ $flyItem('projects.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        {{ __('Projects') }}
                    </a>
                    @endcan
                    @can('module tasks')
                    <a href="{{ route('tasks.index') }}" class="{{ $flyItem('tasks.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        {{ __('Tasks') }}
                    </a>
                    @endcan
                    @can('module timesheet')
                    <a href="{{ route('timesheets.weekly') }}" class="{{ $flyItem('timesheets.*','time-logs.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        {{ __('Timesheet') }}
                    </a>
                    @endcan
                    @can('module calendar')
                    <a href="{{ route('calendar.index') }}" class="{{ $flyItem('calendar.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ __('Calendar') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        {{-- ── People ────────────────────────────────────────── --}}
        <div class="relative px-2 py-0.5"
             @mouseenter="open = 'people'" @mouseleave="open = null">
            <button type="button"
                class="{{ $catBtn($active('teams.*','users.*','recruitment.*','skills.*')) }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </button>
            <div x-show="open === 'people'" x-cloak
                 @mouseenter="open = 'people'" @mouseleave="open = null"
                 class="{{ $flyout }}">
                <p class="{{ $flyHead }}">{{ __('People') }}</p>
                <div class="px-1.5 space-y-0.5">
                    @can('module teams')
                    <a href="{{ route('teams.index') }}" class="{{ $flyItem('teams.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        {{ __('Teams') }}
                    </a>
                    @endcan
                    @can('module user')
                    <a href="{{ route('users.index') }}" class="{{ $flyItem('users.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        {{ __('Users') }}
                    </a>
                    @endcan
                    @can('module recruitment')
                    <a href="{{ route('recruitment.index') }}" class="{{ $flyItem('recruitment.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        {{ __('Recruitment') }}
                    </a>
                    @endcan
                    @can('edit recruitment')
                    <a href="{{ route('skills.index') }}" class="{{ $flyItem('skills.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m1.636-6.364l.707.707M12 21v-1M6.343 17.657l-.707-.707M17.657 17.657l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                        </svg>
                        {{ __('Skills') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        {{-- ── Requests ──────────────────────────────────────── --}}
        @canany(['module leaves', 'module ot'])
        <div class="relative px-2 py-0.5"
             @mouseenter="open = 'requests'" @mouseleave="open = null">
            <button type="button"
                class="{{ $catBtn($active('requests.*','leave-requests.*','overtime-requests.*')) }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </button>
            <div x-show="open === 'requests'" x-cloak
                 @mouseenter="open = 'requests'" @mouseleave="open = null"
                 class="{{ $flyout }}">
                <p class="{{ $flyHead }}">{{ __('Requests') }}</p>
                <div class="px-1.5 space-y-0.5">
                    @canany(['module leaves', 'module ot'])
                    <a href="{{ route('requests.index') }}" class="{{ $flyItem('requests.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                        </svg>
                        {{ __('All Requests') }}
                    </a>
                    @endcanany
                    @can('module leaves')
                    <a href="{{ route('leave-requests.index') }}" class="{{ $flyItem('leave-requests.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        {{ __('Leave Requests') }}
                    </a>
                    @endcan
                    @can('module ot')
                    <a href="{{ route('overtime-requests.index') }}" class="{{ $flyItem('overtime-requests.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('OT Requests') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endcanany

        {{-- ── Admin ─────────────────────────────────────────── --}}
        @canany(['module roles', 'module settings'])
        <div class="relative px-2 py-0.5"
             @mouseenter="open = 'admin'" @mouseleave="open = null">
            <button type="button"
                class="{{ $catBtn($active('roles.*','admin.settings.*','admin.public-holidays.*')) }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>
            <div x-show="open === 'admin'" x-cloak
                 @mouseenter="open = 'admin'" @mouseleave="open = null"
                 class="{{ $flyout }}">
                <p class="{{ $flyHead }}">{{ __('Admin') }}</p>
                <div class="px-1.5 space-y-0.5">
                    @can('module roles')
                    <a href="{{ route('roles.index') }}" class="{{ $flyItem('roles.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        {{ __('Roles') }}
                    </a>
                    @endcan
                    @can('module settings')
                    <a href="{{ route('admin.settings.edit') }}" class="{{ $flyItem('admin.settings.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ __('Settings') }}
                    </a>
                    <a href="{{ route('admin.public-holidays.index') }}" class="{{ $flyItem('admin.public-holidays.*') }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        {{ __('Public Holidays') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endcanany

    </nav>
</aside>
