@props(['teams'])

<div x-show="teamModal" x-cloak @click.self="teamModal = false"
     class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm max-h-[80vh] flex flex-col">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100" x-text="teamName"></h3>
            <button @click="teamModal = false"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl leading-none">&times;</button>
        </div>
        <div class="overflow-y-auto px-4 py-3 space-y-1">
            @foreach($teams as $team)
                <div x-show="activeTeamId === {{ $team->id }}">
                    @forelse($team->users as $user)
                        <a href="{{ route('users.show', $user) }}"
                           class="flex items-center gap-2 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <div class="flex-1 min-w-0">
                                <x-user-status :user="$user" />
                            </div>
                            @if($user->pivot->is_leader)
                                <span class="shrink-0 text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 px-1.5 py-0.5 rounded font-medium">Leader</span>
                            @endif
                        </a>
                    @empty
                        <p class="text-sm text-gray-400 py-2">No members in this team.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</div>