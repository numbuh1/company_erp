<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Tasks') }}</h2>
            @can('edit tasks')
                <a href="{{ route('tasks.create') }}">
                    <x-primary-button>{{ __('Create Task') }}</x-primary-button>
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Task') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Project') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Assignees') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Progress') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Start') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">End (EST)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($tasks as $task)
                            @php
                                $statusClass = match($task->status) {
                                    'In Progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                    'Done'        => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                                    default       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-4">
                                    <a href="{{ route('tasks.show', $task) }}"
                                        class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline whitespace-nowrap">
                                        TK-{{ $task->id }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $task->name }}</td>
                                <td class="px-4 py-4">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded {{ $statusClass }} whitespace-nowrap">{{ $task->status }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    @if($task->project)
                                        <a href="{{ route('projects.show', $task->project) }}"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                            <span class="font-mono text-xs font-semibold">PJ-{{ $task->project->id }}</span>
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($task->assignees as $assignee)
                                            <x-user-status :user="$assignee" :show-name="false" />
                                        @empty
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-20 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $task->progress }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $task->start_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $task->expected_end_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('tasks.show', $task) }}" title="{{ __('View') }}"
                                            class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-blue-600 hover:border-blue-400 bg-white dark:bg-gray-700 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">{{ __('View') }}</span>
                                        </a>

                                        @canany(['edit tasks', 'edit assigned tasks'])
                                            <a href="{{ route('tasks.edit', $task) }}" title="{{ __('Edit') }}"
                                                class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">{{ __('Edit') }}</span>
                                            </a>
                                        @endcanany

                                        @can('delete tasks')
                                            <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="{{ __('Delete') }}"
                                                    onclick="return confirm('Delete this task?')"
                                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">{{ __('Delete') }}</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-6 py-6 text-center text-gray-500">{{ __('No tasks found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $tasks->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
