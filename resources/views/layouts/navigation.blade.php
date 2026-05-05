<nav class="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <!-- Left: hamburger (mobile) + logo -->
            <div class="flex items-center gap-3">
                <!-- Hamburger — toggles sidebar on mobile -->
                <button @click="sidebarOpen = !sidebarOpen"
                    class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition sm:hidden">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="shrink-0 flex items-center">
                    <x-application-logo class="block h-9 w-auto rounded" />
                </a>
            </div>

            <!-- Right: dark toggle + bell + profile -->
            <div class="flex items-center gap-1">

                <!-- Dark Mode Toggle -->
                <button onclick="toggleDarkMode()"
                    class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition"
                    title="Toggle dark mode">
                    <svg class="h-5 w-5 block dark:hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg class="h-5 w-5 hidden dark:block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                    </svg>
                </button>

                <!-- Notification Bell -->
                @auth
                @php
                    $allUnread    = auth()->user()->unreadNotifications()->latest()->get();
                    $bellPreviews = $allUnread->take(5);
                    $unreadCount  = $allUnread->count();
                @endphp
                <div class="relative" x-data="{ open: false, marked: false }" @click.outside="open = false">
                    <button @click="open = !open; if (open && !marked) { marked = true; markNotificationsRead(); }"
                        class="relative p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-2.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if($unreadCount > 0)
                            <span x-ref="badge"
                                class="absolute top-0.5 right-0.5 inline-flex items-center justify-center min-w-[1rem] h-4 px-0.5 text-xs font-bold text-white bg-red-500 rounded-full">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </button>

                    <div x-show="open" x-cloak
                        class="absolute right-0 top-full mt-1 z-50 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-600">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Notifications</span>
                            <a href="{{ route('notifications.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
                        </div>
                        @if($bellPreviews->isEmpty())
                            <div class="px-4 py-6 text-sm text-gray-400 text-center">No unread notifications</div>
                        @else
                            <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-80 overflow-y-auto">
                                @foreach($bellPreviews as $notification)
                                    @php
                                        $nd         = $notification->data;
                                        $nbIncoming = !empty($nd['incoming_user_id']) ? \App\Models\User::find($nd['incoming_user_id']) : null;
                                    @endphp
                                    <a href="{{ $nd['url'] }}" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                        <div class="shrink-0 w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden flex items-center justify-center">
                                            @if($nbIncoming && $nbIncoming->profile_picture)
                                                <img src="{{ asset('storage/profile_pictures/' . $nbIncoming->profile_picture) }}"
                                                     class="w-full h-full object-cover" alt="{{ $nbIncoming->name }}">
                                            @elseif($nbIncoming)
                                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-300">
                                                    {{ strtoupper(substr($nbIncoming->name, 0, 1)) }}
                                                </span>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 17h5l-1.405-2.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $nd['title'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ $nd['description'] }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                @endauth

                <!-- Profile Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('users.profile')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>

            </div>
        </div>
    </div>
</nav>
