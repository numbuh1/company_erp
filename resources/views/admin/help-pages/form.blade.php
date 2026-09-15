@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet" />
    <style>
        .help-editor { min-height: 180px; font-size: 0.9rem; }
        .help-editor img { max-width: 100%; border-radius: 0.375rem; }
        .ql-toolbar.ql-snow { border-radius: 0.375rem 0.375rem 0 0; border-color: rgb(209 213 219); }
        .ql-container.ql-snow { border-radius: 0 0 0.375rem 0.375rem; border-color: rgb(209 213 219); }
        .dark .ql-toolbar.ql-snow { border-color: rgb(75 85 99); }
        .dark .ql-container.ql-snow { border-color: rgb(75 85 99); }
        .dark .ql-editor { color: #e5e7eb; }
        .dark .ql-snow .ql-stroke { stroke: #9ca3af; }
        .dark .ql-snow .ql-fill { fill: #9ca3af; }
        .dark .ql-snow .ql-picker-label { color: #9ca3af; }
    </style>
@endpush

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($helpPage) ? __('Edit Help Page') : __('Add Help Page') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <form id="help-page-form" method="POST"
                    action="{{ isset($helpPage) ? route('admin.help-pages.update', $helpPage) : route('admin.help-pages.store') }}">
                    @csrf
                    @if(isset($helpPage)) @method('PUT') @endif

                    @if($errors->any())
                        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Title --}}
                    <div class="mb-5">
                        <x-input-label :value="__('Title')" />
                        <x-text-input name="title" class="w-full mt-1"
                            value="{{ old('title', $helpPage->title ?? '') }}"
                            placeholder="{{ __('Help page title…') }}" />
                        @error('title')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Route (optional — without a route the page only appears in Help Pages) --}}
                    <div class="mb-5">
                        <x-input-label :value="__('Route')" />
                        <p class="text-xs text-gray-400 mt-0.5 mb-1">{{ __('Optional. Links this help page to a specific screen via the Help button.') }}</p>
                        <select name="route" id="route-select"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">— {{ __('None') }} —</option>
                            @foreach($routes as $routeName)
                                <option value="{{ $routeName }}"
                                    {{ old('route', $helpPage->route ?? '') === $routeName ? 'selected' : '' }}>
                                    {{ $routeName }}
                                </option>
                            @endforeach
                        </select>
                        @error('route')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Active toggle --}}
                    <div class="mb-6">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                {{ old('is_active', $helpPage->is_active ?? true) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ __('Active') }}</span>
                        </label>
                        <p class="text-xs text-gray-400 mt-0.5 ml-6">{{ __('Only active help pages will show the Help button to users.') }}</p>
                    </div>

                    {{-- Components --}}
                    @php
                        $initialComponents = isset($helpPage)
                            ? $helpPage->components->map(fn($c) => [
                                'id'   => $c->id,
                                'type' => $c->type,
                                'image' => $c->image ?? '',
                                '_initialContent' => $c->contents->pluck('content', 'locale')->toArray(),
                            ])->values()->toArray()
                            : [];
                    @endphp

                    <div x-data="helpEditor()" class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <x-input-label :value="__('Components')" />
                            <span class="text-xs text-gray-400" x-text="components.length + ' {{ __('step(s)') }}'"></span>
                        </div>

                        <template x-for="(comp, idx) in components" :key="comp._key">
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 mb-4 bg-gray-50 dark:bg-gray-700/30"
                                 x-init="$nextTick(() => _hpInitEditors(comp._key, comp._initialContent))">

                                {{-- Header --}}
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 text-xs font-bold"
                                          x-text="idx + 1"></span>

                                    <select x-model="comp.type"
                                        class="text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 py-1 px-2">
                                        <option value="text">{{ __('Rich Text') }}</option>
                                        <option value="text_with_image">{{ __('Rich Text + Image') }}</option>
                                    </select>

                                    <div class="ml-auto flex items-center gap-0.5">
                                        <button type="button" @click="moveUp(idx)" :disabled="idx === 0"
                                            class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-400 disabled:opacity-30 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <button type="button" @click="moveDown(idx)" :disabled="idx === components.length - 1"
                                            class="p-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-400 disabled:opacity-30 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <button type="button" @click="removeComponent(idx)"
                                            class="p-1.5 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-red-400 hover:text-red-600 transition-colors ml-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Image upload (text_with_image only) --}}
                                <div x-show="comp.type === 'text_with_image'" x-cloak class="mb-3">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Image') }}</label>
                                    <div class="flex items-start gap-3">
                                        <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ __('Upload') }}
                                            <input type="file" accept="image/*" @change="uploadImage($event, comp)" class="hidden">
                                        </label>
                                        <template x-if="comp.image">
                                            <div class="relative inline-block">
                                                <img :src="comp.image" class="h-24 rounded-lg border border-gray-200 dark:border-gray-600 object-cover">
                                                <button type="button" @click="comp.image = ''"
                                                    class="absolute -top-2 -right-2 flex items-center justify-center w-5 h-5 bg-red-500 hover:bg-red-600 text-white rounded-full text-xs shadow transition-colors">&times;</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Language tabs + editors --}}
                                <div>
                                    <div class="flex gap-1 mb-2 border-b border-gray-200 dark:border-gray-600">
                                        <button type="button" @click="comp._tab = 'vi'"
                                            :class="comp._tab === 'vi' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                            class="px-3 py-1.5 text-xs font-medium -mb-px transition-colors">Tiếng Việt</button>
                                        <button type="button" @click="comp._tab = 'en'"
                                            :class="comp._tab === 'en' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                            class="px-3 py-1.5 text-xs font-medium -mb-px transition-colors">English</button>
                                    </div>

                                    <div x-show="comp._tab === 'vi'">
                                        <div :id="'editor-' + comp._key + '-vi'" class="help-editor bg-white dark:bg-gray-900"></div>
                                    </div>
                                    <div x-show="comp._tab === 'en'" style="display:none">
                                        <div :id="'editor-' + comp._key + '-en'" class="help-editor bg-white dark:bg-gray-900"></div>
                                    </div>
                                </div>

                                {{-- Hidden inputs for form submission --}}
                                <input type="hidden" :name="'components[' + idx + '][type]'" :value="comp.type">
                                <input type="hidden" :name="'components[' + idx + '][sort_order]'" :value="idx">
                                <input type="hidden" :name="'components[' + idx + '][image]'" :value="comp.image || ''">
                                <input type="hidden" :name="'components[' + idx + '][id]'" :value="comp.id || ''">
                                <input type="hidden" :name="'components[' + idx + '][contents][vi]'" :id="'content-' + comp._key + '-vi'">
                                <input type="hidden" :name="'components[' + idx + '][contents][en]'" :id="'content-' + comp._key + '-en'">
                            </div>
                        </template>

                        {{-- Add component button --}}
                        <button type="button" @click="addComponent()"
                            class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg
                                   text-sm font-medium text-gray-500 dark:text-gray-400
                                   hover:border-indigo-400 hover:text-indigo-500 dark:hover:border-indigo-500 dark:hover:text-indigo-400
                                   transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Add Step') }}
                        </button>
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-primary-button type="submit">
                            {{ isset($helpPage) ? __('Save') : __('Add Help Page') }}
                        </x-primary-button>
                        <a href="{{ route('admin.help-pages.index') }}">
                            <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>window._helpComponents = @json($initialComponents);</script>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
        <script>
            var _HelpConf = {
                uploadFail: '{{ addslashes(__('Image upload failed. Please try again.')) }}',
                uploadUrl:  '{{ route("admin.help-pages.upload-image") }}',
            };

            window._helpQuills = {};

            function _hpMakeEditor(containerId) {
                var el = document.getElementById(containerId);
                if (!el) return null;
                return new Quill('#' + containerId, {
                    theme: 'snow',
                    placeholder: '{{ addslashes(__('Write help content…')) }}',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            ['blockquote', 'code-block'],
                            ['link', 'image'],
                            ['clean']
                        ]
                    }
                });
            }

            function _hpAddImageHandler(quill) {
                quill.getModule('toolbar').addHandler('image', function () {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');
                    input.click();
                    input.addEventListener('change', function () {
                        var file = input.files[0];
                        if (!file) return;
                        var fd = new FormData();
                        fd.append('image', file);
                        fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                        fetch(_HelpConf.uploadUrl, { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                var range = quill.getSelection(true);
                                quill.insertEmbed(range.index, 'image', data.url, Quill.sources.USER);
                                quill.setSelection(range.index + 1, Quill.sources.SILENT);
                            })
                            .catch(function () { alert(_HelpConf.uploadFail); });
                    });
                });
            }

            function _hpInitEditors(key, initialContent) {
                ['vi', 'en'].forEach(function (locale) {
                    var cid = 'editor-' + key + '-' + locale;
                    if (window._helpQuills[cid]) return;
                    var q = _hpMakeEditor(cid);
                    if (!q) return;
                    _hpAddImageHandler(q);
                    window._helpQuills[cid] = q;
                    if (initialContent && initialContent[locale]) {
                        q.root.innerHTML = initialContent[locale];
                    }
                });
            }

            function helpEditor() {
                var raw = window._helpComponents || [];
                var comps = raw.map(function (c, i) {
                    return {
                        _key: i,
                        _tab: 'vi',
                        id: c.id || null,
                        type: c.type || 'text',
                        image: c.image || '',
                        _initialContent: c._initialContent || {}
                    };
                });
                var nextKey = comps.length;

                if (comps.length === 0) {
                    comps.push({ _key: nextKey++, _tab: 'vi', id: null, type: 'text', image: '', _initialContent: {} });
                }

                return {
                    components: comps,
                    _nextKey: nextKey,

                    addComponent: function () {
                        this.components.push({
                            _key: this._nextKey++,
                            _tab: 'vi',
                            id: null,
                            type: 'text',
                            image: '',
                            _initialContent: {}
                        });
                    },

                    removeComponent: function (idx) {
                        if (this.components.length <= 1) return;
                        var comp = this.components[idx];
                        ['vi', 'en'].forEach(function (loc) {
                            delete window._helpQuills['editor-' + comp._key + '-' + loc];
                        });
                        this.components.splice(idx, 1);
                    },

                    moveUp: function (idx) {
                        if (idx <= 0) return;
                        var item = this.components.splice(idx, 1)[0];
                        this.components.splice(idx - 1, 0, item);
                    },

                    moveDown: function (idx) {
                        if (idx >= this.components.length - 1) return;
                        var item = this.components.splice(idx, 1)[0];
                        this.components.splice(idx + 1, 0, item);
                    },

                    uploadImage: function (event, comp) {
                        var file = event.target.files[0];
                        if (!file) return;
                        var fd = new FormData();
                        fd.append('image', file);
                        fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                        fetch(_HelpConf.uploadUrl, { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (data) { comp.image = data.url; })
                            .catch(function () { alert(_HelpConf.uploadFail); });
                    }
                };
            }

            // Searchable route selector
            document.addEventListener('DOMContentLoaded', function () {
                new TomSelect('#route-select', { allowEmptyOption: true, maxOptions: 500 });
            });

            // Sync all Quill contents to hidden inputs on submit
            document.getElementById('help-page-form').addEventListener('submit', function () {
                Object.keys(window._helpQuills).forEach(function (cid) {
                    var q = window._helpQuills[cid];
                    var inputId = 'content-' + cid.replace('editor-', '');
                    var input = document.getElementById(inputId);
                    if (input && q) {
                        var html = q.root.innerHTML;
                        input.value = (html === '<p><br></p>') ? '' : html;
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
