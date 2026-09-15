<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.mail-logs.index') }}"
               class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Mail Detail') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Email header card --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('From') }}</dt>
                    <dd class="text-gray-800 dark:text-gray-200">{{ $mailLog->from ?? '—' }}</dd>

                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('To') }}</dt>
                    <dd class="text-gray-800 dark:text-gray-200">{{ implode(', ', $mailLog->to ?? []) }}</dd>

                    @if($mailLog->cc)
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('CC') }}</dt>
                    <dd class="text-gray-800 dark:text-gray-200">{{ implode(', ', $mailLog->cc) }}</dd>
                    @endif

                    @if($mailLog->bcc)
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('BCC') }}</dt>
                    <dd class="text-gray-800 dark:text-gray-200">{{ implode(', ', $mailLog->bcc) }}</dd>
                    @endif

                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('Subject') }}</dt>
                    <dd class="text-gray-800 dark:text-gray-200 font-semibold">{{ $mailLog->subject ?? '—' }}</dd>

                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('Date') }}</dt>
                    <dd class="text-gray-800 dark:text-gray-200">{{ $mailLog->created_at->format('d/m/Y H:i:s') }}</dd>

                    @if($mailLog->mailable_class)
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('Type') }}</dt>
                    <dd><code class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded">{{ class_basename($mailLog->mailable_class) }}</code></dd>
                    @endif
                </dl>
            </div>

            {{-- Email body rendered in iframe --}}
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                @if($mailLog->body)
                    <iframe src="{{ route('admin.mail-logs.preview', $mailLog) }}"
                            class="w-full border-0"
                            style="min-height: 500px;"
                            onload="this.style.height = this.contentWindow.document.documentElement.scrollHeight + 'px'"></iframe>
                @else
                    <div class="p-8 text-center text-gray-400">{{ __('No email content available.') }}</div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
