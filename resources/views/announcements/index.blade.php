<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Thông báo</h2>
            @can('edit announcements')
                <a href="{{ route('announcements.create') }}"><x-primary-button>Thông báo mới</x-primary-button></a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            {{-- Search --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('announcements.index') }}" class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Tìm kiếm theo tiêu đề hoặc nội dung…"
                           class="block w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-sm text-sm px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition shrink-0">
                        Tìm
                    </button>
                    @if(request('search'))
                        <a href="{{ route('announcements.index') }}"
                           class="px-4 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition shrink-0">
                            Đặt lại
                        </a>
                    @endif
                </form>
            </div>

            @forelse($announcements as $announcement)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex-1 min-w-0 mr-4">
                            <a href="{{ route('announcements.show', $announcement) }}"
                                class="text-lg font-semibold text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                {{ $announcement->title }}
                            </a>
                            <p class="text-xs text-gray-400 mt-1">
                                Người đăng: {{ $announcement->author?->name ?? 'System' }}
                                · {{ $announcement->created_at->format('d/m/Y H:i') }}
                            </p>
                            <div class="mt-2">
                                @if($announcement->teams->isEmpty())
                                    <span class="inline-flex items-center gap-1 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded">
                                        🌐 All Company
                                    </span>
                                @else
                                    @foreach($announcement->teams as $team)
                                        <span class="inline-flex items-center gap-1 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded mr-1">
                                            👥 {{ $team->name }}
                                        </span>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            @can('edit announcements')
                                <a href="{{ route('announcements.edit', $announcement) }}">
                                    <x-secondary-button>Chỉnh sửa</x-secondary-button>
                                </a>
                            @endcan
                            @can('delete announcements')
                                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this announcement?')"
                                        class="inline-flex items-center px-3 py-1.5 text-sm text-red-600 border border-red-300 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        Xóa
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    {{ request('search') ? 'Không tìm thấy thông báo nào khớp với tìm kiếm.' : 'Chưa có thông báo.' }}
                </div>
            @endforelse

            <div>{{ $announcements->links() }}</div>
        </div>
    </div>
</x-app-layout>
