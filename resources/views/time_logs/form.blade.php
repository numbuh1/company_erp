<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ isset($timeLog) ? 'Edit Time Log' : 'Log Time' }}
            </h2>
            <a href="{{ route('time-logs.index') }}"><x-secondary-button>Back</x-secondary-button></a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ isset($timeLog) ? route('time-logs.update', $timeLog) : route('time-logs.store') }}"
                    x-data="{ time: '{{ old('time_spent', isset($timeLog) ? $timeLog->time_spent : '') }}' }">
                    @csrf
                    @if(isset($timeLog)) @method('PUT') @endif

                    {{-- Date --}}
                    <div class="mb-4">
                        <x-input-label for="date" value="Date *" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full"
                            value="{{ old('date', isset($timeLog) ? $timeLog->date->format('Y-m-d') : now()->format('Y-m-d')) }}" required />
                        <x-input-error :messages="$errors->get('date')" class="mt-1" />
                    </div>

                    @php
                        $initProjectId = old('project_id', $default_project_id ?? ($timeLog->project_id ?? null));
                        $initProject   = $initProjectId ? $projects->firstWhere('id', $initProjectId) : null;
                        $initTaskId    = old('task_id', $default_task_id ?? ($timeLog->task_id ?? null));
                        $initTask      = $initTaskId ? $tasks->firstWhere('id', $initTaskId) : null;
                    @endphp

                    {{-- Project --}}
                    <div class="mb-4">
                        <x-input-label for="project_id" value="Linked Project" />
                        <select id="project_id" name="project_id" placeholder="Search projects...">
                            <option value="">— None —</option>
                            @if($initProject)
                                <option value="{{ $initProject->id }}" selected>PJ-{{ $initProject->id }} {{ $initProject->name }}</option>
                            @endif
                        </select>
                        <x-input-error :messages="$errors->get('project_id')" class="mt-1" />
                    </div>

                    {{-- Task --}}
                    <div class="mb-4">
                        <x-input-label for="task_id" value="Linked Task" />
                        <select id="task_id" name="task_id" placeholder="Search tasks...">
                            <option value="">— None —</option>
                            @if($initTask)
                                <option value="{{ $initTask->id }}" selected>TK-{{ $initTask->id }} {{ $initTask->name }}</option>
                            @endif
                        </select>
                        <x-input-error :messages="$errors->get('task_id')" class="mt-1" />
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <x-input-label for="description" value="Description" />
                        <textarea id="description" name="description" rows="3"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="What did you work on?">{{ old('description', $timeLog->description ?? '') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    {{-- Time Spent --}}
                    <div class="mb-6">
                        <x-input-label for="time_spent" value="Time Spent (hours) *" />
                        <x-text-input id="time_spent" name="time_spent" type="number" min="0.25" max="24" step="0.25"
                            class="mt-1 block w-full" x-model="time" required />
                        <p class="mt-1 text-xs text-gray-500">Decimal hours: 0.5 = 30m, 1.5 = 1h 30m, etc.</p>
                        {{-- Quick buttons --}}
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach([0.5 => '30m', 1 => '1h', 1.5 => '1h 30m', 2 => '2h', 2.5 => '2h 30m', 3 => '3h', 4 => '4h', 6 => '6h', 8 => '8h'] as $val => $label)
                                <button type="button" @click="time = {{ $val }}"
                                    :class="time == {{ $val }} ? 'bg-indigo-100 border-indigo-400 text-indigo-700 dark:bg-indigo-900 dark:border-indigo-500 dark:text-indigo-300' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    class="text-xs px-2 py-1 rounded border transition">{{ $label }}</button>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('time_spent')" class="mt-1" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('time-logs.index') }}"><x-secondary-button type="button">Cancel</x-secondary-button></a>
                        <x-primary-button type="submit">{{ isset($timeLog) ? 'Update' : 'Log Time' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet" />
        <style>
            .ts-wrapper.single .ts-control { padding: 6px 10px; }
            .dark .ts-wrapper .ts-control,
            .dark .ts-wrapper .ts-dropdown { background: rgb(17 24 39); color: rgb(209 213 219); border-color: rgb(55 65 81); }
            .dark .ts-wrapper .ts-dropdown .option:hover,
            .dark .ts-wrapper .ts-dropdown .option.active { background: rgb(55 65 81); }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
        <script>
            const projectSelect = new TomSelect('#project_id', {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                allowEmptyOption: true,
                preload: true,
                load: function(query, callback) {
                    fetch('{{ route("projects.search") }}?q=' + encodeURIComponent(query))
                        .then(r => r.json()).then(callback).catch(() => callback());
                },
                onChange: function() {
                    taskSelect.clear();
                    taskSelect.clearOptions();
                    taskSelect.load('');
                }
            });

            const taskSelect = new TomSelect('#task_id', {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                allowEmptyOption: true,
                preload: true,
                load: function(query, callback) {
                    const projectId = projectSelect.getValue();
                    let url = '{{ route("tasks.search") }}?q=' + encodeURIComponent(query);
                    if (projectId) url += '&project_id=' + projectId;
                    fetch(url).then(r => r.json()).then(callback).catch(() => callback());
                }
            });
        </script>
    @endpush
</x-app-layout>
