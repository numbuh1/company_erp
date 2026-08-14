@auth
@php
    $currentRoute = request()->route()?->getName();
    $helpPage = $currentRoute ? \App\Models\HelpPage::forRoute($currentRoute) : null;
    $helpContent = $helpPage
        ? $helpPage->getContent(session('locale', config('app.locale', 'en')))
        : null;
@endphp

@if($helpPage && $helpContent)
<div x-data="{ open: false }"
     @keydown.escape.window="open = false"
     style="position:fixed; bottom:6rem; right:1.5rem; z-index:70">

    {{-- FAB button --}}
    <button type="button" @click="open = true"
        title="{{ __('Help') }}"
        class="flex items-center justify-center w-12 h-12 rounded-full shadow-lg
               bg-indigo-100 dark:bg-indigo-900 hover:bg-indigo-200 dark:hover:bg-indigo-800
               text-indigo-600 dark:text-indigo-300 transition-colors duration-150">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </button>

    {{-- Modal --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="open = false"
         id="help-fab-modal"
         class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 px-4">

        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl
                    border border-gray-200 dark:border-gray-700 flex flex-col max-h-[80vh]">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $helpPage->title }}
                </h3>
                <button type="button" @click="open = false"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="overflow-y-auto flex-1 px-5 py-5">
                <div class="ql-container ql-snow" style="border:none; font-size:0.9rem;">
                    <div class="ql-editor" style="padding:0;">
                        {!! $helpContent !!}
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

{{-- Quill snow CSS for rendering stored content --}}
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet" />
<style>
    #help-fab-modal .ql-container.ql-snow { border: none !important; }
    #help-fab-modal .ql-editor { padding: 0; }
    #help-fab-modal .ql-editor img { max-width: 100%; border-radius: 0.375rem; }
</style>
@endif
@endauth
