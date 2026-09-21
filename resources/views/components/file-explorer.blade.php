@php use Illuminate\Support\Facades\Storage; @endphp

@props([
    'model',
    'routePrefix',
    'storagePath',
    'items',
    'currentFolder',
    'breadcrumb',
    'title'        => 'Files',
    'canUpload'    => true,
    'canManageAll' => false,
    'extraParams'  => [],
])

@php
    $apiUrl          = route($routePrefix . '.files.index', $model);
    $uploadUrl       = route($routePrefix . '.files.upload', $model);
    $folderCreateUrl = route($routePrefix . '.folders.create', $model);

    $renameUrlTpl    = route($routePrefix . '.files.rename', [$model, '__ID__']);
    $deleteUrlTpl    = route($routePrefix . '.files.delete', [$model, '__ID__']);
    $downloadUrlTpl  = route($routePrefix . '.files.download', [$model, '__ID__']);
    $storageBase     = Storage::url($storagePath);

    $initialItems = $items->map(fn ($f) => [
        'id'           => $f->id,
        'is_folder'    => $f->is_folder,
        'display_name' => $f->display_name,
        'size'         => $f->size,
        'uploader'     => $f->uploader?->name,
        'uploaded_by'  => $f->uploaded_by,
        'date'         => $f->created_at->format('d/m/y H:i'),
        'stored_name'  => $f->stored_name,
        'can_manage'   => $canManageAll || $f->uploaded_by === auth()->id(),
        'can_delete'   => $canManageAll || (!$f->is_folder && $f->uploaded_by === auth()->id()),
    ])->values();

    $initialBreadcrumb = collect($breadcrumb)->map(fn ($f) => [
        'id'           => $f->id,
        'display_name' => $f->display_name,
    ])->values();
@endphp

<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6"
    x-data="fileExplorer({
        apiUrl: '{{ $apiUrl }}',
        uploadUrl: '{{ $uploadUrl }}',
        folderCreateUrl: '{{ $folderCreateUrl }}',
        renameUrlTpl: '{{ $renameUrlTpl }}',
        deleteUrlTpl: '{{ $deleteUrlTpl }}',
        downloadUrlTpl: '{{ $downloadUrlTpl }}',
        storageBase: '{{ $storageBase }}',
        csrfToken: '{{ csrf_token() }}',
        initialItems: {{ Js::from($initialItems) }},
        initialBreadcrumb: {{ Js::from($initialBreadcrumb) }},
        initialFolderId: {{ $currentFolder ? $currentFolder->id : 'null' }},
        canUpload: {{ $canUpload ? 'true' : 'false' }},
    })">

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
            <a href="#" @click.prevent="navigate(null)"
                :class="!currentFolderId ? 'font-semibold text-gray-800 dark:text-gray-200' : 'text-blue-500 hover:underline cursor-pointer'">
                Root
            </a>
            <template x-for="(crumb, idx) in breadcrumb" :key="crumb.id">
                <span class="flex items-center gap-1">
                    <span class="text-gray-400">/</span>
                    <a href="#" @click.prevent="navigate(crumb.id)"
                        :class="idx === breadcrumb.length - 1
                            ? 'font-semibold text-gray-800 dark:text-gray-200'
                            : 'text-blue-500 hover:underline cursor-pointer'"
                        x-text="crumb.display_name">
                    </a>
                </span>
            </template>
        </div>

        {{-- Action buttons --}}
        <div class="flex gap-2" x-show="canUpload">
            <button type="button" @click="showNewFolder = true"
                class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                {{ __('New Folder') }}
            </button>
            <button type="button" @click="showUpload = true"
                class="inline-flex items-center gap-1 text-sm px-3 py-1.5 rounded border border-indigo-400 bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                {{ __('Upload') }}
            </button>
        </div>
    </div>

    {{-- Loading indicator --}}
    <div x-show="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-6 w-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    {{-- ── File List ── --}}
    <div x-show="!loading" class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700 text-xs font-medium text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-2 text-left w-1/2">{{ __('Name') }}</th>
                    <th class="px-4 py-2 text-left">{{ __('Size') }}</th>
                    <th class="px-4 py-2 text-left">{{ __('Uploaded By') }}</th>
                    <th class="px-4 py-2 text-left">{{ __('Date') }}</th>
                    <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                {{-- Back row --}}
                <template x-if="currentFolderId !== null">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-4 py-2" colspan="5">
                            <a href="#" @click.prevent="goBack()"
                                class="flex items-center gap-2 text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                ..
                            </a>
                        </td>
                    </tr>
                </template>

                <template x-for="item in items" :key="item.id">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">

                        {{-- Name --}}
                        <td class="px-4 py-2">
                            <template x-if="item.is_folder">
                                <a href="#" @click.prevent="navigate(item.id)"
                                    class="flex items-center gap-2 text-yellow-600 hover:text-yellow-700 font-medium cursor-pointer">
                                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M10 4H2a2 2 0 00-2 2v12a2 2 0 002 2h20a2 2 0 002-2V8a2 2 0 00-2-2h-10L10 4z"/>
                                    </svg>
                                    <span x-text="item.display_name"></span>
                                </a>
                            </template>
                            <template x-if="!item.is_folder">
                                <a :href="storageBase + '/' + item.stored_name" target="_blank"
                                    class="flex items-center gap-2 text-blue-600 hover:text-blue-800">
                                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span x-text="item.display_name"></span>
                                </a>
                            </template>
                        </td>

                        {{-- Size --}}
                        <td class="px-4 py-2 text-gray-500">
                            <span x-text="formatSize(item)"></span>
                        </td>

                        {{-- Uploaded By --}}
                        <td class="px-4 py-2 text-gray-500" x-text="item.uploader || '—'"></td>

                        {{-- Date --}}
                        <td class="px-4 py-2 text-gray-500" x-text="item.date"></td>

                        {{-- Actions --}}
                        <td class="px-4 py-2 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <template x-if="item.can_manage">
                                    <a :href="downloadUrlTpl.replace('__ID__', item.id)"
                                        class="relative group inline-flex items-center justify-center w-7 h-7 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-green-600 hover:border-green-400 bg-white dark:bg-gray-700 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-0.5 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none"
                                            x-text="item.is_folder ? 'Download as ZIP' : 'Download'"></span>
                                    </a>
                                </template>
                                <template x-if="item.can_manage">
                                    <button type="button"
                                        @click="openRename(item)"
                                        class="relative group inline-flex items-center justify-center w-7 h-7 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2.414A2 2 0 019.586 13z"/></svg>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-0.5 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">{{ __('Rename') }}</span>
                                    </button>
                                </template>
                                <template x-if="item.can_delete">
                                    <button type="button"
                                        @click="deleteItem(item)"
                                        class="relative group inline-flex items-center justify-center w-7 h-7 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-0.5 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">{{ __('Delete') }}</span>
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                </template>

                <template x-if="items.length === 0 && !loading">
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                            <span x-text="currentFolderId ? '{{ __('This folder is empty.') }}' : '{{ __('No files yet.') }}'"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- ── New Folder Modal ── --}}
    <div x-show="showNewFolder" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showNewFolder = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('New Folder') }}</h3>
            <form @submit.prevent="createFolder()">
                <div class="mb-4">
                    <x-input-label :value="__('Folder Name')" />
                    <x-text-input x-model="newFolderName" class="mt-1 block w-full" required placeholder="e.g. Documents" />
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button type="button" @click="showNewFolder = false">{{ __('Cancel') }}</x-secondary-button>
                    <x-primary-button :disabled="false" x-bind:disabled="submitting">{{ __('Create') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Upload Modal ── --}}
    <div x-show="showUpload" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showUpload = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Upload File') }}</h3>
            <form @submit.prevent="uploadFile()">
                <div class="mb-4">
                    <x-input-label :value="__('File')" />
                    <input type="file" x-ref="fileInput" required
                        class="mt-1 block w-full text-sm text-gray-700 dark:text-gray-300
                            file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0
                            file:text-sm file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-gray-400">{{ __('Max 50 MB') }}</p>
                </div>

                {{-- Upload progress --}}
                <div x-show="uploadProgress > 0 && uploadProgress < 100" class="mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full transition-all" :style="'width: ' + uploadProgress + '%'"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" x-text="uploadProgress + '%'"></p>
                </div>

                <div class="flex justify-end gap-2">
                    <x-secondary-button type="button" @click="showUpload = false">{{ __('Cancel') }}</x-secondary-button>
                    <x-primary-button x-bind:disabled="submitting">{{ __('Upload') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Rename Modal ── --}}
    <div x-show="showRename" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showRename = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Rename') }}</h3>
            <form @submit.prevent="renameItem()">
                <div class="mb-4">
                    <x-input-label :value="__('New Name')" />
                    <x-text-input x-model="renameName" class="mt-1 block w-full" required />
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button type="button" @click="showRename = false">{{ __('Cancel') }}</x-secondary-button>
                    <x-primary-button x-bind:disabled="submitting">{{ __('Rename') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('fileExplorer', (config) => ({
        apiUrl: config.apiUrl,
        uploadUrl: config.uploadUrl,
        folderCreateUrl: config.folderCreateUrl,
        renameUrlTpl: config.renameUrlTpl,
        deleteUrlTpl: config.deleteUrlTpl,
        downloadUrlTpl: config.downloadUrlTpl,
        storageBase: config.storageBase,
        csrfToken: config.csrfToken,
        canUpload: config.canUpload,

        items: config.initialItems,
        breadcrumb: config.initialBreadcrumb,
        currentFolderId: config.initialFolderId,

        loading: false,
        submitting: false,
        uploadProgress: 0,

        showNewFolder: false,
        showUpload: false,
        showRename: false,

        newFolderName: '',
        renameItemId: null,
        renameName: '',

        async navigate(folderId) {
            this.loading = true;
            try {
                const url = folderId
                    ? `${this.apiUrl}?folder_id=${folderId}`
                    : this.apiUrl;
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.items = data.items;
                this.breadcrumb = data.breadcrumb;
                this.currentFolderId = data.current_folder_id;
            } catch (e) {
                console.error('Failed to load folder', e);
            } finally {
                this.loading = false;
            }
        },

        goBack() {
            if (this.breadcrumb.length > 1) {
                this.navigate(this.breadcrumb[this.breadcrumb.length - 2].id);
            } else {
                this.navigate(null);
            }
        },

        async createFolder() {
            if (this.submitting) return;
            this.submitting = true;
            try {
                const body = new FormData();
                body.append('name', this.newFolderName);
                if (this.currentFolderId) body.append('parent_id', this.currentFolderId);

                await fetch(this.folderCreateUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                this.showNewFolder = false;
                this.newFolderName = '';
                await this.navigate(this.currentFolderId);
            } catch (e) {
                console.error('Failed to create folder', e);
            } finally {
                this.submitting = false;
            }
        },

        async uploadFile() {
            if (this.submitting) return;
            const file = this.$refs.fileInput?.files?.[0];
            if (!file) return;

            this.submitting = true;
            this.uploadProgress = 0;

            try {
                const body = new FormData();
                body.append('file', file);
                if (this.currentFolderId) body.append('parent_id', this.currentFolderId);

                const xhr = new XMLHttpRequest();
                await new Promise((resolve, reject) => {
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                        }
                    });
                    xhr.addEventListener('load', () => resolve());
                    xhr.addEventListener('error', () => reject(new Error('Upload failed')));
                    xhr.open('POST', this.uploadUrl);
                    xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.send(body);
                });

                this.showUpload = false;
                this.uploadProgress = 0;
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                await this.navigate(this.currentFolderId);
            } catch (e) {
                console.error('Failed to upload file', e);
            } finally {
                this.submitting = false;
            }
        },

        openRename(item) {
            this.renameItemId = item.id;
            this.renameName = item.display_name;
            this.showRename = true;
        },

        async renameItem() {
            if (this.submitting || !this.renameItemId) return;
            this.submitting = true;
            try {
                const body = new FormData();
                body.append('name', this.renameName);

                await fetch(this.renameUrlTpl.replace('__ID__', this.renameItemId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                this.showRename = false;
                this.renameItemId = null;
                this.renameName = '';
                await this.navigate(this.currentFolderId);
            } catch (e) {
                console.error('Failed to rename', e);
            } finally {
                this.submitting = false;
            }
        },

        async deleteItem(item) {
            const msg = item.is_folder
                ? '{{ __('Delete this folder and all its contents?') }}'
                : '{{ __('Delete this file?') }}';
            if (!confirm(msg)) return;

            try {
                await fetch(this.deleteUrlTpl.replace('__ID__', item.id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                await this.navigate(this.currentFolderId);
            } catch (e) {
                console.error('Failed to delete', e);
            }
        },

        formatSize(item) {
            if (item.is_folder || !item.size) return '—';
            if (item.size >= 1048576) return (item.size / 1048576).toFixed(1) + ' MB';
            return (item.size / 1024).toFixed(1) + ' KB';
        },
    }));
});
</script>
@endpush
@endonce
