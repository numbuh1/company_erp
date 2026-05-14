<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">
            {{ isset($user) ? 'Edit User' : 'Create User' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto">
            <form method="POST"
                action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}"
                enctype="multipart/form-data">

                @csrf
                @if(isset($user)) @method('PUT') @endif

                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-6">

                    {{-- ── Main Info ────────────────────────────────────────── --}}
                    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Thông tin cơ bản</p>

                        {{-- Profile Picture --}}
                        <div class="mb-6">
                            <x-input-label value="Ảnh đại diện" />
                            <div class="mb-3">
                                @if(isset($user) && $user->profile_picture)
                                    <img id="currentPicture"
                                        src="{{ asset('storage/profile_pictures/' . $user->profile_picture) }}"
                                        class="w-24 h-24 rounded-full object-cover border-2 border-gray-300">
                                @else
                                    <div id="currentPicture" class="w-24 h-24 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-400 text-sm">
                                        No photo
                                    </div>
                                @endif
                            </div>
                            <input type="file" id="profilePictureInput" accept="image/*"
                                class="text-sm text-gray-600 dark:text-gray-300">
                            <input type="hidden" name="profile_picture_cropped" id="profilePictureCropped">
                        </div>

                        {{-- Crop Modal --}}
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

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Tên" />
                                <x-text-input name="name" class="w-full mt-1"
                                    value="{{ old('name', $user->name ?? '') }}" />
                                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label for="full_name" value="Họ và tên" />
                                <x-text-input id="full_name" name="full_name" class="w-full mt-1"
                                    value="{{ old('full_name', $user->full_name ?? '') }}"
                                    placeholder="Tên đầy đủ (tuỳ chọn)" />
                                @error('full_name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label value="Chức vụ" />
                                <x-text-input name="position" class="w-full mt-1"
                                    value="{{ old('position', $user->position ?? '') }}" />
                            </div>
                            <div>
                                <x-input-label value="Email" />
                                <x-text-input name="email" type="email" class="w-full mt-1"
                                    value="{{ old('email', $user->email ?? '') }}" />
                                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- ── Private Info (HR-only) ───────────────────────────── --}}
                    @can('edit all user')
                    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow border border-amber-200 dark:border-amber-700">
                        <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wide mb-4">
                            Thông tin riêng tư
                        </p>
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
                    </div>
                    @endcan

                    {{-- ── Contact Info (HR-only) ───────────────────────────── --}}
                    @can('edit all user')
                    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow border border-sky-200 dark:border-sky-700">
                        <p class="text-xs font-semibold text-sky-700 dark:text-sky-400 uppercase tracking-wide mb-4">
                            Thông tin liên hệ
                        </p>
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
                    </div>
                    @endcan

                    {{-- ── Settings (password) ─────────────────────────────── --}}
                    @if(!isset($user) || auth()->id() === $user->id || auth()->user()->can('edit all user'))
                    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow border border-gray-200 dark:border-gray-600">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                            Cài đặt tài khoản
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Mật khẩu mới" />
                                <x-text-input type="password" name="password" class="w-full mt-1" autocomplete="new-password" />
                                @if(isset($user))
                                    <p class="text-xs text-gray-400 mt-1">Để trống để giữ mật khẩu hiện tại.</p>
                                @endif
                                @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label value="Xác nhận mật khẩu" />
                                <x-text-input type="password" name="password_confirmation" class="w-full mt-1" autocomplete="new-password" />
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ── HR Only ──────────────────────────────────────────── --}}
                    @can('edit all user')
                    <div class="bg-white dark:bg-gray-800 p-6 rounded shadow border border-yellow-300 dark:border-yellow-600">
                        <p class="text-xs font-semibold text-yellow-700 dark:text-yellow-400 uppercase tracking-wide mb-4">
                            HR Only
                        </p>

                        {{-- Active Status --}}
                        <div class="mb-4">
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
                        <div class="mb-4">
                            <x-input-label value="Vai trò" />
                            <select name="roles[]" id="roles-select" data-multi-select
                                    data-placeholder="Chọn vai trò…">
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}"
                                        {{ isset($user) && $user->hasRole($role->name) ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Supervisors --}}
                        <div class="mb-4">
                            <x-input-label value="Người giám sát" />
                            <p class="text-xs text-gray-400 mb-1">Người dùng giám sát người này</p>
                            @if(empty($supervisorOptions) || $supervisorOptions->isEmpty())
                                <p class="text-xs text-gray-400 px-1">Chưa có người dùng nào khác.</p>
                            @else
                                <select name="supervisors[]" id="supervisors-select" data-multi-select
                                        data-placeholder="Chọn người giám sát…" class="mt-1 block w-full" multiple>
                                    @foreach($supervisorOptions ?? [] as $opt)
                                        <option value="{{ $opt->id }}"
                                            {{ (isset($user) && $user->supervisors->contains($opt->id)) ? 'selected' : '' }}>
                                            {{ $opt->name }}{{ $opt->position ? ' · ' . $opt->position : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        {{-- WFH --}}
                        <div class="mb-4">
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
                        <div class="mb-4"
                            x-data="{
                                salary: '{{ old('salary', $user->salary ?? '') }}',
                                salaryType: '{{ old('salary_type', $user->salary_type ?? 'monthly') }}',
                                get hourlyRate() {
                                    const s = parseFloat(this.salary);
                                    if (!s || !this.salaryType) return null;
                                    const m = { monthly: s/176, weekly: s/40, daily: s/8, hourly: s };
                                    return m[this.salaryType] ?? null;
                                },
                                get monthlyRate() {
                                    const s = parseFloat(this.salary);
                                    if (!s || !this.salaryType) return null;
                                    const m = { monthly: s, weekly: s*52/12, daily: s*22, hourly: s*176 };
                                    return m[this.salaryType] ?? null;
                                },
                                fmt(n) {
                                    if (n === null) return '—';
                                    return new Intl.NumberFormat('vi-VN').format(Math.round(n));
                                }
                            }">
                            <x-input-label value="Lương" />
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
                            <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded px-2 py-1">
                                    <span class="font-medium">Giờ:</span> <span x-text="fmt(hourlyRate)"></span> ₫
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded px-2 py-1">
                                    <span class="font-medium">Tháng:</span> <span x-text="fmt(monthlyRate)"></span> ₫
                                </div>
                            </div>
                        </div>

                        {{-- Leave Balance --}}
                        <div class="mb-4">
                            <x-input-label value="Số giờ phép còn lại (giờ)" />
                            <x-text-input type="number" step="0.5" name="leave_balance" class="w-full mt-1"
                                value="{{ old('leave_balance', $user->leave_balance ?? 112) }}" />
                        </div>

                        {{-- Balance change reason --}}
                        <div>
                            <x-input-label value="Lý do thay đổi số giờ phép" />
                            <x-text-input name="balance_reason" class="w-full mt-1"
                                value="{{ old('balance_reason') }}" />
                        </div>
                    </div>
                    @endcan

                </div>

                <div class="flex justify-end mt-6 space-x-2">
                    <x-primary-button>{{ isset($user) ? 'Lưu' : 'Tạo' }}</x-primary-button>
                    <a href="{{ route('users.index') }}"><x-secondary-button>Hủy</x-secondary-button></a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/users/form.js')
    @endpush
</x-app-layout>
