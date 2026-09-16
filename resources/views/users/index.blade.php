<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __('Users') }}</h2>
            @can('create all user')
                <div class="flex gap-2">
                    <a href="{{ route('users.import.form') }}"><x-secondary-button>{{ __('Import CSV') }}</x-secondary-button></a>
                    <a href="{{ route('users.create') }}"><x-primary-button>{{ __('Create User') }}</x-primary-button></a>
                </div>
            @endcan
        </div>
    </x-slot>

    <div x-data="{ tab: 'overall' }">

        @if(session('success'))
            <div class="mx-4 mt-3 p-3 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mx-4 mt-3 p-3 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Search --}}
        <div class="px-4 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <form method="GET" action="{{ route('users.index') }}" class="flex items-center gap-2 max-w-md">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $search ?? '' }}"
                        placeholder="{{ __('Search by name, email, position…') }}"
                        class="w-full pl-9 pr-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <x-primary-button class="!py-2">{{ __('Search') }}</x-primary-button>
                @if(!empty($search))
                    <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('Clear') }}</a>
                @endif
            </form>
        </div>

        {{-- Tab bar --}}
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4">
            <nav class="-mb-px flex">
                @php
                    $canEditAll = auth()->user()->can('edit all user');
                    $tabs = [
                        ['key' => 'overall',  'label' => 'Employee',      'show' => true],
                        ['key' => 'team',     'label' => 'Team',          'show' => true],
                        ['key' => 'salary',   'label' => 'Salary',        'show' => $canViewSalary],
                        ['key' => 'leaves',   'label' => 'Leave Balance', 'show' => true],
                        ['key' => 'personal', 'label' => 'Personal Info', 'show' => $canViewPersonal],
                        ['key' => 'actions',  'label' => 'Actions',       'show' => $canEditAll],
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
                        {{ __($t['label']) }}
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
                            {{ __('Name') }}
                        </th>

                        {{-- Overall columns --}}
                        <th x-show="tab === 'overall'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[10rem]">
                            {{ __('Full Name') }}
                        </th>
                        <th x-show="tab === 'overall'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[14rem]">
                            {{ __('Email') }}
                        </th>
                        <th x-show="tab === 'overall'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[10rem]">
                            {{ __('Position') }}
                        </th>
                        <th x-show="tab === 'overall'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[7rem]">
                            {{ __('Grade') }}
                        </th>
                        <th x-show="tab === 'overall'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[12rem]">
                            {{ __('Role') }}
                        </th>

                        {{-- Team columns --}}
                        <th x-show="tab === 'team'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[16rem]">
                            {{ __('Team') }}
                        </th>

                        {{-- Salary columns --}}
                        @if($canViewSalary)
                        @php $thSal = "px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap"; @endphp
                        <th x-show="tab === 'salary'" class="{{ $thSal }} text-left min-w-[8rem]">{{ __('Basic Salary') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[5rem]">{{ __('Period') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">{{ __('/Hour') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">{{ __('/Day') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">{{ __('/Week') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[7rem]">{{ __('/Month') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[7rem]">{{ __('Adjustment') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">{{ __('Bonus') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[7rem]">{{ __('Allow. Excl. Tax') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">{{ __('Parking') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">{{ __('Insurance') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[6rem]">{{ __('Income Tax') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[7rem]">{{ __('Other Ded.') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[8rem]">{{ __('Gross') }}</th>
                        <th x-show="tab === 'salary'" class="{{ $thSal }} min-w-[8rem]">{{ __('Net') }}</th>
                        @endif

                        {{-- Leaves columns --}}
                        <th x-show="tab === 'leaves'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[8rem]">
                            {{ __('Remaining Leave Hours') }}
                        </th>

                        {{-- Personal Info columns --}}
                        @if($canViewPersonal)
                        @php $thPer = "px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap"; @endphp
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[9rem]">{{ __('Phone') }}</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[14rem]">{{ __('Address') }}</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[9rem]">{{ __('Citizen ID') }}</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[7rem]">{{ __('Birthday') }}</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[7rem]">{{ __('Tax Code') }}</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[7rem]">{{ __('Social Insurance ID') }}</th>
                        <th x-show="tab === 'personal'" class="{{ $thPer }} min-w-[8rem]">{{ __('Contract Expiry') }}</th>
                        @endif

                        {{-- Actions columns --}}
                        @if($canEditAll)
                        <th x-show="tab === 'actions'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[7rem]">
                            {{ __('Status') }}
                        </th>
                        <th x-show="tab === 'actions'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[12rem]">
                            {{ __('Actions') }}
                        </th>
                        @endif

                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($users as $user)
                        <tr class="group/row group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">

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
                                                {{ __('Inactive') }}
                                            </span>
                                        @endif
                                    </div>
                                    @can('edit all user')
                                    <a href="{{ route('users.edit', $user) }}" title="{{ __('Edit') }}"
                                       class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded border border-gray-300 dark:border-gray-600 text-gray-400 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition opacity-0 group-hover/row:opacity-100">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </a>
                                    @endcan
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

                            {{-- Overall: Grade --}}
                            <td x-show="tab === 'overall'"
                                class="px-3 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $user->grade ?? '—' }}
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
                                $typeLabel = ['monthly' => __('Monthly'), 'weekly' => __('Weekly'), 'daily' => __('Daily'), 'hourly' => __('Hourly')];
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
                                    <span class="text-xs text-gray-400 ml-0.5">{{ __('h') }}</span>
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

                            {{-- Actions columns --}}
                            @if($canEditAll)
                            <td x-show="tab === 'actions'" class="px-3 py-2 whitespace-nowrap">
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline"
                                          onsubmit="return confirm('{{ $user->is_active ? __('Deactivate this account?') : __('Activate this account?') }}')">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium px-2 py-1 rounded transition
                                            {{ $user->is_active
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-800'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-800' }}">
                                            {{ $user->is_active ? __('Active') : __('Inactive') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td x-show="tab === 'actions'" class="px-3 py-2 whitespace-nowrap">
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.generate-password', $user) }}" class="inline"
                                          onsubmit="return confirm('{{ __('Generate a new password and send it via email?') }}')">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded
                                                   bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300
                                                   hover:bg-indigo-200 dark:hover:bg-indigo-800 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                            {{ __('Send Password') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            @endif

                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="px-6 py-10 text-center text-gray-400 text-sm">
                                {{ __('No users found.') }}
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