<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Mail History') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded text-sm">{{ session('success') }}</div>
            @endif

            {{-- Logging toggle + Search --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <form method="POST" action="{{ route('admin.mail-logs.toggle-logging') }}" class="flex items-center gap-3">
                    @csrf
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1"
                            {{ $loggingEnabled ? 'checked' : '' }}
                            onchange="this.form.submit()"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ __('Enable mail logging') }}</span>
                    </label>
                </form>

                <form method="GET" action="{{ route('admin.mail-logs.index') }}" class="flex items-center gap-2">
                    <x-text-input name="search" class="text-sm py-1.5"
                        value="{{ request('search') }}"
                        placeholder="{{ __('Search subject or recipient…') }}" />
                    <x-primary-button class="py-1.5 text-sm">{{ __('Search') }}</x-primary-button>
                    @if(request('search'))
                        <a href="{{ route('admin.mail-logs.index') }}" class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('To') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Subject') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Type') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($mailLogs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer"
                                onclick="window.location='{{ route('admin.mail-logs.show', $log) }}'">
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200 max-w-[200px] truncate">
                                    {{ implode(', ', $log->to ?? []) }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200 max-w-[300px] truncate">
                                    {{ $log->subject ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($log->mailable_class)
                                        <code class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded">{{ class_basename($log->mailable_class) }}</code>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">{{ __('No emails logged yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($mailLogs->hasPages())
                <div class="mt-4">{{ $mailLogs->links() }}</div>
            @endif

        </div>
    </div>
</x-app-layout>
