<x-app-layout>
    @php
        $posStatusColor = fn($s) => match($s) {
            'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
            'done'        => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
            default       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        };
    @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Tuyển dụng</h2>
            @can('edit recruitment')
                <a href="{{ route('recruitment.create') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                    + New Position
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded text-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Chức vụ</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Search Period</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ứng viên</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phân công cho</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($positions as $position)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-4">
                                    <a href="{{ route('recruitment.show', $position) }}"
                                        class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $position->name }}
                                    </a>
                                    <span class="ml-1 text-xs font-medium px-1.5 py-0.5 rounded {{ $posStatusColor($position->status) }}">
                                        {{ ['upcoming' => 'Sắp tới', 'in_progress' => 'Đang tuyển', 'done' => 'Đã đóng'][$position->status] ?? ucfirst(str_replace('_', ' ', $position->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    @if($position->search_start_date || $position->search_end_date)
                                        {{ $position->search_start_date?->format('d/m/Y') ?? '—' }}
                                        →
                                        {{ $position->search_end_date?->format('d/m/Y') ?? '—' }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    <div class="flex flex-wrap gap-1">
                                        @if($position->applicants_count === 0)
                                            <span class="text-gray-400">—</span>
                                        @else
                                            <p class="text-xs font-medium px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400" title="Total">
                                                Tổng: {{ $position->applicants_count }}
                                            </p>
                                            @if($position->cv_screening_count)
                                                @php $s = 'CV Screening'; @endphp
                                                <br><p class="text-xs font-medium px-1.5 py-0.5 rounded {{ \App\Models\RecruitmentApplicant::statusColor($s) }}" title="{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}">{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}: {{ $position->cv_screening_count }}</p>
                                            @endif
                                            @if($position->interview_count)
                                                @php $s = 'Approved for Interview'; @endphp
                                                <br><p class="text-xs font-medium px-1.5 py-0.5 rounded {{ \App\Models\RecruitmentApplicant::statusColor($s) }}" title="{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}">{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}: {{ $position->interview_count }}</p>
                                            @endif
                                            @if($position->approved_count)
                                                @php $s = 'Approved'; @endphp
                                                <br><p class="text-xs font-medium px-1.5 py-0.5 rounded {{ \App\Models\RecruitmentApplicant::statusColor($s) }}" title="{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}">{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}: {{ $position->approved_count }}</p>
                                            @endif
                                            @if($position->rejected_count)
                                                @php $s = 'Rejected'; @endphp
                                                <br><p class="text-xs font-medium px-1.5 py-0.5 rounded {{ \App\Models\RecruitmentApplicant::statusColor($s) }}" title="{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}">{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}: {{ $position->rejected_count }}</p>
                                            @endif
                                            @if($position->offered_count)
                                                @php $s = 'Offered'; @endphp
                                                <br><p class="text-xs font-medium px-1.5 py-0.5 rounded {{ \App\Models\RecruitmentApplicant::statusColor($s) }}" title="{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}">{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}: {{ $position->offered_count }}</p>
                                            @endif
                                            @if($position->hired_count)
                                                @php $s = 'Hired'; @endphp
                                                <br><p class="text-xs font-medium px-1.5 py-0.5 rounded {{ \App\Models\RecruitmentApplicant::statusColor($s) }}" title="{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}">{{ \App\Models\RecruitmentApplicant::statusLabel($s) }}: {{ $position->hired_count }}</p>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($position->assignedUsers->take(3) as $u)
                                            <x-user-status :user="$u" :show-name="false" />
                                        @empty
                                            <span class="text-xs text-gray-400">—</span>
                                        @endforelse
                                        @if($position->assignedUsers->count() > 3)
                                            <span class="text-xs text-gray-500">+{{ $position->assignedUsers->count() - 3 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('recruitment.show', $position) }}" title="Xem"
                                            class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xem</span>
                                        </a>
                                        @can('edit recruitment')
                                            <a href="{{ route('recruitment.edit', $position) }}" title="Chỉnh sửa"
                                                class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Chỉnh sửa</span>
                                            </a>
                                            <form method="POST" action="{{ route('recruitment.destroy', $position) }}" onsubmit="return confirm('Delete this position?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Xóa"
                                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xóa</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">Không tìm thấy vị trí.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $positions->links() }}
        </div>
    </div>
</x-app-layout>
