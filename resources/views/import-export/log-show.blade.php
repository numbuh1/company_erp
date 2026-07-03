<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold">Chi tiết lần nhập</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $log->typeLabel() }} &mdash; {{ $log->created_at->format('d/m/Y H:i') }}
                    &mdash; bởi {{ $log->user?->name ?? '—' }}
                    @if($log->filename)
                        &mdash; <span class="font-mono text-xs">{{ $log->filename }}</span>
                    @endif
                </p>
            </div>
            <a href="{{ route('import-export.index') }}">
                <x-secondary-button>Quay lại</x-secondary-button>
            </a>
        </div>
    </x-slot>

    @push('styles')
    <style>[x-cloak] { display: none !important; }</style>
    @endpush

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="{ filter: 'all' }">

            {{-- ── Summary cards ─────────────────────────────── --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl ring-1 ring-green-200 dark:ring-green-800 p-4 text-center">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">+{{ $log->created_count }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Tạo mới</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl ring-1 ring-blue-200 dark:ring-blue-800 p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $log->updated_count }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Cập nhật</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl ring-1 {{ $log->skipped_count > 0 ? 'ring-amber-200 dark:ring-amber-800' : 'ring-gray-200 dark:ring-gray-700' }} p-4 text-center">
                    <p class="text-2xl font-bold {{ $log->skipped_count > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">{{ $log->skipped_count }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Bỏ qua</p>
                </div>
            </div>

            {{-- ── Filter bar ────────────────────────────────── --}}
            @php $rows = $log->rows ?? []; @endphp
            @if(count($rows) > 0)
            <div class="flex gap-2">
                @php
                    $countCreated = collect($rows)->where('action', 'created')->count();
                    $countUpdated = collect($rows)->where('action', 'updated')->count();
                    $countSkipped = collect($rows)->where('action', 'skipped')->count();
                    $filterBtn = fn(string $key, string $label, string $cls) =>
                        "type=\"button\" @click=\"filter = '{$key}'\" :class=\"filter === '{$key}' ? 'ring-2 ring-offset-1 {$cls}' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'\" class=\"px-3 py-1 text-xs font-medium rounded-full transition-all\"";
                @endphp
                <button {!! $filterBtn('all', 'Tất cả', 'ring-gray-400 bg-gray-200 dark:bg-gray-600 text-gray-700') !!}>
                    Tất cả ({{ count($rows) }})
                </button>
                @if($countCreated > 0)
                <button {!! $filterBtn('created', 'Tạo mới', 'ring-green-400 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300') !!}>
                    Tạo mới ({{ $countCreated }})
                </button>
                @endif
                @if($countUpdated > 0)
                <button {!! $filterBtn('updated', 'Cập nhật', 'ring-blue-400 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300') !!}>
                    Cập nhật ({{ $countUpdated }})
                </button>
                @endif
                @if($countSkipped > 0)
                <button {!! $filterBtn('skipped', 'Bỏ qua', 'ring-amber-400 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300') !!}>
                    Bỏ qua ({{ $countSkipped }})
                </button>
                @endif
            </div>
            @endif

            {{-- ── Row detail table ──────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">

                @if(empty($rows))
                    <div class="p-8 text-center text-gray-400 text-sm">Không có dữ liệu chi tiết.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">Dòng</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Hành động</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bản ghi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chi tiết thay đổi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($rows as $entry)
                            @php
                                $action = $entry['action'] ?? 'skipped';
                                $showRow = match($action) {
                                    'created' => "filter === 'all' || filter === 'created'",
                                    'updated' => "filter === 'all' || filter === 'updated'",
                                    default   => "filter === 'all' || filter === 'skipped'",
                                };
                                $actionBadge = match($action) {
                                    'created' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                    'updated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                    default   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                };
                                $actionLabel = match($action) {
                                    'created' => 'Tạo mới',
                                    'updated' => 'Cập nhật',
                                    default   => 'Bỏ qua',
                                };
                            @endphp
                            <tr x-show="{{ $showRow }}" x-cloak
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors align-top">
                                <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ $entry['row'] ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded {{ $actionBadge }}">
                                        {{ $actionLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium">
                                    {{ $entry['identifier'] ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($action === 'skipped')
                                        <span class="text-xs text-amber-600 dark:text-amber-400">{{ $entry['error'] ?? '—' }}</span>
                                    @elseif($action === 'created')
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($entry['changes'] ?? [] as $field => $value)
                                                @if($value !== null && $value !== '')
                                                <span class="inline-flex items-center gap-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">
                                                    <span class="font-medium text-gray-500 dark:text-gray-400">{{ $field }}:</span>
                                                    {{ is_array($value) ? json_encode($value) : $value }}
                                                </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @elseif($action === 'updated')
                                        @if(empty($entry['changes']))
                                            <span class="text-xs text-gray-400">Không có thay đổi</span>
                                        @else
                                        <div class="space-y-1">
                                            @foreach($entry['changes'] as $field => $change)
                                            <div class="flex items-start gap-1.5 text-xs">
                                                <span class="font-mono font-medium text-gray-500 dark:text-gray-400 shrink-0">{{ $field }}:</span>
                                                @if(is_array($change) && isset($change['from']))
                                                    <span class="text-red-500 dark:text-red-400 line-through">
                                                        {{ $change['from'] !== null ? $change['from'] : 'trống' }}
                                                    </span>
                                                    <svg class="w-3 h-3 text-gray-400 shrink-0 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                    <span class="text-green-600 dark:text-green-400">
                                                        {{ $change['to'] !== null ? $change['to'] : 'trống' }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-600 dark:text-gray-300">{{ is_array($change) ? json_encode($change) : $change }}</span>
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
