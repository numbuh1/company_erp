<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div id="headerIcon" class="w-7 h-7 flex items-center justify-center">
                {{-- spinner --}}
                <svg class="animate-spin w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200" id="headerTitle">{{ __('Importing data…') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $log->typeLabel() }}
                    @if($log->filename)
                        &mdash; <span class="font-mono text-xs">{{ $log->filename }}</span>
                    @endif
                    &mdash; bởi {{ $log->user?->name ?? '—' }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-5">

            {{-- Progress card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6 space-y-5">

                {{-- Progress bar --}}
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600 dark:text-gray-400 font-medium" id="statusText">{{ __('Waiting for worker to process…') }}</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-200" id="percentText">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                        <div id="progressBar"
                             class="h-4 rounded-full transition-all duration-700 ease-out bg-indigo-500"
                             style="width: 0%">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500" id="rowsText">0 / — {{ __('rows') }}</p>
                </div>

                {{-- Summary counts --}}
                <div class="grid grid-cols-3 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400" id="createdCount">0</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('Created') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="updatedCount">0</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('Updated') }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" id="skippedCount">0</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('Skipped') }}</p>
                    </div>
                </div>

                {{-- Error section (hidden until error) --}}
                <div id="errorSection" class="hidden rounded-lg border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-red-700 dark:text-red-300">{{ __('Import failed') }}</p>
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400 font-mono break-all" id="errorMessage"></p>
                        </div>
                    </div>
                </div>

                {{-- Done: redirecting notice (hidden until done) --}}
                <div id="doneSection" class="hidden text-center text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-5 h-5 text-green-500 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ __('Done! Redirecting to results…') }}
                </div>

                {{-- Action buttons (hidden until stopped) --}}
                <div id="actionSection" class="hidden flex gap-3 pt-2">
                    <a href="{{ route('import-export.log.show', $log) }}"
                       class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        {{ __('View detailed results') }}
                    </a>
                    <a href="{{ route('import-export.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        {{ __('Back') }}
                    </a>
                </div>
            </div>

            {{-- Worker hint --}}
            <p class="text-xs text-center text-gray-400 dark:text-gray-500">
                {{ __('Import is running in the background. You can leave this page — results will still be saved.') }}
            </p>

        </div>
    </div>

    @push('scripts')
    <script>
    var _LRM = {
        done:           '{{ __("Done!") }}',
        importComplete: '{{ __("Import complete") }}',
        errorOccurred:  '{{ __("An error occurred") }}',
        importFailed:   '{{ __("Import failed") }}',
        processingRow:  '{{ __("Processing row") }}',
        rows:           '{{ __("rows") }}',
    };
    (function () {
        const progressUrl = @json(route('import-export.progress-data', $log));
        const resultUrl   = @json(route('import-export.log.show', $log));

        const bar         = document.getElementById('progressBar');
        const percentText = document.getElementById('percentText');
        const statusText  = document.getElementById('statusText');
        const rowsText    = document.getElementById('rowsText');
        const createdEl   = document.getElementById('createdCount');
        const updatedEl   = document.getElementById('updatedCount');
        const skippedEl   = document.getElementById('skippedCount');
        const errorSec    = document.getElementById('errorSection');
        const errorMsg    = document.getElementById('errorMessage');
        const doneSec     = document.getElementById('doneSection');
        const actionSec   = document.getElementById('actionSection');
        const headerTitle = document.getElementById('headerTitle');
        const headerIcon  = document.getElementById('headerIcon');

        let interval;

        function setDone() {
            clearInterval(interval);
            bar.style.width = '100%';
            bar.classList.replace('bg-indigo-500', 'bg-green-500');
            percentText.textContent = '100%';
            statusText.textContent  = _LRM.done;
            headerTitle.textContent = _LRM.importComplete;
            headerIcon.innerHTML    = '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            doneSec.classList.remove('hidden');
            setTimeout(() => { window.location.href = resultUrl; }, 2000);
        }

        function setError(message) {
            clearInterval(interval);
            bar.classList.replace('bg-indigo-500', 'bg-red-500');
            statusText.textContent  = _LRM.errorOccurred;
            headerTitle.textContent = _LRM.importFailed;
            headerIcon.innerHTML    = '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            errorSec.classList.remove('hidden');
            errorMsg.textContent = message || '';
            actionSec.classList.remove('hidden');
            actionSec.style.display = 'flex';
        }

        async function poll() {
            try {
                const res  = await fetch(progressUrl);
                const data = await res.json();

                const total     = data.total_rows     || 0;
                const processed = data.processed_rows || 0;
                const pct       = data.percentage      || 0;

                bar.style.width             = pct + '%';
                percentText.textContent     = pct + '%';
                createdEl.textContent       = data.created_count || 0;
                updatedEl.textContent       = data.updated_count || 0;
                skippedEl.textContent       = data.skipped_count || 0;
                rowsText.textContent        = processed + ' / ' + (total || '—') + ' ' + _LRM.rows;

                if (data.status === 'in_progress' && total > 0) {
                    statusText.textContent = _LRM.processingRow + ' ' + processed + ' / ' + total + '…';
                }

                if (data.status === 'done') {
                    setDone();
                } else if (data.status === 'error') {
                    setError(data.error_message);
                }
            } catch (e) {
                console.error('Progress poll error:', e);
            }
        }

        // Poll immediately then every second
        poll();
        interval = setInterval(poll, 1000);
    })();
    </script>
    @endpush
</x-app-layout>
