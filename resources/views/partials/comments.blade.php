@php /** @var \Illuminate\Database\Eloquent\Model $commentable */ @endphp

<div x-data="{
    previews: [],
    lightbox: null,
    handleFiles(e) {
        this.previews = [];
        Array.from(e.target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = ev => this.previews.push(ev.target.result);
            reader.readAsDataURL(file);
        });
    }
}">

    {{-- Lightbox overlay --}}
    <div x-show="lightbox !== null" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
         @click.self="lightbox = null" @keydown.escape.window="lightbox = null">
        <button @click="lightbox = null"
            class="absolute top-4 right-4 text-white hover:text-gray-300 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <img :src="lightbox" class="max-w-full max-h-[90vh] rounded shadow-xl object-contain">
    </div>

    {{-- Comment list --}}
    <div class="space-y-5 mb-6">
        @forelse($commentable->comments as $comment)
            <div class="flex gap-3">
                {{-- Avatar --}}
                <div class="shrink-0 pt-0.5">
                    @if($comment->user->profile_picture)
                        <img src="{{ asset('storage/profile_pictures/' . $comment->user->profile_picture) }}"
                             class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-600">
                    @else
                        <div class="w-8 h-8 rounded-full bg-pink-100 dark:bg-pink-900 flex items-center justify-center text-pink-600 dark:text-pink-300 text-sm font-bold">
                            {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                {{-- Bubble --}}
                <div class="flex-1 min-w-0">
                    <div class="bg-gray-50 dark:bg-gray-700/60 rounded-xl px-4 py-3">
                        <div class="flex items-start justify-between gap-2 mb-1.5">
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $comment->user->name }}</span>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs text-gray-400">{{ $comment->created_at->format('d/m/y H:i') }}</span>
                                @if($comment->user_id === auth()->id() || auth()->user()->can('edit all user'))
                                    <form method="POST" action="{{ route('comments.destroy', $comment) }}"
                                          onsubmit="return confirm('Delete this comment?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete comment"
                                            class="text-gray-300 dark:text-gray-500 hover:text-red-500 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $comment->body }}</p>
                    </div>

                    {{-- Attached images --}}
                    @if($comment->images)
                        <div class="flex flex-wrap gap-2 mt-2 ml-1">
                            @foreach($comment->images as $img)
                                <button type="button"
                                    @click="lightbox = '{{ asset('storage/comment_images/' . $img) }}'"
                                    class="w-20 h-20 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 hover:opacity-80 transition focus:outline-none">
                                    <img src="{{ asset('storage/comment_images/' . $img) }}"
                                         class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400 italic">No comments yet. Be the first!</p>
        @endforelse
    </div>

    {{-- Post form --}}
    <form method="POST" action="{{ route('comments.store') }}"
          enctype="multipart/form-data" class="flex gap-3">
        @csrf
        <input type="hidden" name="commentable_type" value="{{ $commentableType }}">
        <input type="hidden" name="commentable_id"   value="{{ $commentable->id }}">

        {{-- Current user avatar --}}
        <div class="shrink-0 pt-0.5">
            @if(auth()->user()->profile_picture)
                <img src="{{ asset('storage/profile_pictures/' . auth()->user()->profile_picture) }}"
                     class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-600">
            @else
                <div class="w-8 h-8 rounded-full bg-pink-100 dark:bg-pink-900 flex items-center justify-center text-pink-600 dark:text-pink-300 text-sm font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            @endif
        </div>

        <div class="flex-1 min-w-0">
            <textarea name="body" rows="2" required
                placeholder="Write a comment…"
                class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none transition-all"
                @focus="$el.rows = 4" @blur="if(!$el.value.trim()) $el.rows = 2"></textarea>

            {{-- Image previews --}}
            <div x-show="previews.length > 0" class="flex flex-wrap gap-2 mt-2">
                <template x-for="(src, i) in previews" :key="i">
                    <div class="w-16 h-16 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 shrink-0">
                        <img :src="src" class="w-full h-full object-cover">
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-between mt-2">
                <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Attach images
                    <input type="file" name="images[]" accept="image/*" multiple class="hidden"
                           @change="handleFiles($event)">
                </label>
                <x-primary-button type="submit">Post</x-primary-button>
            </div>
        </div>
    </form>
</div>