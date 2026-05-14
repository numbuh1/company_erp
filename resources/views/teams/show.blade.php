<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Team Details
            </h2>

            <div class="space-x-2">
                @can('edit teams')
                    <a href="{{ route('teams.edit', $team) }}">
                        <x-secondary-button>Chỉnh sửa</x-secondary-button>
                    </a>
                @endcan

                <a href="{{ route('teams.index') }}">
                    <x-secondary-button>Quay lại</x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Team Info -->
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ $team->name }}
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    {{ $team->users->count() }} members
                </p>
            </div>

            <div class="grid grid-cols-2 gap-6">

                <!-- Leaders -->
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b dark:border-gray-700">
                        <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200">
                            Trưởng nhóm
                        </h3>
                    </div>

                    <div class="p-6">
                        @php
                            $leaders = $team->users->where('pivot.is_leader', true)->sortBy('name');
                        @endphp

                        @forelse($leaders as $leader)
                            <div class="flex justify-between items-center mb-3">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $leader->name }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $leader->email }}
                                    </p>
                                </div>

                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                    Trưởng nhóm
                                </span>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">Chưa có trưởng nhóm.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Members -->
                <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b dark:border-gray-700">
                        <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200">
                            Thành viên
                        </h3>
                    </div>

                    <div class="p-6">
                        @php
                            $members = $team->users->where('pivot.is_leader', false)->sortBy('name');
                        @endphp

                        @forelse($members as $member)
                            <div class="flex justify-between items-center mb-3">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $member->name }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $member->email }}
                                    </p>
                                </div>

                                <span class="text-gray-500 text-sm">
                                    Thành viên
                                </span>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">Chưa có thành viên.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>