<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __('Import / Export') }}</h2>
    </x-slot>

    @push('styles')
    <style>[x-cloak] { display: none !important; }</style>
    @endpush

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="importPageData('{{ session('import_just_done') ? 'history' : 'export' }}')">

            {{-- Tab bar --}}
            <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700">
                @php
                    $tabOn  = 'border-b-2 border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400';
                    $tabOff = 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200';
                @endphp
                <button type="button" @click="tab = 'export'"
                    :class="tab === 'export' ? '{{ $tabOn }}' : '{{ $tabOff }}'"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    {{ __('Export Data') }}
                </button>
                <button type="button" @click="tab = 'import'; cancelPreview()"
                    :class="tab === 'import' ? '{{ $tabOn }}' : '{{ $tabOff }}'"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    {{ __('Import Data') }}
                </button>
                <button type="button" @click="tab = 'history'"
                    :class="tab === 'history' ? '{{ $tabOn }}' : '{{ $tabOff }}'"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors">
                    {{ __('Import History') }}
                    @if($logs->total() > 0)
                        <span class="ml-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded-full">
                            {{ $logs->total() }}
                        </span>
                    @endif
                </button>
            </div>

            {{-- Flash: import error --}}
            @if(session('import_error'))
                <div class="rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 p-4">
                    <p class="text-sm text-red-700 dark:text-red-400">{{ session('import_error') }}</p>
                </div>
            @endif

            {{-- ═══════════ EXPORT TAB ═══════════ --}}
            <div x-show="tab === 'export'" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    @php
                        $exports = [
                            ['type' => 'users',          'label' => __('Users'),          'desc' => __('Name, email, personal info, salary, roles, teams.'),   'color' => 'indigo'],
                            ['type' => 'teams',          'label' => __('Teams'),          'desc' => __('Team name, team leader, member list.'),                'color' => 'purple'],
                            ['type' => 'leave-requests', 'label' => __('Leave Requests'), 'desc' => __('All leave requests with status and approver.'),        'color' => 'green'],
                            ['type' => 'ot-requests',    'label' => __('OT Requests'),    'desc' => __('All OT requests with project, task, and approver.'),  'color' => 'orange'],
                        ];
                    @endphp

                    @foreach($exports as $ex)
                    @php
                        $ring   = match($ex['color']) { 'purple' => 'ring-purple-200 dark:ring-purple-800', 'green' => 'ring-green-200 dark:ring-green-800', 'orange' => 'ring-orange-200 dark:ring-orange-800', default => 'ring-indigo-200 dark:ring-indigo-800' };
                        $iconBg = match($ex['color']) { 'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400', 'green' => 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400', 'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400', default => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' };
                        $btnCls = match($ex['color']) { 'purple' => 'bg-purple-600 hover:bg-purple-700 focus:ring-purple-500', 'green' => 'bg-green-600 hover:bg-green-700 focus:ring-green-500', 'orange' => 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-500', default => 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500' };
                    @endphp
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 {{ $ring }} p-5 flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg {{ $iconBg }} flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $ex['label'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $ex['desc'] }}</p>
                            </div>
                        </div>
                        <a href="{{ route('import-export.export', $ex['type']) }}"
                           class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white {{ $btnCls }} focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            {{ __('Download Excel') }}
                        </a>
                    </div>
                    @endforeach

                </div>
                <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                    {{ __('Export files are in') }} <strong>.xlsx</strong> {{ __('format. Data reflects all records in the database at time of export.') }}
                </p>
            </div>

            {{-- ═══════════ IMPORT TAB ═══════════ --}}
            <div x-show="tab === 'import'" x-cloak>

                {{-- Error banner --}}
                <div x-show="previewError" x-cloak class="mb-4 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 p-4">
                    <p class="text-sm text-red-700 dark:text-red-400" x-text="previewError"></p>
                </div>

                {{-- ── STEP: File selection (idle + loading) ── --}}
                <div x-show="importStep !== 'preview'">

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6 space-y-5">

                        {{-- Type selector --}}
                        <div>
                            <x-input-label :value="__('Data Type')" />
                            <select x-model="importType" @change="cancelPreview()"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                <option value="users">{{ __('Users') }}</option>
                                <option value="teams">{{ __('Teams') }}</option>
                                <option value="leave-requests">{{ __('Leave Requests') }}</option>
                                <option value="ot-requests">{{ __('OT Requests') }}</option>
                            </select>
                        </div>

                        {{-- Template download --}}
                        <div class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ __('Download template before importing:') }}&nbsp;</span>
                            <a :href="templateUrl()" class="font-medium underline hover:text-blue-900 dark:hover:text-blue-100">
                                {{ __('Download template (.xlsx)') }}
                            </a>
                        </div>

                        {{-- Column notes per type --}}
                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <template x-if="importType === 'users'">
                                <div class="space-y-1">
                                    <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('Required columns:') }} <code>name</code>, <code>email</code></p>
                                    <p>{{ __('Optional columns:') }} <code>password</code>, <code>full_name</code>, <code>contact_email</code>, <code>position</code>, <code>grade</code>, <code>phone_number</code>, <code>citizen_id</code>, <code>tax_code</code>, <code>social_insurance_id</code>, <code>home_address</code></p>
                                    <p><code>birthday</code>, <code>contract_expiry</code>, <code>probation_start_date</code>, <code>probation_end_date</code> — {{ __('format') }} <code>dd/mm/yyyy</code></p>
                                    <p><code>employment_status</code>: <code>active</code> / <code>on_probation</code> / <code>inactive</code></p>
                                    <p><code>is_active</code>, <code>wfh_without_approval</code>: <code>1</code> / <code>0</code></p>
                                    <p><code>leave_balance</code>: {{ __('hours') }} &nbsp;·&nbsp; <code>salary</code>: {{ __('amount') }} &nbsp;·&nbsp; <code>salary_type</code>: <code>monthly</code> / <code>weekly</code> / <code>daily</code> / <code>hourly</code></p>
                                    <p><code>roles</code>: {{ __('role name, separated by') }} <code>|</code></p>
                                    <p class="text-indigo-600 dark:text-indigo-400">{{ __('Existing email → update. New email → create.') }}</p>
                                </div>
                            </template>
                            <template x-if="importType === 'teams'">
                                <div class="space-y-1">
                                    <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('Required columns:') }} <code>name</code></p>
                                    <p>{{ __('Optional columns:') }} <code>leaders</code>, <code>members</code></p>
                                    <p><code>leaders</code>/<code>members</code>: {{ __('user name or') }} <code>ID:Name</code>, {{ __('separated by') }} <code>|</code></p>
                                    <p class="text-indigo-600 dark:text-indigo-400">{{ __('Existing team name → update members. New name → create.') }}</p>
                                </div>
                            </template>
                            <template x-if="importType === 'leave-requests'">
                                <div class="space-y-1">
                                    <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('Required columns:') }} <code>user</code>, <code>type</code>, <code>start_at</code>, <code>end_at</code>, <code>hours</code></p>
                                    <p>{{ __('Optional columns:') }} <code>description</code>, <code>status</code>, <code>approved_by</code>, <code>reject_reason</code></p>
                                    <p><code>user</code> / <code>approved_by</code>: {{ __('name or ID') }}</p>
                                    <p><code>type</code>: <code>annual</code> / <code>sick</code> / <code>unpaid</code></p>
                                    <p><code>start_at</code> / <code>end_at</code>: <code>dd/mm/yyyy HH:mm</code></p>
                                    <p><code>status</code>: <code>pending</code> ({{ __('default') }}) / <code>approved</code> / <code>rejected</code></p>
                                </div>
                            </template>
                            <template x-if="importType === 'ot-requests'">
                                <div class="space-y-1">
                                    <p class="font-medium text-gray-700 dark:text-gray-300">{{ __('Required columns:') }} <code>user</code>, <code>start_at</code>, <code>end_at</code>, <code>hours</code></p>
                                    <p>{{ __('Optional columns:') }} <code>type</code>, <code>project</code>, <code>task</code>, <code>description</code>, <code>status</code>, <code>approved_by</code>, <code>reject_reason</code></p>
                                    <p><code>type</code>: <code>OT x1.5</code> / <code>OT x2</code> / <code>OT x3</code></p>
                                    <p><code>project</code> / <code>task</code>: {{ __('name or ID') }}</p>
                                    <p><code>status</code>: <code>pending</code> ({{ __('default') }}) / <code>approved</code> / <code>rejected</code></p>
                                </div>
                            </template>
                        </div>

                        {{-- File input --}}
                        <div>
                            <x-input-label value="File (.xlsx, .xls, .csv)" />
                            <input type="file" id="importFile" accept=".xlsx,.xls,.csv"
                                   class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300
                                          file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0
                                          file:text-sm file:font-medium
                                          file:bg-indigo-50 file:text-indigo-700
                                          dark:file:bg-indigo-900/30 dark:file:text-indigo-300
                                          hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 cursor-pointer">
                        </div>

                        {{-- Preview button --}}
                        <div class="flex items-center gap-3 pt-1">
                            <button type="button" @click="doPreview()" :disabled="importStep === 'loading'"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <template x-if="importStep === 'loading'">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </template>
                                <template x-if="importStep !== 'loading'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </template>
                                <span x-text="importStep === 'loading' ? '{{ __('Analyzing...') }}' : '{{ __('Preview changes') }}'"></span>
                            </button>
                            <p class="text-xs text-gray-400">{{ __('Max 10 MB. File will be analyzed before actual import.') }}</p>
                        </div>
                    </div>

                    <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-700 dark:text-amber-300 space-y-1">
                        <p class="font-semibold">{{ __('Notes when importing data:') }}</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>{{ __('The first row must be column headers. The italic yellow rows in the template are examples — delete them before importing.') }}</li>
                            <li>{{ __('Users: matched by email → update if exists, create if not.') }}</li>
                            <li>{{ __('Teams: matched by name → update members if exists, create if not.') }}</li>
                            <li>{{ __('Leave / OT requests: always created, no duplicate check.') }}</li>
                            <li>{{ __('Click "Preview changes" to review data before confirming.') }}</li>
                        </ul>
                    </div>
                </div>

                {{-- ── STEP: Preview ── --}}
                <div x-show="importStep === 'preview'" x-cloak class="space-y-4">

                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ __('Preview import results') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                File: <span class="font-mono" x-text="previewData?.filename ?? ''"></span>
                            </p>
                        </div>
                    </div>

                    {{-- Summary cards --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-white dark:bg-gray-800 rounded-xl ring-1 ring-green-200 dark:ring-green-800 p-4 text-center">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400"
                               x-text="'+' + (previewData?.created_count ?? 0)"></p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ __('Created') }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl ring-1 ring-blue-200 dark:ring-blue-800 p-4 text-center">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                               x-text="previewData?.updated_count ?? 0"></p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ __('Updated') }}</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl ring-1 p-4 text-center"
                             :class="(previewData?.skipped_count ?? 0) > 0 ? 'ring-amber-200 dark:ring-amber-800' : 'ring-gray-200 dark:ring-gray-700'">
                            <p class="text-2xl font-bold" x-text="previewData?.skipped_count ?? 0"
                               :class="(previewData?.skipped_count ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400'"></p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ __('Skipped') }}</p>
                        </div>
                    </div>

                    {{-- Filter buttons --}}
                    <div class="flex flex-wrap gap-2" x-show="(previewData?.rows?.length ?? 0) > 0">
                        <button type="button" @click="previewFilter = 'all'"
                                :class="previewFilter === 'all'
                                    ? 'ring-2 ring-offset-1 ring-gray-400 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                                class="px-3 py-1 text-xs font-medium rounded-full transition-all">
                            {{ __('All') }} (<span x-text="previewData?.rows?.length ?? 0"></span>)
                        </button>
                        <button type="button" x-show="(previewData?.created_count ?? 0) > 0"
                                @click="previewFilter = 'created'"
                                :class="previewFilter === 'created'
                                    ? 'ring-2 ring-offset-1 ring-green-400 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                                class="px-3 py-1 text-xs font-medium rounded-full transition-all">
                            {{ __('Created') }} (<span x-text="previewData?.created_count ?? 0"></span>)
                        </button>
                        <button type="button" x-show="(previewData?.updated_count ?? 0) > 0"
                                @click="previewFilter = 'updated'"
                                :class="previewFilter === 'updated'
                                    ? 'ring-2 ring-offset-1 ring-blue-400 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                                class="px-3 py-1 text-xs font-medium rounded-full transition-all">
                            {{ __('Updated') }} (<span x-text="previewData?.updated_count ?? 0"></span>)
                        </button>
                        <button type="button" x-show="(previewData?.skipped_count ?? 0) > 0"
                                @click="previewFilter = 'skipped'"
                                :class="previewFilter === 'skipped'
                                    ? 'ring-2 ring-offset-1 ring-amber-400 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                                class="px-3 py-1 text-xs font-medium rounded-full transition-all">
                            {{ __('Skipped') }} (<span x-text="previewData?.skipped_count ?? 0"></span>)
                        </button>
                    </div>

                    {{-- Rows table --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
                        <div x-show="(previewData?.rows?.length ?? 0) === 0"
                             class="p-8 text-center text-gray-400 dark:text-gray-500 text-sm">
                            {{ __('No valid data in file.') }}
                        </div>
                        <template x-if="(previewData?.rows?.length ?? 0) > 0">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">{{ __('Row') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">{{ __('Action') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Data') }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Change details') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <template x-for="row in filteredRows" :key="row.row">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors align-top">
                                            <td class="px-4 py-3 text-gray-400 font-mono text-xs" x-text="row.row"></td>
                                            <td class="px-4 py-3">
                                                <span class="text-xs font-medium px-2 py-0.5 rounded"
                                                      :class="actionBadge(row.action)"
                                                      x-text="actionLabel(row.action)"></span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium" x-text="row.identifier"></td>
                                            <td class="px-4 py-3" x-html="renderChanges(row)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </template>
                    </div>

                    {{-- Action bar --}}
                    <div class="flex items-center gap-3 flex-wrap">
                        <form :action="confirmUrl()" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="temp_path" :value="previewData?.temp_path">
                            <input type="hidden" name="filename" :value="previewData?.filename">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ __('Confirm import') }}
                            </button>
                        </form>
                        <button type="button" @click="cancelPreview()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            {{ __('Choose another file') }}
                        </button>
                    </div>

                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-xs text-blue-700 dark:text-blue-300">
                        {{ __('This is a preview — data has') }} <strong>{{ __('not been changed') }}</strong>. {{ __('Click "Confirm import" to apply the changes above.') }}
                    </div>

                </div>
            </div>

            {{-- ═══════════ HISTORY TAB ═══════════ --}}
            <div x-show="tab === 'history'" x-cloak>
                @if($logs->isEmpty())
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-10 text-center text-gray-400 dark:text-gray-500 text-sm">
                        {{ __('No import history yet.') }}
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Time') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Type') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('File') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Importer') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Created') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Updated') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Skipped') }}</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($logs as $log)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                        {{ $log->created_at->format('d/m/y H:i') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $badge = match($log->type) {
                                                'users'          => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                                'teams'          => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                                'leave-requests' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                                'ot-requests'    => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                                default          => 'bg-gray-100 text-gray-600',
                                            };
                                        @endphp
                                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ $badge }}">
                                            {{ $log->typeLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ $log->statusBadge() }}">
                                            {{ $log->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-[160px] truncate" title="{{ $log->filename }}">
                                        {{ $log->filename ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $log->user?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($log->created_count > 0)
                                            <span class="font-semibold text-green-600 dark:text-green-400">+{{ $log->created_count }}</span>
                                        @else
                                            <span class="text-gray-400">0</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($log->updated_count > 0)
                                            <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $log->updated_count }}</span>
                                        @else
                                            <span class="text-gray-400">0</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($log->skipped_count > 0)
                                            <span class="font-semibold text-amber-600 dark:text-amber-400">{{ $log->skipped_count }}</span>
                                        @else
                                            <span class="text-gray-400">0</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('import-export.log.show', $log) }}"
                                           class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                            {{ __('Details') }}
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($logs->hasPages())
                            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                                {{ $logs->links() }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
    var _LRM = {
        pleaseSelectFile: '{{ __("Please select a file first.") }}',
        parseError:       '{{ __("An error occurred while analyzing the file.") }}',
        connectionError:  '{{ __("Could not connect to server. Please try again.") }}',
        created:          '{{ __("Created") }}',
        updated:          '{{ __("Updated") }}',
        skipped:          '{{ __("Skipped") }}',
        noChanges:        '{{ __("No changes") }}',
        empty:            '{{ __("(empty)") }}',
    };
    function importPageData(initialTab) {
        return {
            tab: initialTab,
            importType: 'users',
            importStep: 'idle',
            previewData: null,
            previewError: null,
            previewFilter: 'all',

            templateUrl() {
                return '{{ url('data-transfer/template') }}/' + this.importType;
            },

            previewUrl() {
                return '{{ url('data-transfer/preview') }}/' + this.importType;
            },

            confirmUrl() {
                return '{{ url('data-transfer/import') }}/' + this.importType;
            },

            cancelPreview() {
                this.importStep = 'idle';
                this.previewData = null;
                this.previewError = null;
                const fi = document.getElementById('importFile');
                if (fi) fi.value = '';
            },

            async doPreview() {
                const fileInput = document.getElementById('importFile');
                if (!fileInput || !fileInput.files.length) {
                    this.previewError = _LRM.pleaseSelectFile;
                    return;
                }
                this.importStep = 'loading';
                this.previewError = null;

                const fd = new FormData();
                fd.append('file', fileInput.files[0]);
                fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

                try {
                    const res = await fetch(this.previewUrl(), { method: 'POST', body: fd });
                    const json = await res.json();
                    if (!res.ok) {
                        if (json.errors) {
                            this.previewError = Object.values(json.errors).flat().join(' ');
                        } else {
                            this.previewError = json.error || json.message || _LRM.parseError;
                        }
                        this.importStep = 'idle';
                        return;
                    }
                    this.previewData = json;
                    this.previewFilter = 'all';
                    this.importStep = 'preview';
                } catch (e) {
                    this.previewError = _LRM.connectionError;
                    this.importStep = 'idle';
                }
            },

            get filteredRows() {
                if (!this.previewData) return [];
                if (this.previewFilter === 'all') return this.previewData.rows;
                return this.previewData.rows.filter(r => r.action === this.previewFilter);
            },

            actionLabel(action) {
                return ({ created: _LRM.created, updated: _LRM.updated, skipped: _LRM.skipped })[action] || action;
            },

            actionBadge(action) {
                return ({
                    created: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                    updated: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                    skipped: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                })[action] || 'bg-gray-100 text-gray-600';
            },

            renderChanges(row) {
                const esc = s => {
                    const d = document.createElement('div');
                    d.appendChild(document.createTextNode(s == null ? '' : String(s)));
                    return d.innerHTML;
                };

                if (row.action === 'skipped') {
                    return '<span class="text-xs text-amber-600 dark:text-amber-400">' + esc(row.error) + '</span>';
                }

                if (row.action === 'created') {
                    const chips = Object.entries(row.changes || {})
                        .filter(([, v]) => v !== null && v !== '')
                        .map(([k, v]) =>
                            '<span class="inline-flex items-center gap-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">' +
                            '<span class="font-medium text-gray-500 dark:text-gray-400">' + esc(k) + ':</span> ' + esc(v) + '</span>'
                        ).join('');
                    return '<div class="flex flex-wrap gap-1.5">' + chips + '</div>';
                }

                if (row.action === 'updated') {
                    const changes = row.changes || {};
                    if (!Object.keys(changes).length) {
                        return '<span class="text-xs text-gray-400">' + _LRM.noChanges + '</span>';
                    }
                    const items = Object.entries(changes).map(([k, v]) => {
                        if (v && typeof v === 'object' && 'from' in v) {
                            return '<div class="flex items-start gap-1.5 text-xs">' +
                                '<span class="font-mono font-medium text-gray-500 dark:text-gray-400 shrink-0">' + esc(k) + ':</span>' +
                                '<span class="text-red-500 dark:text-red-400 line-through">' + (v.from !== null ? esc(String(v.from)) : _LRM.empty) + '</span>' +
                                '<svg class="w-3 h-3 text-gray-400 shrink-0 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>' +
                                '</svg>' +
                                '<span class="text-green-600 dark:text-green-400">' + (v.to !== null ? esc(String(v.to)) : _LRM.empty) + '</span>' +
                                '</div>';
                        }
                        return '<div class="flex items-start gap-1.5 text-xs">' +
                            '<span class="font-mono font-medium text-gray-500 dark:text-gray-400 shrink-0">' + esc(k) + ':</span> ' +
                            '<span class="text-gray-600 dark:text-gray-300">' + esc(String(v)) + '</span>' +
                            '</div>';
                    }).join('');
                    return '<div class="space-y-1">' + items + '</div>';
                }

                return '';
            },
        };
    }
    </script>
    @endpush
</x-app-layout>
