<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __('Users') }}</h2>
            @can('create all user')
                <a href="{{ route('users.create') }}"><x-primary-button>{{ __('Create User') }}</x-primary-button></a>
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
                @foreach([
                    ['key' => 'overall',  'label' => 'Overall'],
                    ['key' => 'team',     'label' => 'Team'],
                    ['key' => 'salary',   'label' => 'Salary'],
                    ['key' => 'leaves',   'label' => 'Leaves'],
                    ['key' => 'personal', 'label' => 'Personal Info'],
                ] as $t)
                    <button type="button"
                        @click="tab = '{{ $t['key'] }}'"
                        :class="tab === '{{ $t['key'] }}'
                            ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap">
                        {{ $t['label'] }}
                    </button>
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
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[12rem]">
                            {{ __('Roles') }}
                        </th>

                        {{-- Team columns --}}
                        <th x-show="tab === 'team'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[16rem]">
                            {{ __('Teams') }}
                        </th>

                        {{-- Salary columns (TBA) --}}
                        <th x-show="tab === 'salary'"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider whitespace-nowrap min-w-[10rem] italic">
                            Coming Soon
                        </th>

                        {{-- Leaves columns --}}
                        <th x-show="tab === 'leaves'"
                            class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[8rem]">
                            {{ __('Leave Balance') }}
                        </th>

                        {{-- Personal Info columns (TBA) --}}
                        <th x-show="tab === 'personal'"
                            class="px-3 py-2 text-left text-xs font-medium text-gray-300 dark:text-gray-600 uppercase tracking-wider whitespace-nowrap min-w-[10rem] italic">
                            Coming Soon
                        </th>

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
                                                {{ __('Inactive') }}
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

                            {{-- Salary: TBA --}}
                            <td x-show="tab === 'salary'"
                                class="px-3 py-2 text-gray-300 dark:text-gray-600 text-xs italic">
                                —
                            </td>

                            {{-- Leaves: Leave Balance --}}
                            <td x-show="tab === 'leaves'" class="px-3 py-2 whitespace-nowrap">
                                @if($user->leave_balance !== null)
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        {{ $user->leave_balance }}
                                    </span>
                                    <span class="text-xs text-gray-400 ml-0.5">{{ __('hrs') }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Personal Info: TBA --}}
                            <td x-show="tab === 'personal'"
                                class="px-3 py-2 text-gray-300 dark:text-gray-600 text-xs italic">
                                —
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-10 text-center text-gray-400 text-sm">
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