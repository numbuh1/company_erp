@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet" />
    <style>
        .ql-editor { padding: 0; font-size: 0.9rem; line-height: 1.7; }
        .ql-container.ql-snow { border: none; }
        .ql-editor img { max-width: 100%; border-radius: 0.375rem; }
    </style>
@endpush

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('help-pages.index') }}"
               class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $helpPage->title }}</h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 py-6 space-y-6">
        @php $visibleComponents = $helpPage->components->filter(fn($c) => $c->getContent($locale) || $c->image); @endphp

        @forelse($visibleComponents as $component)
            @php $content = $component->getContent($locale); @endphp
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                {{-- Step indicator --}}
                @if($visibleComponents->count() > 1)
                <div class="flex items-center gap-3 mb-4">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 text-sm font-bold shrink-0">
                        {{ $loop->iteration }}
                    </span>
                    <span class="text-sm font-medium text-gray-400 dark:text-gray-500">
                        {{ __('Step :number', ['number' => $loop->iteration]) }}
                    </span>
                </div>
                @endif

                @if($component->type === 'text_with_image' && $component->image)
                    <div class="flex flex-col md:flex-row gap-6">
                        @if($content)
                        <div class="flex-1 min-w-0">
                            <div class="ql-container ql-snow">
                                <div class="ql-editor text-gray-800 dark:text-gray-200">{!! $content !!}</div>
                            </div>
                        </div>
                        @endif
                        <div class="md:w-2/5 shrink-0">
                            <img src="{{ $component->image }}" alt=""
                                 class="rounded-lg border border-gray-200 dark:border-gray-700 w-full object-cover">
                        </div>
                    </div>
                @elseif($content)
                    <div class="ql-container ql-snow">
                        <div class="ql-editor text-gray-800 dark:text-gray-200">{!! $content !!}</div>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">{{ __('No content available.') }}</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
