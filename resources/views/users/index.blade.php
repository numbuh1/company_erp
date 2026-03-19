<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Users</h2>

            <a href="{{ route('users.create') }}">
                <x-primary-button>Create User</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto">

            <div class="bg-white dark:bg-gray-800 shadow rounded">

                <table class="min-w-full">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Email</th>
                            <th class="px-4 py-2 text-left">Roles</th>
                            <th class="px-4 py-2 text-left">Teams</th>
                            <th class="px-4 py-2 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($users as $user)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $user->name }}</td>
                                <td class="px-4 py-2">{{ $user->email }}</td>

                                <td class="px-4 py-2">
                                    @foreach($user->roles as $role)
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </td>

                                <td class="px-4 py-2">
								    <div class="flex flex-wrap gap-1">
								        @forelse($user->teams as $team)
								            <span class="text-xs px-2 py-0.5 rounded
								                {{ $team->pivot->is_leader
								                    ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
								                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
								                {{ $team->name }}{{ $team->pivot->is_leader ? ' ★' : '' }}
								            </span>
								        @empty
								            <span class="text-xs text-gray-400">—</span>
								        @endforelse
								    </div>
								</td>

                                <td class="px-4 py-2 text-right space-x-2">
                                	@if($user->id === auth()->user()->id)
	                                    <a href="{{ route('users.edit', $user) }}">
	                                        <x-secondary-button>Edit</x-secondary-button>
	                                    </a>
	                                @else
	                                	@canany(['edit team user', 'edit all user'])
		                                	<a href="{{ route('users.edit', $user) }}">
		                                        <x-secondary-button>Edit</x-secondary-button>
		                                    </a>
		                                @endcanany
	                                @endif

                                    @canany(['delete team user', 'delete all user'])
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline">
                                            @csrf @method('DELETE')
                                            <x-danger-button onclick="return confirm('Delete user?')">
                                                Delete
                                            </x-danger-button>
                                        </form>
                                    @endcanany
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $users->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>