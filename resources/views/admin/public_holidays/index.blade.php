<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Public Holidays</h2>
            <a href="{{ route('admin.public-holidays.create') }}"><x-primary-button>+ Add Holiday</x-primary-button></a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Repeats</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($holidays as $holiday)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $holiday->name }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $holiday->start_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $holiday->end_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    @if($holiday->repeats_annually)
                                        <span class="text-xs font-medium px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">Annual</span>
                                    @else
                                        <span class="text-xs text-gray-400">One-time</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.public-holidays.edit', $holiday) }}" title="Edit"
                                            class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Edit</span>
                                        </a>
                                        <form method="POST" action="{{ route('admin.public-holidays.destroy', $holiday) }}" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete" onclick="return confirm('Delete this holiday?')"
                                                class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No public holidays configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
