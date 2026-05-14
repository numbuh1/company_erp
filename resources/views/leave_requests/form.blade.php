<x-app-layout>
    @php $readonly = $readonly ?? false; @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $readonly ? 'Leave Request Details' : (isset($leave) ? 'Edit Leave Request' : 'Create Leave Request') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <form method="POST"
                    action="{{ isset($leave) ? route('leave-requests.update', $leave) : route('leave-requests.store') }}">
                    
                    @csrf
                    @if(isset($leave)) @method('PUT') @endif

                    <!-- User -->
                    @if(isset($leave))
                        <div class="mb-4">
                            <x-input-label value="{{ __('User') }}" />
                            <input type="text" value="{{ $leave->user->name }}" class="w-full border rounded p-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300" disabled>
                            <input type="hidden" name="user_id" value="{{ $leave->user_id }}">
                        </div>
                    @else
                        @can('edit team leaves')
                            <div class="mb-4">
                                <x-input-label value="{{ __('User') }}" />
                                <select name="user_id" class="w-full border rounded p-2">
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}"
                                            @selected(old('user_id') == $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endcan
                    @endif

                    <!-- Type -->
                    <div class="mb-4">
                        <x-input-label value="{{ __('Type') }}" />
                        <select name="type" class="w-full border rounded p-2" @disabled($readonly)>
                            @foreach(['annual', 'sick', 'unpaid'] as $type)
                                <option value="{{ $type }}"
                                    @selected(old('type', $leave->type ?? '') == $type)>
                                    {{ __( ucfirst($type) ) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Start -->
                    <div class="mb-4">
                        <x-input-label value="{{ __('Start Time') }}" />
                        <input type="datetime-local" name="start_at" id="start_at"
                            value="{{ old('start_at', isset($leave) ? $leave->start_at->format('Y-m-d\TH:i') : '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    <!-- End -->
                    <div class="mb-4">
                        <x-input-label value="{{ __('End Time') }}" />
                        <input type="datetime-local" name="end_at" id="end_at"
                            value="{{ old('end_at', isset($leave) ? $leave->end_at->format('Y-m-d\TH:i') : '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    <!-- Hours (auto) -->
                    <div class="mb-4">
                        <x-input-label value="{{ __('Hours') }}" />
                        <input type="number" step="0.5" id="hours" name="hours"
                            value="{{ old('hours', $leave->hours ?? '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <x-input-label value="{{ __('Description') }}" />
                        <textarea name="description" class="w-full border rounded p-2" @disabled($readonly)>{{ old('description', $leave->description ?? '') }}</textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end mt-6 space-x-2">
                        @if(!$readonly)
                            <x-primary-button>
                                {{ isset($leave) ? 'Update' : 'Create' }}
                            </x-primary-button>
                        @endif

                        @if($readonly)
                            @canany(['edit team leaves', 'edit all leaves'])
                                @if(!in_array($leave->status, ['approved', 'rejected']))
                                    <a href="{{ route('leave-requests.edit', $leave) }}">
                                        <x-secondary-button>{{ __('Edit') }}</x-secondary-button>
                                    </a>
                                @endif
                            @endcanany

                            @canany(['approve team leaves', 'approve all leaves'])
                                @if($leave->status === 'pending')
                                    <form method="POST" action="{{ route('leave-requests.approve', $leave) }}" class="inline">
                                        @csrf
                                        <x-primary-button>{{ __('Approve') }}</x-primary-button>
                                    </form>
                                    <x-danger-button onclick="openRejectModal('{{ route('leave-requests.reject', $leave->id) }}')">
                                        {{ __('Reject') }}
                                    </x-danger-button>
                                @endif
                            @endcanany
                        @endif

                        <a href="{{ route('leave-requests.index') }}">
                            <x-secondary-button>{{ $readonly ? 'Back' : 'Cancel' }}</x-secondary-button>
                        </a>
                    </div>


                </form>

            </div>
        </div>
    </div>
    @if($readonly)
        @include('leave_requests._partials.reject_modal')
    @endif
    @push('scripts')
        @vite('resources/js/leave_requests/form.js')
    @endpush
</x-app-layout>