<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <span class="font-mono text-sm font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">TK-{{ $task->id }}</span>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $task->name }}</h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('time-logs.create', ['task_id' => $task->id]) }}"><x-secondary-button>{{ __('Log Time') }}</x-secondary-button></a>
                @canany(['edit tasks', 'edit assigned tasks'])
                    <a href="{{ route('tasks.edit', $task) }}"><x-secondary-button>{{ __('Edit') }}</x-secondary-button></a>
                @endcanany
                <a href="{{ route('tasks.index') }}"><x-secondary-button>{{ __('Back') }}</x-secondary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            {{-- Task Details --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">{{ __('Task Details') }}</h3>

                @php
                    $statusClass = match($task->status) {
                        'In Progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                        'Done'        => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                        default       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                    };
                @endphp

                <div class="mb-4">
                    <x-input-label value="{{ __('Status') }}" />
                    <div class="mt-1">
                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ $statusClass }}">{{ $task->status }}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <x-input-label value="Linked Project" />
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        @if($task->project)
                            <span class="font-mono text-xs font-semibold text-gray-500 dark:text-gray-400">PJ-{{ $task->project->id }}</span>
                            <a href="{{ route('projects.show', $task->project) }}" class="ml-1 text-blue-600 hover:underline">{{ $task->project->name }}</a>
                        @else
                            <span class="text-gray-400">— (standalone task)</span>
                        @endif
                    </p>
                </div>

                <div class="mb-4">
                    <x-input-label value="{{ __('Description') }}" />
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $task->description ?? '—' }}</p>
                </div>

                <div class="mb-4">
                    <x-input-label value="{{ __('Progress') }}" />
                    <div class="mt-2 flex items-center gap-3">
                        <div class="w-64 bg-gray-200 dark:bg-gray-600 rounded-full h-3">
                            <div class="bg-indigo-500 h-3 rounded-full transition-all" style="width: {{ $task->progress }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $task->progress }}%</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div>
                        <x-input-label value="{{ __('Start Date') }}" />
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $task->start_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <x-input-label value="{{ __('Expected End Date') }}" />
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $task->expected_end_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <x-input-label value="{{ __('Actual End Date') }}" />
                        <p class="mt-1 text-sm {{ $task->actual_end_date ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $task->actual_end_date?->format('d/m/Y') ?? '—' }}
                        </p>
                    </div>
                </div>

                <div>
                    <x-input-label value="{{ __('Assignees') }}" />
                    <div class="mt-2 space-y-1">
                        @forelse($task->assignees as $assignee)
                            <a href="{{ route('users.show', $assignee) }}" class="flex items-center gap-2 hover:opacity-80 transition rounded px-1 py-0.5">
                                <x-user-status :user="$assignee" />
                            </a>
                        @empty
                            <span class="text-sm text-gray-400">{{ __('None') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Comments --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-5">Comments
                    @if($task->comments->isNotEmpty())
                        <span class="ml-1.5 text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 px-1.5 py-0.5 rounded-full font-normal">{{ $task->comments->count() }}</span>
                    @endif
                </h3>
                @include('partials.comments', [
                    'commentable'     => $task,
                    'commentableType' => 'task',
                ])
            </div>

            {{-- Activity Log --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">{{ __('Activity Log') }}</h3>

                @if($activities->isEmpty())
                    <p class="text-sm text-gray-400">{{ __('No activity recorded.') }}</p>
                @else
                    <div class="space-y-3">
                        @foreach($activities as $activity)
                            <div class="flex gap-4 text-sm border-l-2 border-indigo-300 pl-4 py-1">
                                <div class="text-gray-400 whitespace-nowrap w-32 shrink-0">
                                    {{ $activity->created_at->format('d/m/y H:i') }}
                                </div>
                                <div>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">
                                        {{ $activity->causer?->name ?? 'System' }}
                                    </span>
                                    <span class="text-gray-500 ml-1">{{ $activity->description }}</span>

                                    @php $changes = $activity->properties['attributes'] ?? []; @endphp
                                    @if(count($changes))
                                        <div class="mt-1 space-y-0.5">
                                            @foreach($changes as $key => $newVal)
                                                @php $oldVal = $activity->properties['old'][$key] ?? null; @endphp
                                                <div class="text-xs text-gray-500">
                                                    <span class="font-medium">{{ str_replace('_', ' ', $key) }}</span>:
                                                    @if($oldVal !== null)
                                                        <span class="line-through text-red-400">{{ $oldVal }}</span> →
                                                    @endif
                                                    <span class="text-green-600">{{ $newVal }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
