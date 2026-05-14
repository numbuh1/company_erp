@php use Illuminate\Support\Facades\Storage; @endphp

@props([
    'model',           // The parent Eloquent model (e.g. $project)
    'routePrefix',     // Route name prefix, e.g. 'projects' → builds 'projects.files.upload' etc.
    'storagePath',     // Storage path prefix for files, e.g. 'project_files/5'
    'items',           // Collection<ProjectFile> — contents of current folder
    'currentFolder',   // ProjectFile|null — null means root
    'breadcrumb',      // array<ProjectFile> — ancestor folders root → current
    'title'      => 'Files',
    'canUpload'   => true,
    'canManageAll' => false,  // true = can rename/delete anything (not just own uploads)
])

@php
    $showUrl        = route($routePrefix . '.show', $model);
    $uploadUrl      = route($routePrefix . '.files.upload', $model);
    $folderCreateUrl = route($routePrefix . '.folders.create', $model);
@endphp

<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6"
    x-data="{
        showNewFolder: false,
        showUpload: false,
        showRename: false,
        renameUrl: '',
        renameName: '',
        openRename(url, name) {
            this.renameUrl = url;
            this.renameName = name;
            this.showRename = true;
        }
    }">

    @if($title)
        <h3 class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4">{{ $title }}</h3>
    @endif

    {{-- ── Header ── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-1 text-sm flex-wrap">
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
            <a href="{{ $showUrl }}"
                class="{{ !$currentFolder ? 'font-semibold text-gray-800 dark:text-gray-200' : 'text-blue-500 hover:underline' }}">
                Root
            </a>
            @foreach($breadcrumb as $crumb)
                <span class="text-gray-400">/</span>
                <a href="{{ $showUrl }}?folder_id={{ $crumb->id }}"
                    class="{{ $loop->last ? 'font-semibold text-gray-800 dark:text-gray-200' : 'text-blue-500 hover:underline' }}">
                    {{ $crumb->display_name }}
                </a>
            @endforeach
        </div>

        {{-- Action buttons --}}
        @if($canUpload)
            <div class="flex gap-2">
                <button type="button" @click="showNewFolder = true"
                    class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    New Folder
                </button>
                <button type="button" @click="showUpload = true"
                    class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded border border-indigo-400 bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Upload
                </button>
            </div>
        @endif
    </div>

    {{-- ── File List ── --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700 text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-2 text-left w-1/2">Name</th>
                    <th class="px-4 py-2 text-left">Size</th>
                    <th class="px-4 py-2 text-left">Uploaded By</th>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                {{-- Back row --}}
                @if($currentFolder)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-4 py-2" colspan="5">
                            <a href="{{ $currentFolder->parent_id
                                    ? $showUrl . '?folder_id=' . $currentFolder->parent_id
                                    : $showUrl }}"
                                class="flex items-center gap-2 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                ..
                            </a>
                        </td>
                    </tr>
                @endif

                @forelse($items as $item)
                    @php
                        $canManageItem   = $canManageAll || $item->uploaded_by === auth()->id();
                        $canDeleteItem   = $canManageAll || (!$item->is_folder && $item->uploaded_by === auth()->id());
                        $renameUrl     = route($routePrefix . '.files.rename', [$model, $item]);
                        $deleteUrl     = route($routePrefix . '.files.delete', [$model, $item]);
                        $downloadUrl   = route($routePrefix . '.files.download', [$model, $item]);
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">

                        {{-- Name --}}
                        <td class="px-4 py-2">
                            @if($item->is_folder)
                                <a href="{{ $showUrl }}?folder_id={{ $item->id }}"
                                    class="flex items-center gap-2 text-yellow-600 hover:text-yellow-700 font-medium">
                                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M10 4H2a2 2 0 00-2 2v12a2 2 0 002 2h20a2 2 0 002-2V8a2 2 0 00-2-2h-10L10 4z"/>
                                    </svg>
                                    {{ $item->display_name }}
                                </a>
                            @else
                                <a href="{{ Storage::url($storagePath . '/' . $item->stored_name) }}"
                                    target="_blank"
                                    class="flex items-center gap-2 text-blue-600 hover:text-blue-800">
                                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    {{ $item->display_name }}
                                </a>
                            @endif
                        </td>

                        {{-- Size --}}
                        <td class="px-4 py-2 text-gray-500">
                            @if(!$item->is_folder && $item->size)
                                {{ $item->size >= 1048576
                                    ? round($item->size / 1048576, 1) . ' MB'
                                    : round($item->size / 1024, 1) . ' KB' }}
                            @else
                                —
                            @endif
                        </td>

                        {{-- Uploaded By --}}
                        <td class="px-4 py-2 text-gray-500">{{ $item->uploader?->name ?? '—' }}</td>

                        {{-- Date --}}
                        <td class="px-4 py-2 text-gray-500">{{ $item->created_at->format('d/m/y H:i') }}</td>

                        {{-- Actions --}}
                        <td class="px-4 py-2 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if($canManageItem)
                                    {{-- Download --}}
                                    <a href="{{ $downloadUrl }}"
                                        class="relative group inline-flex items-center justify-center w-7 h-7 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-green-600 hover:border-green-400 bg-white dark:bg-gray-700 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-0.5 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">
                                            {{ $item->is_folder ? 'Download as ZIP' : 'Download' }}
                                        </span>
                                    </a>

                                    {{-- Rename --}}
                                    <button type="button"
                                        @click="openRename('{{ $renameUrl }}', '{{ addslashes($item->display_name) }}')"
                                        class="relative group inline-flex items-center justify-center w-7 h-7 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2.414A2 2 0 019.586 13z"/></svg>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-0.5 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Rename</span>
                                    </button>
                                @endif

                                @if($canDeleteItem)
                                    {{-- Delete --}}
                                    <form method="POST" action="{{ $deleteUrl }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('{{ $item->is_folder ? 'Delete this folder and all its contents?' : 'Delete this file?' }}')"
                                            class="relative group inline-flex items-center justify-center w-7 h-7 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-0.5 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Delete</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                            {{ $currentFolder ? 'This folder is empty.' : 'No files yet.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── New Folder Modal ── --}}
    <div x-show="showNewFolder" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showNewFolder = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">New Folder</h3>
            <form method="POST" action="{{ $folderCreateUrl }}">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $currentFolder?->id }}">
                <div class="mb-4">
                    <x-input-label value="Folder Name" />
                    <x-text-input name="name" class="mt-1 block w-full" required placeholder="e.g. Documents" />
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button type="button" @click="showNewFolder = false">Cancel</x-secondary-button>
                    <x-primary-button>Create</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Upload Modal ── --}}
    <div x-show="showUpload" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showUpload = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Upload File</h3>
            <form method="POST" action="{{ $uploadUrl }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $currentFolder?->id }}">
                <div class="mb-4">
                    <x-input-label value="File" />
                    <input type="file" name="file" required
                        class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300
                            file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0
                            file:text-sm file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-400">Max 50 MB</p>
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button type="button" @click="showUpload = false">Cancel</x-secondary-button>
                    <x-primary-button>Upload</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Rename Modal ── --}}
    <div x-show="showRename" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showRename = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Rename</h3>
            <form method="POST" :action="renameUrl">
                @csrf
                <div class="mb-4">
                    <x-input-label value="New Name" />
                    <x-text-input name="name" class="mt-1 block w-full" x-model="renameName" required />
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button type="button" @click="showRename = false">Cancel</x-secondary-button>
                    <x-primary-button>Rename</x-primary-button>
                </div>
            </form>
        </div>
    </div>

</div>
