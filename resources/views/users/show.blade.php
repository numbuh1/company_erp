<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">User Profile</h2>
            <div class="flex gap-2">
                @if(auth()->id() === $user->id || auth()->user()->canAny(['edit team user', 'edit all user']))
                    <a href="{{ route('users.edit', $user) }}">
                        <x-primary-button>Edit</x-primary-button>
                    </a>
                @endif
                <a href="{{ route('users.index') }}">
                    <x-secondary-button>Back</x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto space-y-6">

            {{-- Basic Info --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Basic Information</h3>

                <div class="flex items-center gap-6 mb-6">
                    @if($user->profile_picture)
                        <img src="{{ asset('storage/profile_pictures/' . $user->profile_picture) }}"
                            class="w-20 h-20 rounded-full object-cover border-2 border-gray-300">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-400 text-sm">
                            No photo
                        </div>
                    @endif
                    <div>
                        <p class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $user->name }}</p>
                        @if($user->position)
                            <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ $user->position }}</p>
                        @endif
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                    </div>

                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label value="Position" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                            {{ $user->position ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <x-input-label value="Roles" />
                        <div class="flex flex-wrap gap-1 mt-1">
                            @forelse($user->roles as $role)
                                <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs px-2 py-1 rounded">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-400">No roles</span>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Leave Balance" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                            {{ rtrim(rtrim(number_format($user->leave_balance ?? 0, 2), '0'), '.') }}h
                            <a href="{{ route('users.leave-balance-history', $user) }}"
                                class="text-xs text-blue-500 ml-1">history</a>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Leave Requests --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Leave Requests</h3>

                @if($leaveRequests->isEmpty())
                    <p class="text-sm text-gray-400">No pending or approved leave requests.</p>
                @else
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left">Type</th>
                                <th class="px-3 py-2 text-left">Start</th>
                                <th class="px-3 py-2 text-left">End</th>
                                <th class="px-3 py-2 text-left">Hours</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-left">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaveRequests as $lr)
                                <tr class="border-t">
                                    <td class="px-3 py-2 capitalize">{{ $lr->type }}</td>
                                    <td class="px-3 py-2">{{ $lr->start_at->format('d/m/y') }}</td>
                                    <td class="px-3 py-2">{{ $lr->end_at->format('d/m/y') }}</td>
                                    <td class="px-3 py-2">{{ rtrim(rtrim(number_format($lr->hours, 2), '0'), '.') }}h</td>
                                    <td class="px-3 py-2">
                                        @if($lr->status === 'pending')
                                            <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded">Pending</span>
                                        @elseif($lr->status === 'approved')
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded">Approved</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-500">{{ $lr->created_at->format('d/m/y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Teams --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Teams</h3>

                @if($user->teams->isEmpty())
                    <p class="text-sm text-gray-400">Not a member of any team.</p>
                @else
                    <div class="space-y-2">
                        @foreach($user->teams as $team)
                            <div class="flex items-center justify-between border rounded px-4 py-3 dark:border-gray-600">
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                    {{ $team->name }}
                                </span>
                                @if($team->pivot->is_leader)
                                    <span class="bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 text-xs px-2 py-0.5 rounded">
                                        Leader
                                    </span>
                                @else
                                    <span class="bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 text-xs px-2 py-0.5 rounded">
                                        Member
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
