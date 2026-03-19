<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Time Log</h2>
            <div class="flex gap-2">
                @if($canEdit)
                    <a href="{{ route('time-logs.edit', $timeLog) }}"><x-secondary-button>Edit</x-secondary-button></a>
                @endif
                <a href="{{ route('time-logs.index') }}"><x-secondary-button>Back</x-secondary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label value="Date" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $timeLog->date->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <x-input-label value="Time Spent" />
                        <p class="mt-1 text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $timeLog->formatted_time }}</p>
                    </div>
                </div>

                <div>
                    <x-input-label value="Logged By" />
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $timeLog->user?->name ?? '—' }}</p>
                </div>

                <div>
                    <x-input-label value="Context" />
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        @if($timeLog->task)
                            <a href="{{ route('tasks.show', $timeLog->task) }}" class="text-indigo-600 hover:underline">
                                <span class="font-mono font-semibold">TK-{{ $timeLog->task_id }}</span> {{ $timeLog->task->name }}
                            </a>
                            @if($timeLog->project)
                                <span class="text-gray-400 mx-1">in</span>
                                <a href="{{ route('projects.show', $timeLog->project) }}" class="text-indigo-600 hover:underline">
                                    <span class="font-mono font-semibold">PJ-{{ $timeLog->project_id }}</span> {{ $timeLog->project->name }}
                                </a>
                            @endif
                        @elseif($timeLog->project)
                            <a href="{{ route('projects.show', $timeLog->project) }}" class="text-indigo-600 hover:underline">
                                <span class="font-mono font-semibold">PJ-{{ $timeLog->project_id }}</span> {{ $timeLog->project->name }}
                            </a>
                        @else
                            <span class="text-gray-400">Other (no project or task)</span>
                        @endif
                    </p>
                </div>

                <div>
                    <x-input-label value="Description" />
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $timeLog->description ?? '—' }}</p>
                </div>

                @if($canEdit)
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <form method="POST" action="{{ route('time-logs.destroy', $timeLog) }}">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Delete this time log?')"
                                class="text-sm text-red-600 hover:underline">Delete this log</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
