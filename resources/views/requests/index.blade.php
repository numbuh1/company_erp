<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">Tất cả yêu cầu</h2>
            <div class="flex items-center gap-2">
                @php
                    $calDate   = $dateFrom ?: now()->format('Y-m-d');
                    $calUrl    = route('calendar.index') . '?' . http_build_query(['date' => $calDate]);
                    $exportUrl = route('requests.export') . '?' . http_build_query(array_filter([
                        'date_from' => $dateFrom,
                        'date_to'   => $dateTo,
                        'type'      => $type,
                        'status'    => $status !== 'all' ? $status : null,
                    ]));
                @endphp
                @can('module calendar')
                <a href="{{ $calUrl }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white dark:bg-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Xem lịch
                </a>
                @endcan
                <a href="{{ $exportUrl }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white dark:bg-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Xuất
                </a>
                @can('module leaves')
                <a href="{{ route('leave-requests.create') }}">
                    <x-primary-button>+ Leave</x-primary-button>
                </a>
                @endcan
                @can('module ot')
                <a href="{{ route('overtime-requests.create') }}">
                    <x-primary-button>+ OT</x-primary-button>
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="px-6 pt-4">
            <div class="p-3 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded text-sm">{{ session('success') }}</div>
        </div>
    @endif

    {{-- Tab bar --}}
    @php
        $tabs = [
            'all'   => 'Tất cả',
            'leave' => 'Nghỉ phép',
            'ot'    => 'Tăng ca',
        ];
    @endphp
    <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4">
        <div class="flex">
            @foreach($tabs as $tabKey => $tabLabel)
                @php
                    $tabUrl    = route('requests.index', array_merge(request()->except('page'), ['type' => $tabKey]));
                    $isActive  = $type === $tabKey;
                @endphp
                <a href="{{ $tabUrl }}"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                        {{ $isActive
                            ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }}">
                    {{ $tabLabel }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Filter bar --}}
    @include('partials.request_filter_bar', [
        'routeName'      => 'requests.index',
        'dateFrom'       => $dateFrom,
        'dateTo'         => $dateTo,
        'status'         => $status,
        'showTypeFilter' => false,
        'type'           => $type,
    ])

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    @if($type === 'all')
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người dùng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số giờ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phân loại</th>
                    @if($type !== 'leave')
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dự án</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Công việc</th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người duyệt</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($items as $row)
                    @php
                        $r       = $row['record'];
                        $isLeave = $row['_type'] === 'leave';
                        $showRoute  = $isLeave ? route('leave-requests.show', $r) : route('overtime-requests.show', $r);
                        $editRoute  = $isLeave ? route('leave-requests.edit', $r) : route('overtime-requests.edit', $r);
                        $approveRoute = $isLeave
                            ? route('leave-requests.approve', $r)
                            : route('overtime-requests.approve', $r);
                        $rejectRoute  = $isLeave
                            ? route('leave-requests.reject', $r->id)
                            : route('overtime-requests.reject', $r->id);

                        $canApprove = $isLeave
                            ? auth()->user()->canAny(['approve team leaves', 'approve all leaves'])
                            : auth()->user()->canAny(['approve team ot', 'approve all ot']);

                        $canEdit = $isLeave
                            ? auth()->user()->canAny(['edit team leaves', 'edit all leaves'])
                            : (auth()->user()->can('edit all ot')
                                || auth()->user()->can('edit team ot')
                                || (auth()->user()->can('edit own ot') && $r->user_id === auth()->id()));

                        $statusClass = match($r->status) {
                            'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                            default    => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        @if($type === 'all')
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs px-2 py-1 rounded font-medium
                                {{ $isLeave ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' }}">
                                {{ $isLeave ? 'Leave' : 'OT' }}
                            </span>
                        </td>
                        @endif
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            {{ $r->created_at->format('d/m/y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <x-user-status :user="$r->user" />
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            <div>{{ $r->start_at->translatedFormat('D, d/m/y H:i') }}</div>
                            <div class="text-xs text-gray-500">→ {{ $r->end_at->translatedFormat('D, d/m/y H:i') }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $r->hours }}h</td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs px-2 py-1 rounded
                                {{ $isLeave ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' }}">
                                {{ ['annual' => 'Nghỉ phép năm', 'sick' => 'Nghỉ ốm', 'unpaid' => 'Nghỉ không lương'][$r->type] ?? $r->type }}
                            </span>
                        </td>
                        @if($type !== 'leave')
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $isLeave ? '—' : ($r->project?->name ?? '—') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $isLeave ? '—' : ($r->task?->name ?? '—') }}
                        </td>
                        @endif
                        <td class="px-4 py-3">
                            <span class="inline-block text-xs px-2 py-1 rounded {{ $statusClass }}">
                                {{ ['pending' => 'Đang chờ', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$r->status] ?? ucfirst($r->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $r->approver?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                {{-- View --}}
                                <a href="{{ $showRoute }}" title="Xem"
                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-blue-600 hover:border-blue-400 bg-white dark:bg-gray-700 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xem</span>
                                </a>

                                {{-- Edit --}}
                                @if($canEdit && !in_array($r->status, ['approved', 'rejected']))
                                <a href="{{ $editRoute }}" title="Chỉnh sửa"
                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Chỉnh sửa</span>
                                </a>
                                @endif

                                {{-- Approve / Reject --}}
                                @if($canApprove && $r->status === 'pending')
                                <form method="POST" action="{{ $approveRoute }}" class="inline">
                                    @csrf
                                    <button type="submit" title="Phê duyệt"
                                        class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-green-600 hover:border-green-400 bg-white dark:bg-gray-700 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Phê duyệt</span>
                                    </button>
                                </form>
                                <button type="button" onclick="openRejectModal('{{ $rejectRoute }}')"
                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Từ chối</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $type === 'all' ? 11 : ($type === 'leave' ? 8 : 10) }}" class="px-6 py-10 text-center text-gray-400">
                            Không có yêu cầu nào trong khoảng thời gian được chọn.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $items->links() }}</div>
    </div>

    {{-- Generic reject modal --}}
    <div id="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Từ chối yêu cầu</h3>
            <form id="rejectForm" method="POST" action="">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Lý do <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reject_reason" rows="4" required
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="Nhập lý do từ chối…"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button type="button" onclick="closeRejectModal()">Hủy</x-secondary-button>
                    <x-danger-button type="submit">Xác nhận từ chối</x-danger-button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openRejectModal(url) {
            document.getElementById('rejectForm').action = url;
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
    </script>
</x-app-layout>
