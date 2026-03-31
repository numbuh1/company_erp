<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">OT Requests</h2>
            <a href="{{ route('overtime-requests.create') }}">
                <x-primary-button>Create OT Request</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hours</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reject Reason</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($otRequests as $ot)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $ot->created_at->format('d/m/y H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    <x-user-status :user="$ot->user" />
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    <div>{{ $ot->start_at->format('d/m/y H:i') }}</div>
                                    <div class="text-xs text-gray-500">→ {{ $ot->end_at->format('d/m/y H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    {{ $ot->hours }}h
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">{{ $ot->type }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    {{ $ot->description }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block text-xs px-2 py-1 rounded
                                        @if($ot->status === 'approved') bg-green-100 text-green-800
                                        @elseif($ot->status === 'rejected') bg-red-100 text-red-800
                                        @else bg-yellow-100 text-yellow-800
                                        @endif">
                                        {{ ucfirst($ot->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    {{ $ot->approver?->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                    @if($ot->status === 'rejected')
                                        <span class="text-red-500 text-sm">{{ $ot->reject_reason ?? '-' }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">

                                        {{-- View --}}
                                        <a href="{{ route('overtime-requests.show', $ot) }}" title="View"
                                            class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-blue-600 hover:border-blue-400 bg-white dark:bg-gray-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">View</span>
                                        </a>

                                        {{-- Edit --}}
                                        @if(!in_array($ot->status, ['approved', 'rejected']))
                                            @php
                                                $canEdit = auth()->user()->can('edit all ot')
                                                    || auth()->user()->can('edit team ot')
                                                    || (auth()->user()->can('edit own ot') && $ot->user_id === auth()->id());
                                            @endphp
                                            @if($canEdit)
                                                <a href="{{ route('overtime-requests.edit', $ot) }}" title="Edit"
                                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                    </svg>
                                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Edit</span>
                                                </a>
                                            @endif
                                        @endif

                                        {{-- Approve / Reject --}}
                                        @canany(['approve team ot', 'approve all ot'])
                                            @if($ot->status === 'pending')
                                                <form method="POST" action="{{ route('overtime-requests.approve', $ot) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" title="Approve"
                                                        class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-green-600 hover:border-green-400 bg-white dark:bg-gray-700 transition">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Approve</span>
                                                    </button>
                                                </form>
                                                <button type="button" onclick="openRejectModal('{{ route('overtime-requests.reject', $ot->id) }}')"
                                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Reject</span>
                                                </button>
                                            @endif
                                        @endcanany

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-6 text-center text-gray-500">No OT requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $otRequests->links() }}</div>
            </div>
        </div>
    </div>

    @include('overtime_requests._partials.reject_modal')
</x-app-layout>
