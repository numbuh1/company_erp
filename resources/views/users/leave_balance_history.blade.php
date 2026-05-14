<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Leave Balance History — {{ $user->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                Current balance: <strong>{{ $user->leave_balance }}h</strong>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Changed By') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Change') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Balance After') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Reason') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($logs as $log)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $log->created_at->format('d/m/y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $log->changedBy->name }}
                                </td>
                                <td class="px-4 py-3 text-sm font-medium">
                                    <span class="{{ $log->change_hours >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $log->change_hours >= 0 ? '+' : '' }}{{ $log->change_hours }}h
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $log->balance_after }}h
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ $log->reason ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">{{ __('No history found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $logs->links() }}
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('users.index') }}">
                    <x-secondary-button>{{ __('Back') }}</x-secondary-button>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
