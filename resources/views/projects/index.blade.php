<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Dự án</h2>
            @can('edit projects')
                <a href="{{ route('projects.create') }}">
                    <x-primary-button>Tạo dự án</x-primary-button>
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12" x-data="{
        teamModal: false,
        teamName: '',
        activeTeamId: null,
        openTeamModal(id, name) {
            this.activeTeamId = id;
            this.teamName = name;
            this.teamModal = true;
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhóm</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thành viên khác</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bắt đầu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bắt đầu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">End (EST)</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($projects as $project)
                            @php
                                $statusClass = match($project->status) {
                                    'In Progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                    'Done'        => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                                    default       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-4">
                                    <a href="{{ route('projects.show', $project) }}"
                                        class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline whitespace-nowrap">
                                        PJ-{{ $project->id }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 font-medium text-gray-900 dark:text-gray-100">{{ $project->name }}</td>
                                <td class="px-4 py-4">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded {{ $statusClass }} whitespace-nowrap">{{ $project->status }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($project->teams as $team)
                                            <button type="button"
                                                @click="openTeamModal({{ $team->id }}, '{{ addslashes($team->name) }}')"
                                                class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 text-xs px-2 py-0.5 rounded hover:bg-blue-200 dark:hover:bg-blue-800 cursor-pointer transition">
                                                {{ $team->name }}
                                            </button>
                                        @empty
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    @php
                                        $directMembers = $project->users->take(4);
                                        $extra = max(0, $project->users->count() - 4);
                                    @endphp
                                    <div class="flex items-center flex-wrap gap-1">
                                        @foreach($directMembers as $member)
                                            <x-user-status :user="$member" :show-name="false" />
                                        @endforeach
                                        @if($extra > 0)
                                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">+{{ $extra }}</span>
                                        @endif
                                        @if($project->users->isEmpty())
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $project->start_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $project->start_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $project->expected_end_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('projects.show', $project) }}" title="Xem"
                                            class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-blue-600 hover:border-blue-400 bg-white dark:bg-gray-700 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xem</span>
                                        </a>

                                        @canany(['edit projects', 'edit assigned projects'])
                                            <a href="{{ route('projects.edit', $project) }}" title="Chỉnh sửa"
                                                class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Chỉnh sửa</span>
                                            </a>
                                        @endcanany

                                        @can('delete projects')
                                            <form method="POST" action="{{ route('projects.destroy', $project) }}" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Xóa"
                                                    onclick="return confirm('Delete this project?')"
                                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xóa</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-6 py-6 text-center text-gray-500">Không tìm thấy dự án.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-4">{{ $projects->links() }}</div>
            </div>
        </div>

        {{-- Team Members Modal --}}
        @php $allTeams = $projects->getCollection()->flatMap(fn($p) => $p->teams)->unique('id'); @endphp
        <x-team-modal :teams="$allTeams" />
    </div>
</x-app-layout>
