<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ isset($timeLog) ? __('Edit Time Log') : __('Time Log') }}
            </h2>
            <a href="{{ route('time-logs.index') }}"><x-secondary-button>{{ __('Back') }}</x-secondary-button></a>
        </div>
    </x-slot>

    <div>
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6"
                x-data="{
                    mode: '{{ old('_mode', 'single') }}',
                    time: '{{ old('time_spent', isset($timeLog) ? $timeLog->time_spent : '') }}',
                    isEdit: {{ isset($timeLog) ? 'true' : 'false' }}
                }">

                {{-- Mode toggle (create only) --}}
                @unless(isset($timeLog))
                <div class="mb-4 flex gap-2">
                    <button type="button" @click="mode = 'single'"
                        :class="mode === 'single' ? 'bg-indigo-100 border-indigo-400 text-indigo-700 dark:bg-indigo-900 dark:border-indigo-500 dark:text-indigo-300' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        class="text-sm px-3 py-1.5 rounded border transition">
                        {{ __('Single day') }}
                    </button>
                    <button type="button" @click="mode = 'multi'"
                        :class="mode === 'multi' ? 'bg-indigo-100 border-indigo-400 text-indigo-700 dark:bg-indigo-900 dark:border-indigo-500 dark:text-indigo-300' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        class="text-sm px-3 py-1.5 rounded border transition">
                        {{ __('Multiple days') }}
                    </button>
                </div>
                @endunless

                {{-- Single-day form --}}
                <form method="POST" action="{{ isset($timeLog) ? route('time-logs.update', $timeLog ?? 0) : route('time-logs.store') }}"
                    x-show="mode === 'single'" x-cloak
                    x-data="{ time: '{{ old('time_spent', isset($timeLog) ? $timeLog->time_spent : '') }}' }">
                    @csrf
                    @if(isset($timeLog)) @method('PUT') @endif

                    {{-- Date --}}
                    <div class="mb-4">
                        <x-input-label for="date" value="{{ __('Date') }} *" />
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
                        <x-input-label for="project_id" value="{{ __('Project') }}" />
                        <select id="project_id" name="project_id" placeholder="{{ __('Search project...') }}">
                            <option value="">— None —</option>
                            @if($initProject)
                                <option value="{{ $initProject->id }}" selected>{{ $initProject->project_code }} {{ $initProject->name }}</option>
                            @endif
                        </select>
                        <x-input-error :messages="$errors->get('project_id')" class="mt-1" />
                    </div>

                    {{-- Task --}}
                    <div class="mb-4">
                        <x-input-label for="task_id" value="{{ __('Task') }}" />
                        <select id="task_id" name="task_id" placeholder="{{ __('Search task...') }}">
                            <option value="">— None —</option>
                            @if($initTask)
                                <option value="{{ $initTask->id }}" selected>{{ $initTask->task_code }} {{ $initTask->name }}</option>
                            @endif
                        </select>
                        <x-input-error :messages="$errors->get('task_id')" class="mt-1" />
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <x-input-label for="description" value="{{ __('Description') }}" />
                        <textarea id="description" name="description" rows="3"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="{{ __('What did you work on?') }}">{{ old('description', $timeLog->description ?? '') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    {{-- Time Spent --}}
                    <div class="mb-6">
                        <x-input-label for="time_spent" value="{{ __('Hours') }} *" />
                        <x-text-input id="time_spent" name="time_spent" type="number" min="0.25" max="24" step="0.25"
                            class="mt-1 block w-full" x-model="time" required />
                        <p class="mt-1 text-xs text-gray-500">{{ __('Decimal hours: 0.5 = 30 min, 1.5 = 1h 30m, ...') }}</p>
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
                        <a href="{{ route('time-logs.index') }}"><x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button></a>
                        <x-primary-button type="submit">{{ isset($timeLog) ? __('Save') : __('Log Time') }}</x-primary-button>
                    </div>
                </form>

                {{-- Multi-day form --}}
                @unless(isset($timeLog))
                <form method="POST" action="{{ route('time-logs.store-bulk') }}"
                    x-show="mode === 'multi'" x-cloak
                    x-data="{
                        bulkFrom: @js(old('from_date', now()->startOfMonth()->format('Y-m-d'))),
                        bulkTo: @js(old('to_date', now()->format('Y-m-d'))),
                        preview: null,
                        previewLoading: false,
                        previewError: '',
                        previewSeq: 0,
                        loadPreview() { window.tlBulkPreview(this, {{ auth()->id() }}); },
                    }"
                    x-init="if (mode === 'multi') loadPreview(); $watch('mode', v => { if (v === 'multi') loadPreview(); })">
                    @csrf

                    {{-- Date range --}}
                    <div class="mb-4 grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="from_date" value="{{ __('From date') }} *" />
                            <x-text-input id="from_date" name="from_date" type="date" class="mt-1 block w-full"
                                value="{{ old('from_date', now()->startOfMonth()->format('Y-m-d')) }}" required
                                @change="bulkFrom = $event.target.value; loadPreview()" />
                            <x-input-error :messages="$errors->get('from_date')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="to_date" value="{{ __('To date') }} *" />
                            <x-text-input id="to_date" name="to_date" type="date" class="mt-1 block w-full"
                                value="{{ old('to_date', now()->format('Y-m-d')) }}" required
                                @change="bulkTo = $event.target.value; loadPreview()" />
                            <x-input-error :messages="$errors->get('to_date')" class="mt-1" />
                        </div>
                    </div>

                    <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-md text-sm text-blue-700 dark:text-blue-300">
                        {{ __('Each business day (excluding weekends, holidays, and approved leaves) will be filled up to 8 hours.') }}
                    </div>

                    <div class="mb-4">
                        @include('time_logs._bulk-preview')
                    </div>

                    @php
                        $bulkProjectId = old('project_id', $default_project_id ?? null);
                        $bulkProject   = $bulkProjectId ? $projects->firstWhere('id', $bulkProjectId) : null;
                        $bulkTaskId    = old('task_id', $default_task_id ?? null);
                        $bulkTask      = $bulkTaskId ? $tasks->firstWhere('id', $bulkTaskId) : null;
                    @endphp

                    {{-- Project --}}
                    <div class="mb-4">
                        <x-input-label for="bulk_project_id" value="{{ __('Project') }}" />
                        <select id="bulk_project_id" name="project_id" placeholder="{{ __('Search project...') }}">
                            <option value="">— None —</option>
                            @if($bulkProject)
                                <option value="{{ $bulkProject->id }}" selected>{{ $bulkProject->project_code }} {{ $bulkProject->name }}</option>
                            @endif
                        </select>
                        <x-input-error :messages="$errors->get('project_id')" class="mt-1" />
                    </div>

                    {{-- Task --}}
                    <div class="mb-4">
                        <x-input-label for="bulk_task_id" value="{{ __('Task') }}" />
                        <select id="bulk_task_id" name="task_id" placeholder="{{ __('Search task...') }}">
                            <option value="">— None —</option>
                            @if($bulkTask)
                                <option value="{{ $bulkTask->id }}" selected>{{ $bulkTask->task_code }} {{ $bulkTask->name }}</option>
                            @endif
                        </select>
                        <x-input-error :messages="$errors->get('task_id')" class="mt-1" />
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <x-input-label for="bulk_description" value="{{ __('Description') }}" />
                        <textarea id="bulk_description" name="description" rows="3"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="{{ __('What did you work on?') }}">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('time-logs.index') }}"><x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button></a>
                        <x-primary-button type="submit"
                            x-bind:disabled="previewLoading || !preview || preview.total_hours <= 0"
                            class="disabled:opacity-50">{{ __('Log Time') }}</x-primary-button>
                    </div>
                </form>
                @endunless
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
            const noneOpt = { id: '', text: '— None —' };

            // Single-day selects
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
                    taskSelect.addOption(noneOpt);
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

            // Multi-day selects (only when not editing)
            @unless(isset($timeLog))
            const bulkProjectSelect = new TomSelect('#bulk_project_id', {
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
                    bulkTaskSelect.clear();
                    bulkTaskSelect.clearOptions();
                    bulkTaskSelect.addOption(noneOpt);
                    bulkTaskSelect.load('');
                }
            });

            const bulkTaskSelect = new TomSelect('#bulk_task_id', {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                allowEmptyOption: true,
                preload: true,
                load: function(query, callback) {
                    const projectId = bulkProjectSelect.getValue();
                    let url = '{{ route("tasks.search") }}?q=' + encodeURIComponent(query);
                    if (projectId) url += '&project_id=' + projectId;
                    fetch(url).then(r => r.json()).then(callback).catch(() => callback());
                }
            });
            @endunless
        </script>
    @endpush
</x-app-layout>
