<x-app-layout>
    @php $readonly = $readonly ?? false; @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $readonly ? __('Leave Request Details') : (isset($leave) ? __('Edit Leave Request') : __('Create Leave Request')) }}
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
                            <x-input-label :value="__('User')" />
                            <input type="text" value="{{ $leave->user->name }}" class="w-full border rounded p-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300" disabled>
                            <input type="hidden" name="user_id" value="{{ $leave->user_id }}">
                        </div>
                    @else
                        @can('edit team leaves')
                            <div class="mb-4">
                                <x-input-label :value="__('User')" />
                                <select id="leave-user-select" name="user_id">
                                    <option value="">{{ __('— Select user —') }}</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}"
                                            @selected(old('user_id', auth()->id()) == $user->id)>
                                            {{ $user->name }}{{ $user->position ? ' · ' . $user->position : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endcan
                    @endif

                    <!-- Type -->
                    <div class="mb-4">
                        <x-input-label :value="__('Type')" />
                        <select name="type" class="w-full border rounded p-2" @disabled($readonly)>
                            @foreach(['annual', 'sick', 'unpaid'] as $type)
                                <option value="{{ $type }}"
                                    @selected(old('type', $leave->type ?? '') == $type)>
                                    {{ ['annual' => __('Annual leave'), 'sick' => __('Sick leave'), 'unpaid' => __('Unpaid leave')][$type] ?? ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Start datetime -->
                    <div class="mb-4">
                        <x-input-label :value="__('Start Time')" />
                        <input type="datetime-local" name="start_at" id="start_at" lang="en-GB" data-default-hour="8"
                            value="{{ old('start_at', isset($leave) ? $leave->start_at->format('Y-m-d\TH:i') : '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    <!-- End datetime -->
                    <div class="mb-4">
                        <x-input-label :value="__('End Time')" />
                        <input type="datetime-local" name="end_at" id="end_at" lang="en-GB" data-default-hour="17"
                            value="{{ old('end_at', isset($leave) ? $leave->end_at->format('Y-m-d\TH:i') : '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    <!-- Breakdown (appears above total hours input; header + bullet lines written by JS) -->
                    <div id="leave-hours-breakdown"
                         class="hidden mb-3 px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg text-xs text-gray-700 dark:text-gray-300">
                    </div>

                    <!-- Total hours (calculated; editable for a single day) -->
                    <div class="mb-4">
                        <x-input-label :value="__('Total leave hours')" />
                        <input type="number" step="0.25" id="hours" name="hours"
                            value="{{ old('hours', isset($leave) ? $leave->hours : '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <x-input-label :value="__('Reason')" />
                        <textarea name="description" class="w-full border rounded p-2" @disabled($readonly)>{{ old('description', $leave->description ?? '') }}</textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end mt-6 space-x-2">
                        @if(!$readonly)
                            <x-primary-button>
                                {{ isset($leave) ? __('Save') : __('Create') }}
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

                        <a href="{{ route('requests.index', ['type' => 'leave']) }}">
                            <x-secondary-button>{{ $readonly ? __('Back') : __('Cancel') }}</x-secondary-button>
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
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('leave-user-select');
            if (el) new TomSelect(el, { allowEmptyOption: true, maxOptions: 300 });
        });
        </script>
    @endpush
</x-app-layout>