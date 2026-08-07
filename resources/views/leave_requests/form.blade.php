<x-app-layout>
    @php $readonly = $readonly ?? false; @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $readonly ? 'Yêu cầu Nghỉ phép' : (isset($leave) ? 'Chỉnh sửa Yêu cầu Nghỉ phép' : 'Tạo Yêu cầu Nghỉ phép') }}
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
                            <x-input-label value="Người dùng" />
                            <input type="text" value="{{ $leave->user->name }}" class="w-full border rounded p-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300" disabled>
                            <input type="hidden" name="user_id" value="{{ $leave->user_id }}">
                        </div>
                    @else
                        @can('edit team leaves')
                            <div class="mb-4">
                                <x-input-label value="Người dùng" />
                                <select id="leave-user-select" name="user_id">
                                    <option value="">— Chọn người dùng —</option>
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
                        <x-input-label value="Loại" />
                        <select name="type" class="w-full border rounded p-2" @disabled($readonly)>
                            @foreach(['annual', 'sick', 'unpaid'] as $type)
                                <option value="{{ $type }}"
                                    @selected(old('type', $leave->type ?? '') == $type)>
                                    {{ ['annual' => 'Nghỉ phép năm', 'sick' => 'Nghỉ ốm', 'unpaid' => 'Nghỉ không lương'][$type] ?? ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Start datetime -->
                    <div class="mb-4">
                        <x-input-label value="Giờ bắt đầu" />
                        <input type="datetime-local" name="start_at" id="start_at" lang="en-GB"
                            value="{{ old('start_at', isset($leave) ? $leave->start_at->format('Y-m-d\TH:i') : '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    <!-- End datetime -->
                    <div class="mb-4">
                        <x-input-label value="Giờ kết thúc" />
                        <input type="datetime-local" name="end_at" id="end_at" lang="en-GB"
                            value="{{ old('end_at', isset($leave) ? $leave->end_at->format('Y-m-d\TH:i') : '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    <!-- Partial-day hours section (multi-day leaves only, shown/hidden by JS) -->
                    <div id="partial-day-section" class="hidden mb-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg space-y-3">
                        <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wide">⚡ Chỉnh giờ nghỉ từng ngày</p>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Start day -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Ngày đầu <span id="partial-start-label" class="font-normal text-gray-400 text-xs"></span>
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <input type="number" step="0.25" min="0" max="24" id="start_day_hours" name="start_day_hours"
                                        value="{{ old('start_day_hours', isset($leave) ? ($leave->start_day_hours ?? '') : '') }}"
                                        class="w-24 border rounded p-2 text-sm" @disabled($readonly)
                                        placeholder="0">
                                    <span class="text-xs text-gray-500">giờ</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Giờ nghỉ vào ngày bắt đầu</p>
                            </div>

                            <!-- End day -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Ngày cuối <span id="partial-end-label" class="font-normal text-gray-400 text-xs"></span>
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <input type="number" step="0.25" min="0" max="24" id="end_day_hours" name="end_day_hours"
                                        value="{{ old('end_day_hours', isset($leave) ? ($leave->end_day_hours ?? '') : '') }}"
                                        class="w-24 border rounded p-2 text-sm" @disabled($readonly)
                                        placeholder="0">
                                    <span class="text-xs text-gray-500">giờ</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Giờ nghỉ vào ngày trở lại</p>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown (appears above total hours input; header + bullet lines written by JS) -->
                    <div id="leave-hours-breakdown"
                         class="hidden mb-3 px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg text-xs text-gray-700 dark:text-gray-300">
                    </div>

                    <!-- Total hours (auto-calculated from partial-day inputs) -->
                    <div class="mb-4">
                        <x-input-label value="Tổng giờ nghỉ" />
                        <input type="number" step="0.25" id="hours" name="hours"
                            value="{{ old('hours', isset($leave) ? $leave->hours : '') }}"
                            class="w-full border rounded p-2" @disabled($readonly)>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <x-input-label value="Lý do" />
                        <textarea name="description" class="w-full border rounded p-2" @disabled($readonly)>{{ old('description', $leave->description ?? '') }}</textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end mt-6 space-x-2">
                        @if(!$readonly)
                            <x-primary-button>
                                {{ isset($leave) ? 'Lưu' : 'Tạo' }}
                            </x-primary-button>
                        @endif

                        @if($readonly)
                            @canany(['edit team leaves', 'edit all leaves'])
                                @if(!in_array($leave->status, ['approved', 'rejected']))
                                    <a href="{{ route('leave-requests.edit', $leave) }}">
                                        <x-secondary-button>Chỉnh sửa</x-secondary-button>
                                    </a>
                                @endif
                            @endcanany

                            @canany(['approve team leaves', 'approve all leaves'])
                                @if($leave->status === 'pending')
                                    <form method="POST" action="{{ route('leave-requests.approve', $leave) }}" class="inline">
                                        @csrf
                                        <x-primary-button>Phê duyệt</x-primary-button>
                                    </form>
                                    <x-danger-button onclick="openRejectModal('{{ route('leave-requests.reject', $leave->id) }}')">
                                        Từ chối
                                    </x-danger-button>
                                @endif
                            @endcanany
                        @endif

                        <a href="{{ route('requests.index', ['type' => 'leave']) }}">
                            <x-secondary-button>{{ $readonly ? 'Quay lại' : 'Bỏ' }}</x-secondary-button>
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
        @php
            $formHolidays  = \App\Models\PublicHoliday::getHolidayDates(
                \Carbon\Carbon::now()->subYear(),
                \Carbon\Carbon::now()->addYears(2)
            );
            $formLunchStart = \App\Models\AppSetting::get('lunch_break_start', '12:00');
            $formLunchEnd   = \App\Models\AppSetting::get('lunch_break_end',   '13:00');
        @endphp
        <script>
            window._leaveHolidays   = {!! json_encode($formHolidays, JSON_HEX_TAG) !!};
            window._leaveLunchStart = '{{ $formLunchStart }}';
            window._leaveLunchEnd   = '{{ $formLunchEnd }}';
        </script>
        @vite('resources/js/leave_requests/form.js')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('leave-user-select');
            if (el) new TomSelect(el, { allowEmptyOption: true, maxOptions: 300 });
        });
        </script>
    @endpush
</x-app-layout>