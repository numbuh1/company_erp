<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Hồ sơ người dùng</h2>
            <div class="flex gap-2">
                @if(auth()->id() === $user->id || auth()->user()->canAny(['edit team user', 'edit all user']))
                    <a href="{{ route('users.edit', $user) }}">
                        <x-primary-button>Chỉnh sửa</x-primary-button>
                    </a>
                @endif
                <a href="{{ route('users.index') }}">
                    <x-secondary-button>Quay lại</x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    @push('styles')
    <style>[x-cloak] { display: none !important; }</style>
    @endpush

    @php
        $tabs = [['key' => 'general', 'label' => 'Thông tin chung']];
        if ($canViewPersonal) $tabs[] = ['key' => 'private', 'label' => 'Thông tin riêng tư'];
        if ($canViewPersonal) $tabs[] = ['key' => 'contact', 'label' => 'Thông tin liên hệ'];
        if ($canViewSalary)   $tabs[] = ['key' => 'hr',      'label' => 'HR Only'];
        $defaultTab = $tabs[0]['key'];
    @endphp

    <div class="py-12">
        <div class="max-w-4xl mx-auto space-y-6">

            {{-- Basic Info --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Thông tin cơ bản</h3>

                <div class="flex items-center gap-6 mb-6">
                    @if($user->profile_picture)
                        <img src="{{ asset('storage/profile_pictures/' . $user->profile_picture) }}"
                            class="w-20 h-20 rounded-full object-cover border-2 border-gray-300">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-400 text-sm">
                            No photo
                        </div>
                    @endif
                    <div>
                        <p class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $user->name }}
                            @if($user->full_name)
                             ({{ $user->full_name }})
                            @endif
                        </p>
                        @if($user->position)
                            <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ $user->position }}</p>
                        @endif
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label value="Chức vụ" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                            {{ $user->position ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <x-input-label value="Cấp bậc" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                            {{ $user->grade ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <x-input-label value="Vai trò" />
                        <div class="flex flex-wrap gap-1 mt-1">
                            @forelse($user->roles as $role)
                                <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs px-2 py-1 rounded">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-400">Không có vai trò</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════
                 TAB SYSTEM
            ═══════════════════════════════════════════════════ --}}
            <div x-data="{ activeTab: '{{ $defaultTab }}' }">

                {{-- Tab navigation --}}
                <div class="bg-white dark:bg-gray-800 rounded-t-lg shadow-sm border border-gray-200 dark:border-gray-700 border-b-0">
                    <nav class="flex overflow-x-auto">
                        @foreach($tabs as $tab)
                        <button type="button"
                            @click="activeTab = '{{ $tab['key'] }}'"
                            :class="activeTab === '{{ $tab['key'] }}'
                                ? 'border-b-2 border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300'"
                            class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors shrink-0">
                            {{ $tab['label'] }}
                        </button>
                        @endforeach
                    </nav>
                </div>

                {{-- Tab panels --}}
                <div class="bg-white dark:bg-gray-800 rounded-b-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">

                    {{-- ── General Info ───────────────────── --}}
                    <div x-show="activeTab === 'general'" x-cloak>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                            <div>
                                <x-input-label value="Số giờ phép còn lại" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                                    {{ rtrim(rtrim(number_format($user->leave_balance ?? 0, 2), '0'), '.') }}h
                                    <a href="{{ route('users.leave-balance-history', $user) }}" class="text-xs text-blue-500 ml-1 hover:underline">lịch sử</a>
                                </p>
                            </div>
                            <div>
                                <x-input-label value="Số giờ phép đã sử dụng" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                                    {{ rtrim(rtrim(number_format($spentBalance ?? 0, 2), '0'), '.') }}h
                                </p>
                            </div>
                        </div>

                        {{-- Probation Time --}}
                        @if($user->probation_start_date || $user->probation_end_date)
                            <div class="mb-6">
                                <x-input-label value="Thời gian thử việc" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                                    {{ $user->probation_start_date ? $user->probation_start_date->format('d/m/Y') : '—' }}
                                    –
                                    {{ $user->probation_end_date ? $user->probation_end_date->format('d/m/Y') : '—' }}
                                </p>
                            </div>
                        @endif

                        {{-- Onboarded from recruitment applicant --}}
                        @if($canViewOriginalApplicant ?? false)
                            <div class="mb-6">
                                <x-input-label value="Ứng viên gốc" />
                                <p class="mt-1 text-sm">
                                    <a href="{{ route('recruitment.applicants.show', [$user->recruitmentApplicant->recruitment_position_id, $user->recruitmentApplicant->id]) }}"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $user->recruitmentApplicant->name }}
                                    </a>
                                </p>
                            </div>
                        @endif

                        {{-- Supervisors --}}
                        <div class="mb-6">
                            <x-input-label value="Người giám sát" />
                            @if($user->supervisors->isEmpty())
                                <p class="mt-1 text-sm text-gray-400">Chưa có người giám sát.</p>
                            @else
                                <div class="space-y-2 mt-1">
                                    @foreach($user->supervisors as $supervisor)
                                        <div class="flex items-center gap-3 border rounded px-4 py-3 dark:border-gray-600">
                                            @if($supervisor->profile_picture)
                                                <img src="{{ asset('storage/profile_pictures/' . $supervisor->profile_picture) }}"
                                                    class="w-8 h-8 rounded-full object-cover border border-gray-300">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-300 text-sm font-bold">
                                                    {{ strtoupper(substr($supervisor->name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <a href="{{ route('users.show', $supervisor) }}"
                                                    class="text-sm font-medium text-gray-800 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                    {{ $supervisor->name }}
                                                </a>
                                                @if($supervisor->position)
                                                    <p class="text-xs text-gray-400">{{ $supervisor->position }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Teams --}}
                        <div class="mb-6">
                            <x-input-label value="Nhóm" />
                            @if($user->teams->isEmpty())
                                <p class="mt-1 text-sm text-gray-400">Chưa là thành viên của nhóm nào.</p>
                            @else
                                <div class="space-y-2 mt-1">
                                    @foreach($user->teams as $team)
                                        <div class="flex items-center justify-between border rounded px-4 py-3 dark:border-gray-600">
                                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                                {{ $team->name }}
                                            </span>
                                            @if($team->pivot->is_leader)
                                                <span class="bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 text-xs px-2 py-0.5 rounded">
                                                    Trưởng nhóm
                                                </span>
                                            @else
                                                <span class="bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 text-xs px-2 py-0.5 rounded">
                                                    Thành viên
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ── Private Info ───────────────────── --}}
                    @if($canViewPersonal)
                    <div x-show="activeTab === 'private'" x-cloak>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Căn cước Công dân" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->citizen_id ?: '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="Sinh nhật" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->birthday ? $user->birthday->format('d/m/Y') : '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="Mã số Thuế" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->tax_code ?: '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="Mã BHXH" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->social_insurance_id ?: '—' }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label value="Hết hạn hợp đồng" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->contract_expiry ? $user->contract_expiry->format('d/m/Y') : '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- ── Contact Info ────────────────────── --}}
                    <div x-show="activeTab === 'contact'" x-cloak>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Email liên hệ" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->contact_email ?: '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="Số điện thoại" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->phone_number ?: '—' }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label value="Địa chỉ nhà" />
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->home_address ?: '—' }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ── HR Only / Salary ────────────────── --}}
                    @if($canViewSalary)
                    <div x-show="activeTab === 'hr'" x-cloak>
                        @if($user->salary)
                            @php
                                $typeLabel = ['monthly' => 'Tháng', 'weekly' => 'Tuần', 'daily' => 'Ngày', 'hourly' => 'Giờ'];
                                $fmtMoney  = fn(?float $n) => $n !== null ? number_format($n, 0, '.', ',') . ' ₫' : '—';
                            @endphp
                            <div class="flex items-baseline gap-2 mb-4">
                                <span class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                                    {{ number_format($user->salary, 0, '.', ',') }} ₫
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    / {{ $typeLabel[$user->salary_type] ?? $user->salary_type }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                @foreach([
                                    ['label' => 'Theo giờ',  'value' => $user->hourly_rate],
                                    ['label' => 'Theo ngày',  'value' => $user->daily_rate],
                                    ['label' => 'Theo tuần',  'value' => $user->weekly_rate],
                                    ['label' => 'Theo tháng', 'value' => $user->monthly_rate],
                                ] as $rate)
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-4 py-3 text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $rate['label'] }}</p>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $fmtMoney($rate['value']) }}</p>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400">Chưa có thông tin lương.</p>
                        @endif
                    </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
