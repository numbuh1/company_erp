<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Dark mode init (must be before CSS to avoid flash) -->
        <script>
            (function() {
                const theme = localStorage.getItem('theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/multi-select-dropdown-js/MultiSelect.min.css">
        <style>
            /* "N được chọn" collapse badge for multi-selects with 3+ selections */
            .ms-count-badge {
                display: inline-flex;
                align-items: center;
                padding: 2px 10px;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
                white-space: nowrap;
                background: #e0e7ff;
                color: #4338ca;
            }
            .dark .ms-count-badge { background: #3730a3; color: #c7d2fe; }
        </style>

        {{-- Tom Select --}}
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.5.2/dist/css/tom-select.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.5.2/dist/js/tom-select.complete.min.js" defer></script>

    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900" x-data="{ sidebarOpen: false }">

            @include('layouts.navigation')

            <div class="flex">

                @include('layouts.sidebar')

                <!-- Main content -->
                <div class="flex-1 min-w-0">
                    @isset($header)
                        <header class="bg-white dark:bg-gray-800 shadow">
                            <div class="py-6 px-4 sm:px-6 lg:px-8">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <main>
                        {{ $slot }}
                    </main>
                </div>

            </div>
        </div>

        @include('layouts.checkin-fab')
        <x-leave-request-modal />
        <x-ot-request-modal />
        <x-pending-requests-fab />

        <script>
            function toggleDarkMode() {
                const html = document.documentElement;
                const isDark = html.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            }

            function markNotificationsRead() {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                fetch('{{ route("notifications.mark-read") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                }).then(() => {
                    const badge = document.querySelector('[x-ref="badge"]');
                    if (badge) badge.remove();
                }).catch(() => {});
            }
        </script>
        @stack('scripts')
        <script src="https://cdn.jsdelivr.net/npm/multi-select-dropdown-js/MultiSelect.min.js"></script>
        <script>
            (function () {
                function getTheme() {
                    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                }

                function initMultiSelects() {
                    document.querySelectorAll('[data-multi-select]').forEach(function (el) {
                        if (el._multiSelect) el._multiSelect.destroy();

                        // Collapse header tags to "{N} được chọn" when 3+ items selected
                        function collapseIfNeeded(msEl) {
                            if (!msEl) return;
                            var header = msEl.querySelector('.multi-select-header');
                            if (!header) return;
                            var tags = header.querySelectorAll('.multi-select-header-option');
                            if (tags.length >= 3) {
                                var n = tags.length;
                                tags.forEach(function (t) { t.style.display = 'none'; });
                                var badge = header.querySelector('.ms-count-badge');
                                if (!badge) {
                                    badge = document.createElement('span');
                                    badge.className = 'ms-count-badge';
                                    header.prepend(badge);
                                }
                                badge.textContent = n + ' được chọn';
                            }
                        }

                        var inst = new MultiSelect(el, {
                            theme: getTheme(),
                            onChange: function () { collapseIfNeeded(inst.element); }
                        });
                        collapseIfNeeded(inst.element);
                    });
                }

                document.addEventListener('DOMContentLoaded', initMultiSelects);

                // Keep theme in sync when dark mode is toggled
                new MutationObserver(function () {
                    var theme = getTheme();
                    document.querySelectorAll('.multi-select').forEach(function (ms) {
                        ms.setAttribute('data-theme', theme);
                    });
                }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            })();
        </script>
    </body>
</html>
