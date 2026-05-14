<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Yêu cầu nghỉ phép</h2>
            <div class="flex items-center gap-2">
                @php
                    $calDate   = $dateFrom ?: now()->format('Y-m-d');
                    $calUrl    = route('calendar.index') . '?' . http_build_query(['date' => $calDate]);
                    $exportUrl = route('requests.export') . '?' . http_build_query(array_filter([
                        'date_from' => $dateFrom ?: null,
                        'date_to'   => $dateTo   ?: null,
                        'type'      => 'leave',
                        'status'    => ($status !== 'all') ? $status : null,
                    ]));
                @endphp
                @can('module calendar')
                <a href="{{ $calUrl }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white dark:bg-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Xem lịch
                </a>
                @endcan
                <a href="{{ $exportUrl }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 bg-white dark:bg-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Xuất
                </a>
                <a href="{{ route('leave-requests.create') }}">
                    <x-primary-button>Tạo nghỉ phép</x-primary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            @include('partials.request_filter_bar', [
                'routeName'      => 'leave-requests.index',
                'dateFrom'       => $dateFrom,
                'dateTo'         => $dateTo,
                'status'         => $status,
                'showTypeFilter' => false,
            ])

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người dùng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số giờ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lý do</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người duyệt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lý do từ chối</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($leaveRequests as $leave)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-sm">{{ $leave->created_at->format('d/m/y H:i') }}</td>
                                <td class="px-6 py-4"><x-user-status :user="$leave->user" /></td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    <div>{{ $leave->start_at->translatedFormat('D, d/m/y H:i') }}</div>
                                    <div class="text-xs text-gray-500">→ {{ $leave->end_at->translatedFormat('D, d/m/y H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $leave->hours }}h</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{{ ['annual' => 'Nghỉ phép năm', 'sick' => 'Nghỉ ốm', 'unpaid' => 'Nghỉ không lương'][$leave->type] ?? ucfirst($leave->type) }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $leave->description }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block text-xs px-2 py-1 rounded
                                        @if($leave->status === 'approved') bg-green-100 text-green-800
                                        @elseif($leave->status === 'rejected') bg-red-100 text-red-800
                                        @else bg-yellow-100 text-yellow-800 @endif">
                                        {{ ['pending' => 'Đang chờ', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối'][$leave->status] ?? ucfirst($leave->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $leave->approver?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    @if($leave->status === 'rejected')
                                        <span class="text-red-500 text-sm">{{ $leave->reject_reason ?? '-' }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('leave-requests.show', $leave) }}" title="Xem"
                                            class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-blue-600 hover:border-blue-400 bg-white dark:bg-gray-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xem</span>
                                        </a>
                                        @canany(['edit team leaves', 'edit all leaves'])
                                            @if(!in_array($leave->status, ['approved', 'rejected']))
                                                <a href="{{ route('leave-requests.edit', $leave) }}" title="Chỉnh sửa"
                                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Chỉnh sửa</span>
                                                </a>
                                            @endif
                                        @endcanany
                                        @canany(['approve team leaves', 'approve all leaves'])
                                            @if($leave->status === 'pending')
                                                <form method="POST" action="{{ route('leave-requests.approve', $leave) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" title="Phê duyệt"
                                                        class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-green-600 hover:border-green-400 bg-white dark:bg-gray-700 transition">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Phê duyệt</span>
                                                    </button>
                                                </form>
                                                <button type="button" onclick="openRejectModal('{{ route('leave-requests.reject', $leave->id) }}')"
                                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Từ chối</span>
                                                </button>
                                            @endif
                                        @endcanany
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-6 py-10 text-center text-gray-400">Không tìm thấy yêu cầu nghỉ phép.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $leaveRequests->links() }}</div>
            </div>
        </div>
    </div>
    @include('leave_requests._partials.reject_modal')
</x-app-layout>