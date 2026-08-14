@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet" />
    <style>
        .help-editor { min-height: 360px; font-size: 0.9rem; }
        .help-editor img { max-width: 100%; border-radius: 0.375rem; }
        .ql-toolbar.ql-snow { border-radius: 0.375rem 0.375rem 0 0; border-color: rgb(209 213 219); }
        .ql-container.ql-snow { border-radius: 0 0 0.375rem 0.375rem; border-color: rgb(209 213 219); }
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

                    {{-- Route --}}
                    <div class="mb-5">
                        <x-input-label :value="__('Route')" />
                        <select name="route" id="route-select"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">— {{ __('Select a route') }} —</option>
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

                    {{-- Language tabs --}}
                    @php
                        $locales = ['vi' => 'Tiếng Việt', 'en' => 'English'];
                        $existingContents = isset($helpPage)
                            ? $helpPage->contents->keyBy('locale')
                            : collect();
                    @endphp

                    <div x-data="{ tab: 'vi' }" class="mb-6">
                        <x-input-label :value="__('Content')" class="mb-2" />

                        {{-- Tab buttons --}}
                        <div class="flex gap-1 mb-3 border-b border-gray-200 dark:border-gray-700">
                            @foreach($locales as $code => $label)
                                <button type="button"
                                    @click="tab = '{{ $code }}'"
                                    :class="tab === '{{ $code }}'
                                        ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="px-4 py-2 text-sm font-medium transition -mb-px">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        {{-- Editor panes — both initialized; hidden via display:none so Quill works fine --}}
                        @foreach($locales as $code => $label)
                            @php
                                $initialContent = old("contents.{$code}",
                                    $existingContents->get($code)?->content ?? '');
                            @endphp
                            <div x-show="tab === '{{ $code }}'" style="display: {{ $code === 'vi' ? 'block' : 'none' }}">
                                <div id="editor-{{ $code }}" class="help-editor bg-white"></div>
                                <input type="hidden" name="contents[{{ $code }}]" id="content-{{ $code }}">
                                @if(!empty($initialContent))
                                    <script>window._helpInitial_{{ $code }} = {!! json_encode($initialContent) !!};</script>
                                @endif
                            </div>
                        @endforeach
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

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
        <script>
            var _HelpLRM = {
                uploadFail: '{{ addslashes(__('Image upload failed. Please try again.')) }}',
                uploadUrl:  '{{ route('admin.help-pages.upload-image') }}',
            };

            function makeHelpEditor(containerId, inputId) {
                var quill = new Quill('#' + containerId, {
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

                // Image upload handler
                quill.getModule('toolbar').addHandler('image', function () {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');
                    input.click();
                    input.addEventListener('change', async function () {
                        var file = input.files[0];
                        if (!file) return;
                        var formData = new FormData();
                        formData.append('image', file);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                        try {
                            var response = await fetch(_HelpLRM.uploadUrl, { method: 'POST', body: formData });
                            var data = await response.json();
                            var range = quill.getSelection(true);
                            quill.insertEmbed(range.index, 'image', data.url, Quill.sources.USER);
                            quill.setSelection(range.index + 1, Quill.sources.SILENT);
                        } catch (e) {
                            alert(_HelpLRM.uploadFail);
                        }
                    });
                });

                // Clipboard paste image handler
                quill.root.addEventListener('paste', function (e) {
                    var items = (e.clipboardData || {}).items;
                    if (!items) return;
                    for (var i = 0; i < items.length; i++) {
                        if (items[i].type.indexOf('image') !== -1) {
                            e.preventDefault();
                            var file = items[i].getAsFile();
                            if (!file) continue;
                            var formData = new FormData();
                            formData.append('image', file);
                            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                            fetch(_HelpLRM.uploadUrl, { method: 'POST', body: formData })
                                .then(function (r) { return r.json(); })
                                .then(function (data) {
                                    var range = quill.getSelection(true);
                                    quill.insertEmbed(range.index, 'image', data.url, Quill.sources.USER);
                                    quill.setSelection(range.index + 1, Quill.sources.SILENT);
                                })
                                .catch(function () { alert(_HelpLRM.uploadFail); });
                        }
                    }
                });

                return quill;
            }

            // Searchable route selector
        new TomSelect('#route-select', { allowEmptyOption: true, maxOptions: 500 });

        var quillVi = makeHelpEditor('editor-vi', 'content-vi');
            var quillEn = makeHelpEditor('editor-en', 'content-en');

            // Pre-populate
            if (window._helpInitial_vi) quillVi.root.innerHTML = window._helpInitial_vi;
            if (window._helpInitial_en) quillEn.root.innerHTML = window._helpInitial_en;

            // On submit: write both editors into hidden inputs
            document.getElementById('help-page-form').addEventListener('submit', function () {
                document.getElementById('content-vi').value = quillVi.root.innerHTML;
                document.getElementById('content-en').value = quillEn.root.innerHTML;
            });
        </script>
    @endpush
</x-app-layout>
