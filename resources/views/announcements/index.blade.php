@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet" />
    <style>
        .ql-editor { padding: 0; font-size: 0.9rem; }
        .ql-container.ql-snow { border: none; }
        .ql-editor img { max-width: 100%; border-radius: 0.375rem; }
    </style>
@endpush

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Thông báo</h2>
            @can('edit announcements')
                <a href="{{ route('announcements.create') }}"><x-primary-button>Thông báo mới</x-primary-button></a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-full mx-auto sm:px-6 lg:px-8 py-4">

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col md:flex-row" style="min-height: 75vh;">

            {{-- ── Left: list pane ──────────────────────────────────────── --}}
            <div class="w-full md:w-80 lg:w-96 shrink-0 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700 flex flex-col">

                {{-- Search --}}
                <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                    <form method="GET" action="{{ route('announcements.index') }}" class="flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Tìm kiếm…"
                               class="block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit"
                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition shrink-0">
                            Tìm
                        </button>
                        @if(request('search'))
                            <a href="{{ route('announcements.index') }}"
                               class="px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition shrink-0">
                                ✕
                            </a>
                        @endif
                    </form>
                </div>

                {{-- List --}}
                <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700" style="max-height: 60vh;">
                    @php $qs = request()->getQueryString(); @endphp
                    @forelse($announcements as $item)
                        @php $isActive = $selected && $selected->id === $item->id; @endphp
                        <a href="{{ route('announcements.show', $item) }}{{ $qs ? '?' . $qs : '' }}"
                           class="block px-4 py-3 transition {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' : 'border-l-4 border-transparent hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <div class="flex justify-between items-baseline gap-2">
                                <p class="text-sm font-semibold truncate {{ $isActive ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-800 dark:text-gray-100' }}">
                                    {{ $item->title }}
                                </p>
                                <span class="text-[11px] text-gray-400 shrink-0">{{ $item->created_at->format('d/m') }}</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $item->author?->name ?? 'System' }}</p>
                            <div class="mt-1">
                                @if($item->teams->isEmpty())
                                    <span class="inline-flex items-center gap-1 text-[11px] bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-1.5 py-0.5 rounded">🌐 All Company</span>
                                @else
                                    @foreach($item->teams as $team)
                                        <span class="inline-flex items-center gap-1 text-[11px] bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-1.5 py-0.5 rounded mr-1">👥 {{ $team->name }}</span>
                                    @endforeach
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-400">
                            {{ request('search') ? 'Không tìm thấy thông báo nào khớp với tìm kiếm.' : 'Chưa có thông báo.' }}
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($announcements->hasPages())
                    <div class="border-t border-gray-200 dark:border-gray-700 p-2 text-sm">
                        {{ $announcements->links() }}
                    </div>
                @endif
            </div>

            {{-- ── Right: content pane ──────────────────────────────────── --}}
            <div class="flex-1 min-w-0 flex flex-col" style="max-height: 75vh;">
                @if($selected)
                    <div class="p-6 pb-0 shrink-0">
                        <div class="flex justify-between items-start gap-4 mb-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $selected->title }}</h3>
                            <div class="flex gap-2 shrink-0">
                                @can('edit announcements')
                                    <a href="{{ route('announcements.edit', $selected) }}">
                                        <x-secondary-button>Chỉnh sửa</x-secondary-button>
                                    </a>
                                @endcan
                                @can('delete announcements')
                                    <form method="POST" action="{{ route('announcements.destroy', $selected) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" onclick="return confirm('Delete this announcement?')"
                                            class="inline-flex items-center px-3 py-1.5 text-sm text-red-600 border border-red-300 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                            Xóa
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mb-6">
                            {{ $selected->author?->name ?? 'System' }}
                            · {{ $selected->created_at->format('d/m/Y H:i') }}
                            @if($selected->updated_at->ne($selected->created_at))
                                · <span class="italic">đã sửa {{ $selected->updated_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex-1 overflow-y-auto px-6 pb-6">
                        <div class="ql-container ql-snow">
                            <div class="ql-editor text-gray-800 dark:text-gray-200">
                                {!! $selected->content !!}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex-1 flex items-center justify-center text-gray-400 text-sm">
                        Chưa có thông báo nào.
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
