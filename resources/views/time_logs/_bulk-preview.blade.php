{{-- Expects Alpine scope with: bulkFrom, bulkTo, preview, previewLoading, previewError, previewSeq --}}
@once
<script>
window.tlBulkPreview = function (ctx, userId) {
    if (!ctx.bulkFrom || !ctx.bulkTo) return;
    const seq = ++ctx.previewSeq;
    const qs  = new URLSearchParams({ user_id: userId, from_date: ctx.bulkFrom, to_date: ctx.bulkTo });
    ctx.previewLoading = true;
    fetch(@js(route('time-logs.bulk-preview')) + '?' + qs, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            if (seq !== ctx.previewSeq) return;
            ctx.previewError = ok ? '' : (d.message || @js(__('Could not calculate preview.')));
            ctx.preview      = ok ? d : null;
        })
        .catch(() => {
            if (seq !== ctx.previewSeq) return;
            ctx.preview = null;
            ctx.previewError = @js(__('Could not calculate preview.'));
        })
        .finally(() => { if (seq === ctx.previewSeq) ctx.previewLoading = false; });
};
</script>
@endonce
<div class="rounded-lg border border-gray-200 dark:border-gray-700 text-sm">
    <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Preview') }}</span>
        <span x-show="previewLoading" x-cloak class="text-xs text-gray-400">{{ __('Calculating…') }}</span>
    </div>

    <div x-show="previewError" x-cloak class="px-3 py-2 text-xs text-red-600 dark:text-red-400" x-text="previewError"></div>

    <template x-if="preview && !previewError">
        <div>
            <div class="grid grid-cols-2 divide-x divide-gray-100 dark:divide-gray-700 border-b border-gray-100 dark:border-gray-700">
                <div class="px-3 py-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Total hours to log') }}</div>
                    <div class="text-lg font-bold text-pink-600 dark:text-pink-400" x-text="preview.total_hours + 'h'"></div>
                </div>
                <div class="px-3 py-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Full 8h days') }}</div>
                    <div class="text-lg font-bold text-gray-800 dark:text-gray-100" x-text="preview.full_days"></div>
                </div>
            </div>

            <div x-show="preview.partial.length === 0" class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Every business day in this range will be logged with 8h.') }}
            </div>

            <div x-show="preview.partial.length > 0">
                <div class="px-3 pt-2 pb-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                    {{ __('Days with less than 8h') }} (<span x-text="preview.partial.length"></span>)
                </div>
                <ul class="max-h-40 overflow-y-auto px-3 pb-2 space-y-1">
                    <template x-for="d in preview.partial" :key="d.date">
                        <li class="flex items-start gap-2 text-xs">
                            <span class="w-28 shrink-0 text-gray-600 dark:text-gray-300" x-text="d.label"></span>
                            <span class="flex-1 flex flex-wrap gap-1">
                                <template x-if="d.holiday">
                                    <span class="px-1.5 rounded bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-300" x-text="@js(__('Holiday') . ': ') + d.holiday"></span>
                                </template>
                                <template x-if="d.leave > 0">
                                    <span class="px-1.5 rounded bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300" x-text="@js(__('Leave') . ' ') + d.leave + 'h'"></span>
                                </template>
                                <template x-if="d.logged > 0">
                                    <span class="px-1.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300" x-text="@js(__('Already logged') . ' ') + d.logged + 'h'"></span>
                                </template>
                            </span>
                            <span class="shrink-0 font-semibold"
                                  :class="d.fill > 0 ? 'text-pink-600 dark:text-pink-400' : 'text-gray-400'"
                                  x-text="d.fill > 0 ? '+' + d.fill + 'h' : '—'"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </template>
</div>
