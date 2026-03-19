<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Leave Request Details</h2>
    </x-slot>

    <div class="py-12 max-w-3xl mx-auto">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded p-6">
            <p><strong>User:</strong> {{ $leaveRequest->user->name }}</p>
            <p><strong>Type:</strong> {{ ucfirst($leaveRequest->type) }}</p>
            <p><strong>Period:</strong> {{ $leaveRequest->start_at }} → {{ $leaveRequest->end_at }}</p>
            <p><strong>Hours:</strong> {{ $leaveRequest->hours }}</p>
            <p><strong>Description:</strong> {{ $leaveRequest->description }}</p>
            <p><strong>Status:</strong> {{ ucfirst($leaveRequest->status) }}</p>
            <p><strong>Approver:</strong> {{ $leaveRequest->approver?->name ?? '-' }}</p>
            @if($leaveRequest->status === 'rejected')
                <p><strong>Reject Reason:</strong> {{ $leaveRequest->reject_reason }}</p>
            @endif

            <div class="mt-4 flex space-x-2">
                @canany(['edit team leaves', 'edit all leaves'])
                    <a href="{{ route('leave-requests.edit', $leaveRequest) }}">
                        <x-secondary-button>Edit</x-secondary-button>
                    </a>
                @endcan

                @canany(['approve team leaves','approve all leaves'])
                    @if($leaveRequest->status === 'pending')
                        <form method="POST" action="{{ route('leave-requests.approve', $leaveRequest) }}">
                            @csrf
                            <x-primary-button>Approve</x-primary-button>
                        </form>

                        <!-- Reject modal -->
                        <x-danger-button onclick="openRejectModal('{{ route('leave-requests.reject', $leave->id) }}')">
                            Reject
                        </x-danger-button>
                    @endif
                @endcan
            </div>
        </div>
    </div>
    @include('leave_requests._reject_modal')
</x-app-layout>