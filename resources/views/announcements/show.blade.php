@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet" />
    <style>
        .ql-editor { padding: 0; font-size: 0.9rem; }
        .ql-container.ql-snow { border: none; }
        .ql-editor img { max-width: 100%; border-radius: 0.375rem; }
    </style>
@endpush

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $announcement->title }}
            </h2>
            <div class="flex gap-2">
                @can('edit announcements')
                    <a href="{{ route('announcements.edit', $announcement) }}">
                        <x-secondary-button>Edit</x-secondary-button>
                    </a>
                @endcan
                <a href="{{ route('announcements.index') }}"><x-secondary-button>Back</x-secondary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <p class="text-xs text-gray-400 mb-6">
                    By {{ $announcement->author?->name ?? 'System' }}
                    · {{ $announcement->created_at->format('d/m/Y H:i') }}
                    @if($announcement->updated_at->ne($announcement->created_at))
                        · <span class="italic">edited {{ $announcement->updated_at->format('d/m/Y H:i') }}</span>
                    @endif
                </p>

                <div class="ql-container ql-snow">
                    <div class="ql-editor text-gray-800 dark:text-gray-200">
                        {!! $announcement->content !!}
                    </div>
                </div>

                @can('delete announcements')
                    <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <form method="POST" action="{{ route('announcements.destroy', $announcement) }}">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Delete this announcement?')"
                                class="text-sm text-red-600 hover:underline">Delete announcement</button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
