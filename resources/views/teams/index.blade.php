<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Teams
            </h2>

            <a href="{{ route('teams.create') }}">
                <x-primary-button>
                    Create Team
                </x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    
                    <!-- Header -->
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Team</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Members</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Leaders</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>

                    <!-- Body -->
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">

                        @forelse($teams as $team)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">

                                <!-- Team Name -->
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                                    {{ $team->name }}
                                </td>

                                <!-- Members Count -->
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    {{ $team->users_count ?? $team->users->count() }}
                                </td>

                                <!-- Leaders -->
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    @forelse($team->leaders as $leader)
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1">
                                            {{ $leader->name }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400">None</span>
                                    @endforelse
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right space-x-2">

                                    <a href="{{ route('teams.show', $team) }}">
                                        <x-secondary-button>
                                            View
                                        </x-secondary-button>
                                    </a>

                                    <a href="{{ route('teams.edit', $team) }}">
                                        <x-secondary-button>
                                            Edit
                                        </x-secondary-button>
                                    </a>

                                    <form method="POST" action="{{ route('teams.destroy', $team) }}" class="inline">
                                        @csrf
                                        @method('DELETE')

                                        <x-danger-button onclick="return confirm('Delete this team?')">
                                            Delete
                                        </x-danger-button>
                                    </form>

                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-gray-500">
                                    No teams found.
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
                <div class="p-4">
                    {{ $teams->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>