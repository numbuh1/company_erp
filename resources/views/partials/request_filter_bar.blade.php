@php
$thisMonth = [
    'from' => now()->startOfMonth()->format('Y-m-d'),
    'to'   => now()->endOfMonth()->format('Y-m-d'),
];
$lastMonth = [
    'from' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
    'to'   => now()->subMonth()->endOfMonth()->format('Y-m-d'),
];
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm px-4 py-3 mb-4">
    <form id="filterForm" method="GET" action="{{ route($routeName) }}" class="flex flex-wrap items-end gap-3">

        {{-- Date range --}}
        <div class="flex items-center gap-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Từ</label>
                <input type="date" id="filter_date_from" name="date_from"
                    value="{{ $dateFrom }}"
                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm h-9 px-2">
            </div>
            <span class="text-gray-400 mt-4">→</span>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Đến</label>
                <input type="date" id="filter_date_to" name="date_to"
                    value="{{ $dateTo }}"
                    class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm h-9 px-2">
            </div>
        </div>

        {{-- Quick select --}}
        <div class="flex gap-1.5 mt-4">
            <button type="button"
                onclick="setDateRange('{{ $thisMonth['from'] }}', '{{ $thisMonth['to'] }}')"
                class="px-2.5 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition
                    {{ $dateFrom === $thisMonth['from'] && $dateTo === $thisMonth['to'] ? 'bg-pink-50 dark:bg-pink-900/20 border-pink-300 text-pink-700 dark:text-pink-300' : '' }}">
                Tháng này
            </button>
            <button type="button"
                onclick="setDateRange('{{ $lastMonth['from'] }}', '{{ $lastMonth['to'] }}')"
                class="px-2.5 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition
                    {{ $dateFrom === $lastMonth['from'] && $dateTo === $lastMonth['to'] ? 'bg-pink-50 dark:bg-pink-900/20 border-pink-300 text-pink-700 dark:text-pink-300' : '' }}">
                Tháng trước
            </button>
        </div>

        {{-- Status --}}
        <div>
            <label class="block text-xs text-gray-500 mb-1">Trạng thái</label>
            <select name="status" onchange="document.getElementById('filterForm').submit()"
                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm h-9 pr-8">
                <option value="all"      {{ $status === 'all'      ? 'selected' : '' }}>Tất cả trạng thái</option>
                <option value="pending"  {{ $status === 'pending'  ? 'selected' : '' }}>Đang chờ</option>
                <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Đã từ chối</option>
            </select>
        </div>

        {{-- Type filter (All Requests page only) --}}
        @if(!empty($showTypeFilter))
        <div>
            <label class="block text-xs text-gray-500 mb-1">Loại</label>
            <select name="type" onchange="document.getElementById('filterForm').submit()"
                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm h-9 pr-8">
                <option value="all"   {{ ($type ?? 'all') === 'all'   ? 'selected' : '' }}>Tất cả loại</option>
                <option value="leave" {{ ($type ?? 'all') === 'leave' ? 'selected' : '' }}>Chỉ nghỉ phép</option>
                <option value="ot"    {{ ($type ?? 'all') === 'ot'    ? 'selected' : '' }}>Chỉ tăng ca</option>
            </select>
        </div>
        @endif

        {{-- Apply button --}}
        <div class="mt-4">
            <button type="submit"
                class="px-3 py-1.5 text-sm rounded bg-pink-600 hover:bg-pink-700 text-white transition">
                Áp dụng
            </button>
            @if($dateFrom || $dateTo || ($status !== 'all'))
                <a href="{{ route($routeName) }}" class="ml-2 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    Clear
                </a>
            @endif
        </div>

    </form>
</div>

<script>
function setDateRange(from, to) {
    document.getElementById('filter_date_from').value = from;
    document.getElementById('filter_date_to').value   = to;
    document.getElementById('filterForm').submit();
}
</script>