<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <span class="font-mono text-sm font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">PJ-{{ $project->id }}</span>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $project->name }}</h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('time-logs.create', ['project_id' => $project->id]) }}"><x-secondary-button>Log Time</x-secondary-button></a>
                @canany(['edit projects', 'edit assigned projects'])
                    <a href="{{ route('projects.edit', $project) }}"><x-secondary-button>Edit</x-secondary-button></a>
                @endcanany
                <a href="{{ route('projects.index') }}"><x-secondary-button>Back</x-secondary-button></a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        @if(session('success'))
            <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 space-y-6">

                    {{-- Project Details --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Project Details</h3>

                        @php
                            $statusClass = match($project->status) {
                                'In Progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                'Done'        => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                                default       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                            };
                        @endphp

                        <div class="mb-4">
                            <x-input-label value="Status" />
                            <div class="mt-1">
                                <span class="text-xs font-medium px-2 py-0.5 rounded {{ $statusClass }}">{{ $project->status }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <x-input-label value="Description" />
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $project->description ?? '—' }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                            <div>
                                <x-input-label value="Start" />
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $project->start_date?->format('d/m/Y') ?? '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="End (Est.)" />
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $project->expected_end_date?->format('d/m/Y') ?? '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="End (Actual)" />
                                <p class="mt-1 text-sm {{ $project->actual_end_date ? 'text-green-600' : 'text-gray-400' }}">
                                    {{ $project->actual_end_date?->format('d/m/Y') ?? '—' }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Assigned Teams" />
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @forelse($project->teams as $team)
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded">{{ $team->name }}</span>
                                    @empty
                                        <span class="text-sm text-gray-400">None</span>
                                    @endforelse
                                </div>
                            </div>
                            <div>
                                <x-input-label value="Assigned Members" />
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @forelse($project->users as $member)
                                        <span class="bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 text-xs px-2 py-0.5 rounded">{{ $member->name }}</span>
                                    @empty
                                        <span class="text-sm text-gray-400">None</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Activity Log --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Activity Log</h3>

                        @if($activities->isEmpty())
                            <p class="text-sm text-gray-400">No activity recorded.</p>
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
                <div class="lg:col-span-2 space-y-6">

                    {{-- File Explorer --}}
                    <x-file-explorer
                        :model="$project"
                        route-prefix="projects"
                        :storage-path="'project_files/' . $project->id"
                        :items="$items"
                        :current-folder="$currentFolder"
                        :breadcrumb="$breadcrumb"
                        :can-upload="true"
                        :can-manage-all="$canManageAll"
                    />

                    {{-- Tasks --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200">Tasks</h3>
                            @can('edit tasks')
                                <a href="{{ route('tasks.create', ['project_id' => $project->id]) }}">
                                    <x-secondary-button>Add Task</x-secondary-button>
                                </a>
                            @endcan
                        </div>

                        @if($project->tasks->isEmpty())
                            <p class="text-sm text-gray-400">No tasks yet.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($project->tasks as $task)
                                    @php
                                        $taskStatusClass = match($task->status) {
                                            'In Progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                            'Done'        => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                                            default       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                        };
                                    @endphp
                                    <div class="flex items-center gap-3 p-3 rounded border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                        <a href="{{ route('tasks.show', $task) }}"
                                            class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline shrink-0">
                                            TK-{{ $task->id }}
                                        </a>
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('tasks.show', $task) }}" class="text-sm font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600">{{ $task->name }}</a>
                                            @if($task->assignees->isNotEmpty())
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    @foreach($task->assignees as $assignee)
                                                        <span class="bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300 text-xs px-1.5 py-0.5 rounded">{{ $assignee->name }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        <span class="text-xs font-medium px-2 py-0.5 rounded shrink-0 {{ $taskStatusClass }}">{{ $task->status }}</span>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $task->progress }}%"></div>
                                            </div>
                                            <span class="text-xs text-gray-500 w-8">{{ $task->progress }}%</span>
                                        </div>
                                        @if($task->expected_end_date)
                                            <span class="text-xs text-gray-400 shrink-0">due {{ $task->expected_end_date->format('d/m/Y') }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
            </div>
        </div>
    </div>
</x-app-layout>
