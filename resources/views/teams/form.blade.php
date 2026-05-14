<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($team) ? 'Edit Team' : 'Create Team' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ isset($team) ? route('teams.update', $team) : route('teams.store') }}">
                    @csrf
                    @if(isset($team))
                        @method('PUT')
                    @endif

                    <!-- Team Name -->
                    <div class="mb-6">
                        <x-input-label for="name" value="{{ __('Team Name') }}" />
                        <x-text-input 
                            id="name"
                            name="name"
                            class="mt-1 block w-full"
                            :value="$team->name ?? ''"
                            required
                        />
                    </div>

                    @php
                        $assigned = collect();
                        $unassigned = collect();

                        foreach ($users as $user) {
                            $pivot = $team?->users->firstWhere('id', $user->id)?->pivot;

                            if ($pivot) {
                                $assigned->push([
                                    'model' => $user,
                                    'is_leader' => $pivot->is_leader
                                ]);
                            } else {
                                $unassigned->push($user);
                            }
                        }

                        // Sort assigned: leaders first, then alphabetical
                        $assigned = $assigned->sortBy([
                            fn ($u) => !$u['is_leader'], // leaders first
                            fn ($u) => $u['model']->name
                        ]);

                        // Sort unassigned alphabetically
                        $unassigned = $unassigned->sortBy('name');
                    @endphp

                    <div class="grid grid-cols-2 gap-6">

                        <!-- LEFT: Unassigned -->
                        <div>
                            <h3 class="font-bold mb-2 text-gray-800 dark:text-gray-200">{{ __('Unassigned Users') }}</h3>

                            <input 
                                type="text" 
                                placeholder="{{ __('Search...') }}" 
                                class="mb-2 w-full border rounded p-2"
                                id="search-unassigned"
                            >

                            <div id="unassigned-list" class="border border-gray-300 dark:border-gray-600 rounded p-3 min-h-[300px] space-y-2">
                                @foreach($unassigned as $user)
                                    <div class="user-item flex items-center justify-between" data-id="{{ $user->id }}">
                                        
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" class="assign-toggle">
                                            <span class="text-sm text-gray-800 dark:text-gray-200">{{ $user->name }}</span>
                                        </label>

                                        <!-- hidden input -->
                                        <input type="hidden" name="users[]" value="{{ $user->id }}" disabled>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- RIGHT: Assigned -->
                        <div>
                            <h3 class="font-bold mb-2 text-gray-800 dark:text-gray-200">{{ __('Assigned Users') }}</h3>

                            <input 
                                type="text" 
                                placeholder="{{ __('Search...') }}" 
                                class="mb-2 w-full border rounded p-2"
                                id="search-assigned"
                            >

                            <div id="assigned-list" class="border rounded p-3 min-h-[300px] space-y-2">
                                @foreach($assigned as $item)
                                    <div class="user-item flex items-center justify-between" data-id="{{ $item['model']->id }}">
                                        
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" class="assign-toggle" checked>
                                            <span class="text-sm text-gray-800 dark:text-gray-200">{{ $item['model']->name }}</span>
                                        </label>

                                        <div class="flex items-center space-x-2">
                                            <!-- Leader -->
                                            <label class="text-sm flex items-center space-x-1">
                                                <input 
                                                    type="checkbox" 
                                                    class="leader-checkbox"
                                                    name="leaders[]" 
                                                    value="{{ $item['model']->id }}"
                                                    {{ $item['is_leader'] ? 'checked' : '' }}
                                                >
                                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Leader') }}</span>
                                            </label>
                                        </div>

                                        <!-- active input -->
                                        <input type="hidden" name="users[]" value="{{ $item['model']->id }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end mt-6 space-x-2">
                        <a href="{{ route('teams.index') }}">
                            <x-secondary-button>
                                {{ __('Cancel') }}
                            </x-secondary-button>
                        </a>

                        <x-primary-button>
                            {{ isset($team) ? 'Update Team' : 'Create Team' }}
                        </x-primary-button>
                    </div>

                </form>

            </div>

        </div>
    </div>
    @push('scripts')
        @vite('resources/js/teams/form.js')
    @endpush
</x-app-layout>