<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg divide-y divide-gray-100 dark:divide-gray-700">

                @if($notifications->isEmpty())
                    <div class="px-6 py-12 text-center text-sm text-gray-400">
                        No notifications yet.
                    </div>
                @else
                    @foreach($notifications as $notification)
                        @php
                            $data     = $notification->data;
                            $isUnread = is_null($notification->read_at);
                            $incoming = !empty($data['incoming_user_id'])
                                ? \App\Models\User::find($data['incoming_user_id'])
                                : null;
                        @endphp
                        <a href="{{ $data['url'] }}"
                           class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ $isUnread ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">

                            {{-- Avatar --}}
                            <div class="shrink-0 w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden flex items-center justify-center">
                                @if($incoming && $incoming->profile_picture)
                                    <img src="{{ asset('storage/profile_pictures/' . $incoming->profile_picture) }}"
                                         class="w-full h-full object-cover" alt="{{ $incoming->name }}">
                                @elseif($incoming)
                                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-300">
                                        {{ strtoupper(substr($incoming->name, 0, 1)) }}
                                    </span>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-2.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $data['title'] }}
                                    </p>
                                    @if($isUnread)
                                        <span class="inline-block w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $data['description'] }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </a>
                    @endforeach

                    <div class="px-6 py-4">
                        {{ $notifications->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
