<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Người dùng</h2>
            @can('create all user')
                <a href="{{ route('users.create') }}"><x-primary-button>Tạo người dùng</x-primary-button></a>
            @endcan
        </div>
    </x-slot>

    <div x-data="{ tab: 'overall' }">

        @if(session('success'))
            <div class="mx-4 mt-3 p-3 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Tab bar --}}
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4">
            <nav class="-mb-px flex">
                @php
                    $tabs = [
                        ['key' => 'overall',  'label' => 'Overall',      'show' => true],
                        ['key' => 'team',     'label' => 'Team',         'show' => true],
                        ['key' => 'salary',   'label' => 'Salary',       'show' => $canViewSalary],
                        ['key' => 'leaves',   'label' => 'Leaves',       'show' => true],
                        ['key' => 'personal', 'label' => 'Personal Info','show' => $canViewPersonal],
                    ];
                @endphp
                @foreach($tabs as $t)
                    @if($t['show'])
                    <button type="button"
                        @click="tab = '{{ $t['key'] }}'"
                        :class="tab === '{{ $t['key'] }}'
                            ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap">
                        {{ $t['label'] }}
                    </button>
                    @endif
                @endforeach
            </nav>
        </div>

        {{-- Scrollable table --}}
        <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow-sm">
            <table class="min-w-full border-collapse text-sm">

                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700 border-b-2 border-gray-200 dark:border-gray-600">

                        {{-- Frozen: Name --}}
                        <th class="sticky left-0 z-20 bg-gray-50 dark:bg-gray-700
                                   px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider
                                   border-r border-gray-200 dark:border-gray-600 w-52 min-w-[13rem]">
                            Tên
                        </th>

                        {{-- Overall columns --}}
                        <th x-show="tab === 'overall'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[10rem]">
                            Họ và tên
                        </th>
                        <th x-show="tab === 'overall'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[14rem]">
                            Email
                        </th>
                        <th x-show="tab === 'overall'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[10rem]">
                            Chức vụ
                        </th>
                        <th x-show="tab === 'overall'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[12rem]">
                            Vai trò
                        </th>

                        {{-- Team columns --}}
                        <th x-show="tab === 'team'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[16rem]">
                            Nhóm
                        </th>

                        {{-- Salary columns --}}
                        @if($canViewSalary)
                        @php $thSal = "px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap"; @endphp
                        <th x-show="tab === 'salary'" class="{{ $thSal }} text-left min-w-[8rem]">Lương CB</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[5rem]">Chu kì</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">/Giờ</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">/Ngày</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">/Tuần</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[7rem]">/Tháng</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[7rem]">Điều chỉnh</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">Thưởng</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[7rem]">PC MT</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">Gửi xe</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">BH</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">T.TNCN</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[7rem]">KT khác</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[8rem]">Gross</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[8rem]">Net</th>
                        @endif

                        {{-- Leaves columns --}}
                        <th x-show="tab === 'leaves'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[8rem]">
                            Số giờ phép còn lại
                        </th>

                        {{-- Personal Info columns --}}
                        @if($canViewPersonal)
                        @php $thPer = "px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap"; @endphp
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[9rem]">Số điện thoại</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[14rem]">Địa chỉ</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[9rem]">CCCD</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[7rem]">Sinh nhật</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[7rem]">Mã số Thuế</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[7rem]">Mã BHXH</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[8rem]">Hết hạn HĐ</th>
                        @endif

                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($users as $user)
                        <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">

                            {{-- Frozen: Name cell --}}
                            <td class="sticky left-0 z-10
                                       bg-white dark:bg-gray-800
                                       group-hover:bg-gray-50 dark:group-hover:bg-gray-700/50
                                       transition-colors
                                       px-3 py-2 border-r border-gray-200 dark:border-gray-600
                                       w-52 min-w-[13rem]">
                                <div class="flex items-center gap-2 min-w-0">
                                    <x-user-status :user="$user" :show-name="false" />
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('users.show', $user) }}"
                                           class="block truncate font-medium text-gray-900 dark:text-gray-100
                                                  hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $user->name }}
                                        </a>
                                        @if(!$user->is_active)
                                            <span class="inline-block text-xs px-1.5 rounded
                                                         bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300
                                                         leading-5">
                                                Không hoạt động
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Overall: Full Name --}}
                            <td x-show="tab === 'overall'"
                                class="px-3 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $user->full_name ?? '—' }}
                            </td>

                            {{-- Overall: Email --}}
                            <td x-show="tab === 'overall'"
                                class="px-3 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $user->email }}
                            </td>

                            {{-- Overall: Position --}}
                            <td x-show="tab === 'overall'"
                                class="px-3 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $user->position ?? '—' }}
                            </td>

                            {{-- Overall: Roles --}}
                            <td x-show="tab === 'overall'" class="px-3 py-2">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                     text-xs px-1.5 rounded leading-5 whitespace-nowrap">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>

                            {{-- Team: Teams --}}
                            <td x-show="tab === 'team'" class="px-3 py-2">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->teams as $team)
                                        <span class="text-xs px-1.5 rounded leading-5 whitespace-nowrap
                                            {{ $team->pivot->is_leader
                                                ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $team->name }}{{ $team->pivot->is_leader ? ' ★' : '' }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>

                            {{-- Salary columns --}}
                            @if($canViewSalary)
                            @php
                                $typeLabel = ['monthly'=>'Tháng','weekly'=>'Tuần','daily'=>'Ngày','hourly'=>'Giờ'];
                                $fmtN  = fn(?float $n) => $n ? number_format((int)$n, 0, '.', ',') : '—';
                                $fmtSgn = fn(?int $n) => $n === null ? '—' : number_format($n, 0, '.', ',');
                                $sr    = $user->salaryRecord;
                            @endphp
                            <td x-show="tab === 'salary'" class="px-3 py-2 whitespace-nowrap text-right tabular-nums text-gray-800 dark:text-gray-200 font-medium">
                                @if($user->salary)
                                    {{ number_format($user->salary, 0, '.', ',') }} ₫
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td x-show="tab === 'salary'" class="px-3 py-2 whitespace-nowrap text-center">
                                @if($user->salary_type)
                                    <span class="text-xs font-medium px-1.5 py-0.5 rounded
                                        {{ $user->salary_type === 'monthly' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' :
                                           ($user->salary_type === 'weekly'  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' :
                                           ($user->salary_type === 'daily'   ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' :
                                                                               'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300')) }}">
                                        {{ $typeLabel[$user->salary_type] ?? $user->salary_type }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td x-show="tab === 'salary'" class="px-3 py-2 whitespace-nowrap text-right tabular-nums text-xs text-gray-600 dark:text-gray-400">
                                {{ $user->salary ? $fmtN($user->hourly_rate) . ' ₫' : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="px-3 py-2 whitespace-nowrap text-right tabular-nums text-xs text-gray-600 dark:text-gray-400">
                                {{ $user->salary ? $fmtN($user->daily_rate) . ' ₫' : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="px-3 py-2 whitespace-nowrap text-right tabular-nums text-xs text-gray-600 dark:text-gray-400">
                                {{ $user->salary ? $fmtN($user->weekly_rate) . ' ₫' : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="px-3 py-2 whitespace-nowrap text-right tabular-nums text-xs text-gray-600 dark:text-gray-400">
                                {{ $user->salary ? $fmtN($user->monthly_rate) . ' ₫' : '—' }}
                            </td>
                            {{-- New salary detail columns --}}
                            @php
                                $tdSal = "px-3 py-2 whitespace-nowrap text-right tabular-nums text-xs text-gray-600 dark:text-gray-400";
                            @endphp
                            <td x-show="tab === 'salary'" class="{{ $tdSal }} {{ ($sr?->allowance_adjustment < 0) ? 'text-red-500 dark:text-red-400' : '' }}">
                                {{ $sr ? $fmtSgn($sr->allowance_adjustment) . ($sr->allowance_adjustment !== null ? ' ₫' : '') : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="{{ $tdSal }}">
                                {{ $sr ? $fmtN($sr->allowance_bonus) . ($sr->allowance_bonus ? ' ₫' : '') : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="{{ $tdSal }}">
                                {{ $sr ? $fmtN($sr->allowance_excl_tax) . ($sr->allowance_excl_tax ? ' ₫' : '') : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="{{ $tdSal }}">
                                {{ $sr ? $fmtN($sr->parking_fee) . ($sr->parking_fee ? ' ₫' : '') : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="{{ $tdSal }}">
                                {{ $sr ? $fmtN($sr->insurance) . ($sr->insurance ? ' ₫' : '') : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="{{ $tdSal }}">
                                {{ $sr ? $fmtN($sr->personal_income_tax) . ($sr->personal_income_tax ? ' ₫' : '') : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="{{ $tdSal }}">
                                {{ $sr ? $fmtN($sr->other_deduction) . ($sr->other_deduction ? ' ₫' : '') : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="px-3 py-2 whitespace-nowrap text-right tabular-nums text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                {{ $sr?->gross_pay ? number_format($sr->gross_pay, 0, '.', ',') . ' ₫' : '—' }}
                            </td>
                            <td x-show="tab === 'salary'" class="px-3 py-2 whitespace-nowrap text-right tabular-nums text-xs font-medium text-green-600 dark:text-green-400">
                                {{ $sr?->net_pay ? number_format($sr->net_pay, 0, '.', ',') . ' ₫' : '—' }}
                            </td>
                            @endif

                            {{-- Leaves: Leave Balance --}}
                            <td x-show="tab === 'leaves'" class="px-3 py-2 whitespace-nowrap">
                                @if($user->leave_balance !== null)
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        {{ $user->leave_balance }}
                                    </span>
                                    <span class="text-xs text-gray-400 ml-0.5">giờ</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Personal Info columns --}}
                            @if($canViewPersonal)
                            @php $tdPer = "px-3 py-2 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap"; @endphp
                            <td x-show="tab === 'personal'" class="{{ $tdPer }}">{{ $user->phone_number ?? '—' }}</td>
                            <td x-show="tab === 'personal'" class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 max-w-[14rem] truncate" title="{{ $user->home_address }}">{{ $user->home_address ?? '—' }}</td>
                            <td x-show="tab === 'personal'" class="{{ $tdPer }}">{{ $user->citizen_id ?? '—' }}</td>
                            <td x-show="tab === 'personal'" class="{{ $tdPer }}">{{ $user->birthday ? $user->birthday->format('d/m/Y') : '—' }}</td>
                            <td x-show="tab === 'personal'" class="{{ $tdPer }}">{{ $user->tax_code ?? '—' }}</td>
                            <td x-show="tab === 'personal'" class="{{ $tdPer }}">{{ $user->social_insurance_id ?? '—' }}</td>
                            <td x-show="tab === 'personal'" class="{{ $tdPer }}">{{ $user->contract_expiry ? $user->contract_expiry->format('d/m/Y') : '—' }}</td>
                            @endif

                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="px-6 py-10 text-center text-gray-400 text-sm">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        {{-- Pagination --}}
        <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-4 py-3">
            {{ $users->links() }}
        </div>

    </div>
</x-app-layout>