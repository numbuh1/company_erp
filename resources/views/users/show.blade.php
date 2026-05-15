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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label value="Chức vụ" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                            {{ $user->position ?? '—' }}
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

                    @if($canViewSalary && ($user->salary || $user->salary_type))
                    <div>
                        <x-input-label value="Lương" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                            @if($user->salary)
                                {{ number_format($user->salary, 0, '.', ',') }} ₫
                            @endif
                            @if($user->salary_type)
                                @php $typeLabel = ['monthly' => '/ Tháng', 'weekly' => '/ Tuần', 'daily' => '/ Ngày', 'hourly' => '/ Giờ'][$user->salary_type] ?? $user->salary_type; @endphp
                                <span class="text-gray-500 dark:text-gray-400">{{ $typeLabel }}</span>
                            @endif
                        </p>
                        @if($user->monthly_rate && $user->salary_type !== 'monthly')
                            <p class="text-xs text-gray-400 mt-0.5">≈ {{ number_format($user->monthly_rate, 0, '.', ',') }} ₫/tháng</p>
                        @endif
                    </div>
                    @endif

                    <div>
                        <x-input-label value="Số giờ phép còn lại" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                            {{ rtrim(rtrim(number_format($user->leave_balance ?? 0, 2), '0'), '.') }}h
                            <a href="{{ route('users.leave-balance-history', $user) }}"
                                class="text-xs text-blue-500 ml-1">lịch sử</a>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Leave Requests --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Yêu cầu nghỉ phép</h3>

                @if($leaveRequests->isEmpty())
                    <p class="text-sm text-gray-400">Không có yêu cầu nghỉ phép đang chờ hoặc đã duyệt.</p>
                @else
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left">Loại</th>
                                <th class="px-3 py-2 text-left">Bắt đầu</th>
                                <th class="px-3 py-2 text-left">Kết thúc</th>
                                <th class="px-3 py-2 text-left">Giờ</th>
                                <th class="px-3 py-2 text-left">Trạng thái</th>
                                <th class="px-3 py-2 text-left">Đã gửi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaveRequests as $lr)
                                <tr class="border-t">
                                    <td class="px-3 py-2 capitalize">{{ $lr->type }}</td>
                                    <td class="px-3 py-2">{{ $lr->start_at->format('d/m/y') }}</td>
                                    <td class="px-3 py-2">{{ $lr->end_at->format('d/m/y') }}</td>
                                    <td class="px-3 py-2">{{ rtrim(rtrim(number_format($lr->hours, 2), '0'), '.') }}h</td>
                                    <td class="px-3 py-2">
                                        @if($lr->status === 'pending')
                                            <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded">Đang chờ</span>
                                        @elseif($lr->status === 'approved')
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded">Đã duyệt</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-500">{{ $lr->created_at->format('d/m/y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Supervisors --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Người giám sát</h3>

                @if($user->supervisors->isEmpty())
                    <p class="text-sm text-gray-400">Chưa có người giám sát.</p>
                @else
                    <div class="space-y-2">
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
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Nhóm</h3>

                @if($user->teams->isEmpty())
                    <p class="text-sm text-gray-400">Chưa là thành viên của nhóm nào.</p>
                @else
                    <div class="space-y-2">
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

            {{-- Salary Details --}}
            @if($canViewSalary && $user->salary)
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Lương</h3>
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
            </div>
            @endif

            {{-- Personal Info --}}
            @if($canViewPersonal)
            <div class="bg-white dark:bg-gray-800 p-6 rounded shadow">
                <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">Thông tin cá nhân</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    @if($user->phone_number)
                    <div>
                        <x-input-label value="Số điện thoại" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->phone_number }}</p>
                    </div>
                    @endif

                    @if($user->birthday)
                    <div>
                        <x-input-label value="Sinh nhật" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->birthday->format('d/m/Y') }}</p>
                    </div>
                    @endif

                    @if($user->citizen_id)
                    <div>
                        <x-input-label value="Căn cước Công dân" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->citizen_id }}</p>
                    </div>
                    @endif

                    @if($user->tax_code)
                    <div>
                        <x-input-label value="Mã số Thuế" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->tax_code }}</p>
                    </div>
                    @endif

                    @if($user->social_insurance_id)
                    <div>
                        <x-input-label value="Mã BHXH" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->social_insurance_id }}</p>
                    </div>
                    @endif

                    @if($user->contract_expiry)
                    <div>
                        <x-input-label value="Hết hạn hợp đồng" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->contract_expiry->format('d/m/Y') }}</p>
                    </div>
                    @endif

                    @if($user->home_address)
                    <div class="sm:col-span-2">
                        <x-input-label value="Địa chỉ nhà" />
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $user->home_address }}</p>
                    </div>
                    @endif

                </div>
                @if(!$user->phone_number && !$user->birthday && !$user->citizen_id && !$user->tax_code && !$user->social_insurance_id && !$user->contract_expiry && !$user->home_address)
                    <p class="text-sm text-gray-400">Chưa có thông tin cá nhân.</p>
                @endif
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
