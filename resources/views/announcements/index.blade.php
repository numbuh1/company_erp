<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Announcements</h2>
            @can('edit announcements')
                <a href="{{ route('announcements.create') }}"><x-primary-button>New Announcement</x-primary-button></a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            @forelse($announcements as $announcement)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <a href="{{ route('announcements.show', $announcement) }}"
                                class="text-lg font-semibold text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                {{ $announcement->title }}
                            </a>
                            <p class="text-xs text-gray-400 mt-1">
                                By {{ $announcement->author?->name ?? 'System' }}
                                · {{ $announcement->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <div class="flex gap-2 shrink-0 ml-4">
                            @can('edit announcements')
                                <a href="{{ route('announcements.edit', $announcement) }}">
                                    <x-secondary-button>Edit</x-secondary-button>
                                </a>
                            @endcan
                            @can('delete announcements')
                                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this announcement?')"
                                        class="inline-flex items-center px-3 py-1.5 text-sm text-red-600 border border-red-300 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        Delete
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    No announcements yet.
                </div>
            @endforelse

            <div>{{ $announcements->links() }}</div>
        </div>
    </div>
</x-app-layout>
