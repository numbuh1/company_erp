<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                {{ isset($user) ? 'Chỉnh sửa người dùng' : 'Tạo người dùng' }}
            </h2>
            <a href="javascript:history.back()"><x-secondary-button>Quay lại</x-secondary-button></a>
        </div>
    </x-slot>

    @push('styles')
    <style>[x-cloak] { display: none !important; }</style>
    @endpush

    @php
        $authUser        = auth()->user();
        $isOwnProfile    = isset($user) && $authUser->id === $user->id;
        $canEditBasic    = !isset($user)
                        || $authUser->can('edit all user')
                        || $authUser->can('edit team user');
        $canEditPersonal = $authUser->can('edit all user');
        $canSeePersonal  = $authUser->can('edit all user')
                        || $authUser->can('view all user personal info')
                        || $isOwnProfile;
        $canSeeHR        = $authUser->can('edit all user')
                        || $authUser->can('view all user personal info')
                        || $authUser->canAny(['edit team leaves balance', 'edit all leaves balance']);
        $canEditLeaveBalance = $authUser->canAny(['edit team leaves balance', 'edit all leaves balance']);

        // Build tab list
        $tabs = [];
        if ($canSeePersonal) $tabs[] = ['key' => 'private',  'label' => 'Thông tin riêng tư'];
        if ($canSeePersonal) $tabs[] = ['key' => 'contact',  'label' => 'Thông tin liên hệ'];
        if ($isOwnProfile)   $tabs[] = ['key' => 'settings', 'label' => 'Cài đặt'];
        if ($canSeeHR)       $tabs[] = ['key' => 'hr',       'label' => 'HR Only'];

        $defaultTab = $tabs[0]['key'] ?? null;

        // Email notification preferences for this user
        $emailPrefs = isset($user) ? ($user->preferences?->email_notifications ?? []) : [];

        $sr = isset($user) ? $user->salaryRecord : null;
    @endphp

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <form method="POST"
                action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}"
                enctype="multipart/form-data">
                @csrf
                @if(isset($user)) @method('PUT') @endif

                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                {{-- ═══════════════════════════════════════════════════
                     BASIC INFO — always visible, always at the top
                ═══════════════════════════════════════════════════ --}}
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        Thông tin cơ bản
                        @if(isset($user) && !$canEditBasic)
                            <span class="ml-2 normal-case font-normal text-gray-400">(Chỉ xem — trừ ảnh đại diện)</span>
                        @endif
                    </p>

                    {{-- Profile Picture --}}
                    <div class="mb-6">
                        <x-input-label value="Ảnh đại diện" />
                        <div class="mb-3 mt-1">
                            @if(isset($user) && $user->profile_picture)
                                <img id="currentPicture"
                                    src="{{ asset('storage/profile_pictures/' . $user->profile_picture) }}"
                                    class="w-24 h-24 rounded-full object-cover border-2 border-gray-300 dark:border-gray-600">
                            @else
                                <div id="currentPicture" class="w-24 h-24 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-400 text-sm">
                                    No photo
                                </div>
                            @endif
                        </div>
                        @if(isset($user))
                            {{-- Only editable when updating an existing user --}}
                            <input type="file" id="profilePictureInput" accept="image/*"
                                class="text-sm text-gray-600 dark:text-gray-300">
                            <input type="hidden" name="profile_picture_cropped" id="profilePictureCropped">
                        @endif
                    </div>

                    {{-- Crop Modal --}}
                    @if(isset($user))
                    <div id="cropModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-4">Cắt ảnh đại diện</h3>
                            <div id="cropElement"></div>
                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" id="cropCancelBtn"
                                    class="px-4 py-1.5 text-sm rounded border border-gray-300 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Hủy</button>
                                <button type="button" id="cropConfirmBtn"
                                    class="px-4 py-1.5 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Confirm</button>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Core fields --}}
                    @if($canEditBasic)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Tên *" />
                                <x-text-input name="name" class="w-full mt-1"
                                    value="{{ old('name', $user->name ?? '') }}" required />
                                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label for="full_name" value="Họ và tên" />
                                <x-text-input id="full_name" name="full_name" class="w-full mt-1"
                                    value="{{ old('full_name', $user->full_name ?? '') }}"
                                    placeholder="Tên đầy đủ (tuỳ chọn)" />
                            </div>
                            <div>
                                <x-input-label value="Chức vụ" />
                                <x-text-input name="position" class="w-full mt-1"
                                    value="{{ old('position', $user->position ?? '') }}" />
                            </div>
                            <div>
                                <x-input-label value="Cấp bậc" />
                                <x-text-input name="grade" class="w-full mt-1"
                                    value="{{ old('grade', $user->grade ?? '') }}" />
                            </div>
                            <div>
                                <x-input-label value="Email *" />
                                <x-text-input name="email" type="email" class="w-full mt-1"
                                    value="{{ old('email', $user->email ?? '') }}" required />
                                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>

                            {{-- Password — only shown on create form here; edit uses Settings tab --}}
                            @if(!isset($user))
                            <div>
                                <x-input-label value="Mật khẩu *" />
                                <x-text-input type="password" name="password" class="w-full mt-1" autocomplete="new-password" required />
                                @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label value="Xác nhận mật khẩu *" />
                                <x-text-input type="password" name="password_confirmation" class="w-full mt-1" autocomplete="new-password" required />
                            </div>
                            @endif
                        </div>
                    @else
                        {{-- Read-only display --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <x-input-label value="Tên" />
                                <p class="mt-1 text-gray-800 dark:text-gray-200 font-medium">{{ $user->name }}</p>
                            </div>
                            <div>
                                <x-input-label value="Họ và tên" />
                                <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $user->full_name ?: '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="Chức vụ" />
                                <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $user->position ?: '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="Cấp bậc" />
                                <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $user->grade ?: '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="Email" />
                                <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $user->email }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ═══════════════════════════════════════════════════
                     TAB SYSTEM
                ═══════════════════════════════════════════════════ --}}
                @if(!empty($tabs))
                <div x-data="{ activeTab: '{{ $defaultTab }}' }" class="mt-4">

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

                        {{-- ── Private Info ───────────────────── --}}
                        @if($canSeePersonal)
                        <div x-show="activeTab === 'private'" x-cloak>
                            @if($canEditPersonal)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label value="Căn cước Công dân" />
                                        <x-text-input name="citizen_id" class="w-full mt-1"
                                            value="{{ old('citizen_id', $user->citizen_id ?? '') }}" />
                                    </div>
                                    <div>
                                        <x-input-label value="Sinh nhật" />
                                        <x-text-input type="date" name="birthday" class="w-full mt-1"
                                            value="{{ old('birthday', isset($user) && $user->birthday ? $user->birthday->format('Y-m-d') : '') }}" />
                                        @error('birthday')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <x-input-label value="Mã số Thuế" />
                                        <x-text-input name="tax_code" class="w-full mt-1"
                                            value="{{ old('tax_code', $user->tax_code ?? '') }}" />
                                    </div>
                                    <div>
                                        <x-input-label value="Mã BHXH" />
                                        <x-text-input name="social_insurance_id" class="w-full mt-1"
                                            value="{{ old('social_insurance_id', $user->social_insurance_id ?? '') }}" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-input-label value="Hết hạn hợp đồng" />
                                        <x-text-input type="date" name="contract_expiry" class="w-full mt-1"
                                            value="{{ old('contract_expiry', isset($user) && $user->contract_expiry ? $user->contract_expiry->format('Y-m-d') : '') }}" />
                                        @error('contract_expiry')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div><x-input-label value="Căn cước Công dân" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->citizen_id ?: '—') : '—' }}</p></div>
                                    <div><x-input-label value="Sinh nhật" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) && $user->birthday ? $user->birthday->format('d/m/Y') : '—' }}</p></div>
                                    <div><x-input-label value="Mã số Thuế" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->tax_code ?: '—') : '—' }}</p></div>
                                    <div><x-input-label value="Mã BHXH" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->social_insurance_id ?: '—') : '—' }}</p></div>
                                    <div class="sm:col-span-2"><x-input-label value="Hết hạn hợp đồng" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) && $user->contract_expiry ? $user->contract_expiry->format('d/m/Y') : '—' }}</p></div>
                                </div>
                            @endif
                        </div>

                        {{-- ── Contact Info ────────────────────── --}}
                        <div x-show="activeTab === 'contact'" x-cloak>
                            @if($canEditPersonal)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label value="Số điện thoại" />
                                        <x-text-input name="phone_number" type="tel" class="w-full mt-1"
                                            value="{{ old('phone_number', $user->phone_number ?? '') }}" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-input-label value="Địa chỉ nhà" />
                                        <textarea name="home_address" rows="2"
                                            class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('home_address', $user->home_address ?? '') }}</textarea>
                                    </div>
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div><x-input-label value="Số điện thoại" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->phone_number ?: '—') : '—' }}</p></div>
                                    <div class="sm:col-span-2"><x-input-label value="Địa chỉ nhà" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->home_address ?: '—') : '—' }}</p></div>
                                </div>
                            @endif
                        </div>
                        @endif

                        {{-- ── Settings (own profile only) ─────── --}}
                        @if($isOwnProfile)
                        <div x-show="activeTab === 'settings'" x-cloak>

                            {{-- Sentinel: lets the controller know settings were submitted --}}
                            <input type="hidden" name="_email_prefs" value="1">

                            {{-- Password change --}}
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                                Đổi mật khẩu
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <x-input-label value="Mật khẩu mới" />
                                    <x-text-input type="password" name="password" class="w-full mt-1" autocomplete="new-password" />
                                    <p class="text-xs text-gray-400 mt-1">Để trống để giữ mật khẩu hiện tại.</p>
                                    @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-input-label value="Xác nhận mật khẩu" />
                                    <x-text-input type="password" name="password_confirmation" class="w-full mt-1" autocomplete="new-password" />
                                </div>
                            </div>

                            {{-- Email notification preferences --}}
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                Thông báo Email
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                                Tắt một loại để ngừng nhận email cho danh mục đó, kể cả khi bạn được chỉ định là người nhận hoặc CC.
                            </p>

                            <div class="space-y-0 divide-y divide-gray-100 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">

                                @php
                                    $notifSettings = [
                                        ['key' => 'leave',        'label' => 'Email yêu cầu Nghỉ phép',  'desc' => 'Nhận email khi có yêu cầu nghỉ phép cần duyệt và khi yêu cầu của bạn được cập nhật'],
                                        ['key' => 'ot',           'label' => 'Email yêu cầu Tăng ca',    'desc' => 'Nhận email khi có yêu cầu tăng ca cần duyệt và khi yêu cầu của bạn được cập nhật'],
                                        ['key' => 'project',      'label' => 'Email Dự án & Công việc',  'desc' => 'Nhận email cho các cập nhật về dự án và công việc được giao'],
                                        ['key' => 'announcement', 'label' => 'Email Thông báo',           'desc' => 'Nhận email khi có thông báo mới được đăng'],
                                    ];
                                @endphp

                                @foreach($notifSettings as $ns)
                                <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800">
                                    <div class="mr-4">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $ns['label'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $ns['desc'] }}</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                        <input type="checkbox" name="email_notify_{{ $ns['key'] }}" value="1"
                                            class="sr-only peer"
                                            {{ ($emailPrefs[$ns['key']] ?? true) ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer
                                            peer-checked:bg-indigo-600
                                            after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                            after:bg-white after:border-gray-300 after:border after:rounded-full
                                            after:h-5 after:w-5 after:transition-all
                                            peer-checked:after:translate-x-full peer-checked:after:border-white">
                                        </div>
                                    </label>
                                </div>
                                @endforeach

                            </div>
                        </div>
                        @endif

                        {{-- ── HR Only ────────────────────────── --}}
                        @if($canSeeHR)
                        <div x-show="activeTab === 'hr'" x-cloak>

                            @if($canEditPersonal)
                            {{-- Active Status --}}
                            <div class="mb-5">
                                <x-input-label value="Trạng thái tài khoản" />
                                <label class="inline-flex items-center gap-2 mt-2 cursor-pointer">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Đang hoạt động (có thể đăng nhập)</span>
                                </label>
                            </div>

                            {{-- Roles --}}
                            <div class="mb-5">
                                <x-input-label value="Vai trò" />
                                <select name="roles[]" id="roles-select" data-multi-select
                                        data-placeholder="Chọn vai trò…" class="mt-1">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}"
                                            {{ isset($user) && $user->hasRole($role->name) ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Supervisors --}}
                            <div class="mb-5">
                                <x-input-label value="Người giám sát" />
                                <p class="text-xs text-gray-400 mb-1">Người dùng giám sát người này</p>
                                @if(empty($supervisorOptions) || $supervisorOptions->isEmpty())
                                    <p class="text-xs text-gray-400 px-1">Chưa có người dùng nào khác.</p>
                                @else
                                    <select name="supervisors[]" id="supervisors-select" data-multi-select
                                            data-placeholder="Chọn người giám sát…" class="mt-1 block w-full" multiple>
                                        @foreach($supervisorOptions ?? [] as $opt)
                                            <option value="{{ $opt->id }}"
                                                {{ isset($user) && $user->supervisors->contains($opt->id) ? 'selected' : '' }}>
                                                {{ $opt->name }}{{ $opt->position ? ' · ' . $opt->position : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            {{-- WFH --}}
                            <div class="mb-5">
                                <x-input-label value="Chính sách làm tại nhà" />
                                <label class="inline-flex items-center gap-2 mt-2 cursor-pointer">
                                    <input type="hidden" name="wfh_without_approval" value="0">
                                    <input type="checkbox" name="wfh_without_approval" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        {{ old('wfh_without_approval', $user->wfh_without_approval ?? false) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Làm tại nhà không cần duyệt</span>
                                </label>
                            </div>

                            {{-- Salary --}}
                            <div class="mb-5 pt-4 border-t border-gray-200 dark:border-gray-700"
                                x-data="{
                                    salary:       '{{ old('salary', $user->salary ?? '') }}',
                                    salaryType:   '{{ old('salary_type', $user->salary_type ?? 'monthly') }}',
                                    allowAdj:     '{{ old('allowance_adjustment', $sr?->allowance_adjustment ?? '') }}',
                                    allowBonus:   '{{ old('allowance_bonus', $sr?->allowance_bonus ?? '') }}',
                                    allowExclTax: '{{ old('allowance_excl_tax', $sr?->allowance_excl_tax ?? '') }}',
                                    parking:      '{{ old('parking_fee', $sr?->parking_fee ?? '') }}',
                                    insurance:    '{{ old('insurance', $sr?->insurance ?? '') }}',
                                    pit:          '{{ old('personal_income_tax', $sr?->personal_income_tax ?? '') }}',
                                    otherDed:     '{{ old('other_deduction', $sr?->other_deduction ?? '') }}',
                                    get h() { const s = parseFloat(this.salary); if (!s) return null;
                                        return { monthly: s/160, weekly: s/40, daily: s/8, hourly: s }[this.salaryType] ?? null; },
                                    get d() { const s = parseFloat(this.salary); if (!s) return null;
                                        return { monthly: s/20, weekly: s/5, daily: s, hourly: s*8 }[this.salaryType] ?? null; },
                                    get w() { const s = parseFloat(this.salary); if (!s) return null;
                                        return { monthly: s/4, weekly: s, daily: s*5, hourly: s*40 }[this.salaryType] ?? null; },
                                    get m() { const s = parseFloat(this.salary); if (!s) return null;
                                        return { monthly: s, weekly: s*4, daily: s*20, hourly: s*160 }[this.salaryType] ?? null; },
                                    get totalAllowance() {
                                        return (parseFloat(this.allowAdj)||0) + (parseFloat(this.allowBonus)||0) + (parseFloat(this.allowExclTax)||0) + (parseFloat(this.parking)||0);
                                    },
                                    get totalDeduction() {
                                        return (parseFloat(this.insurance)||0) + (parseFloat(this.pit)||0) + (parseFloat(this.otherDed)||0);
                                    },
                                    get grossPay() { const s = parseFloat(this.salary); if (!s) return null; return s + this.totalAllowance; },
                                    get netPay()   { const g = this.grossPay; if (g === null) return null; return g - this.totalDeduction; },
                                    fmt(n) { if (n === null) return '—';
                                        return new Intl.NumberFormat('vi-VN').format(Math.round(n)); }
                                }">

                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Lương</p>

                                <x-input-label value="Lương cơ bản" />
                                <div class="flex gap-2 mt-1">
                                    <input type="number" name="salary" min="0" step="1" x-model="salary"
                                        placeholder="Nhập mức lương…"
                                        class="flex-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                    <select name="salary_type" x-model="salaryType"
                                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                        <option value="monthly">Tháng</option>
                                        <option value="weekly">Tuần</option>
                                        <option value="daily">Ngày</option>
                                        <option value="hourly">Giờ</option>
                                    </select>
                                </div>
                                <div class="mt-2 grid grid-cols-4 gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded px-2 py-1 text-center"><div class="font-medium mb-0.5">/ Giờ</div><div><span x-text="fmt(h)"></span> ₫</div></div>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded px-2 py-1 text-center"><div class="font-medium mb-0.5">/ Ngày</div><div><span x-text="fmt(d)"></span> ₫</div></div>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded px-2 py-1 text-center"><div class="font-medium mb-0.5">/ Tuần</div><div><span x-text="fmt(w)"></span> ₫</div></div>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded px-2 py-1 text-center"><div class="font-medium mb-0.5">/ Tháng</div><div><span x-text="fmt(m)"></span> ₫</div></div>
                                </div>

                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-5 mb-2">
                                    Phụ cấp
                                    <span class="ml-2 normal-case font-normal text-indigo-600 dark:text-indigo-400" x-show="totalAllowance !== 0">
                                        Tổng: <span x-text="fmt(totalAllowance)"></span> ₫
                                    </span>
                                </p>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div><x-input-label value="Điều chỉnh (±)" /><input type="number" name="allowance_adjustment" step="1" x-model="allowAdj" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                    <div><x-input-label value="Thưởng / Bonus" /><input type="number" name="allowance_bonus" min="0" step="1" x-model="allowBonus" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                    <div><x-input-label value="Phụ cấp miễn thuế" /><input type="number" name="allowance_excl_tax" min="0" step="1" x-model="allowExclTax" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                    <div><x-input-label value="Phí gửi xe" /><input type="number" name="parking_fee" min="0" step="1" x-model="parking" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                </div>

                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-5 mb-2">
                                    Khấu trừ
                                    <span class="ml-2 normal-case font-normal text-red-500 dark:text-red-400" x-show="totalDeduction > 0">
                                        Tổng: <span x-text="fmt(totalDeduction)"></span> ₫
                                    </span>
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div><x-input-label value="Bảo hiểm" /><input type="number" name="insurance" min="0" step="1" x-model="insurance" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                    <div><x-input-label value="Thuế TNCN" /><input type="number" name="personal_income_tax" min="0" step="1" x-model="pit" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                    <div><x-input-label value="Khấu trừ khác" /><input type="number" name="other_deduction" min="0" step="1" x-model="otherDed" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3" x-show="grossPay !== null">
                                    <div class="bg-indigo-50 dark:bg-indigo-900/30 rounded-lg px-4 py-3">
                                        <p class="text-xs text-indigo-500 dark:text-indigo-400 mb-1">Tổng thu nhập (Gross)</p>
                                        <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300"><span x-text="fmt(grossPay)"></span> ₫</p>
                                    </div>
                                    <div class="bg-green-50 dark:bg-green-900/30 rounded-lg px-4 py-3">
                                        <p class="text-xs text-green-500 dark:text-green-400 mb-1">Thực lĩnh (Net)</p>
                                        <p class="text-sm font-semibold text-green-700 dark:text-green-300"><span x-text="fmt(netPay)"></span> ₫</p>
                                    </div>
                                </div>
                            </div>
                            @endif {{-- canEditPersonal --}}

                            {{-- Leave Balance — visible to leave balance managers --}}
                            @if($canEditLeaveBalance)
                            <div class="pt-4 {{ $canEditPersonal ? 'border-t border-gray-200 dark:border-gray-700' : '' }}">
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Số giờ phép</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label value="Số giờ phép còn lại" />
                                        <x-text-input type="number" step="0.5" name="leave_balance" class="w-full mt-1"
                                            value="{{ old('leave_balance', $user->leave_balance ?? 112) }}" />
                                    </div>
                                    <div>
                                        <x-input-label value="Lý do thay đổi" />
                                        <x-text-input name="balance_reason" class="w-full mt-1"
                                            value="{{ old('balance_reason') }}" />
                                    </div>
                                </div>
                            </div>
                            @endif

                        </div>
                        @endif

                    </div>{{-- /tab panels --}}
                </div>{{-- /x-data tabs --}}
                @endif{{-- /!empty($tabs) --}}

                {{-- Save / Cancel --}}
                <div class="flex justify-end mt-6 gap-2">
                    <a href="javascript:history.back()"><x-secondary-button type="button">Hủy</x-secondary-button></a>
                    <x-primary-button>{{ isset($user) ? 'Lưu' : 'Tạo' }}</x-primary-button>
                </div>

            </form>
        </div>
    </div>

    @push('scripts')
        @if(isset($user))
            @vite('resources/js/users/form.js')
        @endif
    @endpush
</x-app-layout>
