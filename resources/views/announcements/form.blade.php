@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet" />
    <style>
        #editor-container { min-height: 400px; font-size: 0.9rem; }
        #editor-container img { max-width: 100%; border-radius: 0.375rem; }
        .ql-toolbar.ql-snow { border-radius: 0.375rem 0.375rem 0 0; border-color: rgb(209 213 219); }
        .ql-container.ql-snow { border-radius: 0 0 0.375rem 0.375rem; border-color: rgb(209 213 219); }
    </style>
@endpush

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($announcement) ? 'Edit Announcement' : 'New Announcement' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <form id="announcement-form" method="POST"
                    action="{{ isset($announcement) ? route('announcements.update', $announcement) : route('announcements.store') }}">
                    @csrf
                    @if(isset($announcement)) @method('PUT') @endif

                    @if($errors->any())
                        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                            <ul class="list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Title -->
                    <div class="mb-5">
                        <x-input-label value="Title" />
                        <x-text-input name="title" class="w-full mt-1"
                            value="{{ old('title', $announcement->title ?? '') }}"
                            placeholder="Announcement title..." />
                        @error('title')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rich Text Content -->
                    <div class="mb-5">
                        <x-input-label value="Content" class="mb-1" />
                        <div id="editor-container" class="mt-1 bg-white"></div>
                        <input type="hidden" name="content" id="content-input">
                        @error('content')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-primary-button type="submit">
                            {{ isset($announcement) ? 'Update' : 'Publish' }}
                        </x-primary-button>
                        <a href="{{ isset($announcement) ? route('announcements.show', $announcement) : route('announcements.index') }}">
                            <x-secondary-button type="button">Cancel</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
        <script>
            const quill = new Quill('#editor-container', {
                theme: 'snow',
                placeholder: 'Write your announcement...',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        ['blockquote'],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            });

            // Custom image upload handler
            quill.getModule('toolbar').addHandler('image', () => {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.click();
                input.addEventListener('change', async () => {
                    const file = input.files[0];
                    if (!file) return;
                    const formData = new FormData();
                    formData.append('image', file);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                    try {
                        const response = await fetch('{{ route('announcements.upload-image') }}', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        const range = quill.getSelection(true);
                        quill.insertEmbed(range.index, 'image', data.url, Quill.sources.USER);
                        quill.setSelection(range.index + 1, Quill.sources.SILENT);
                    } catch (e) {
                        alert('Image upload failed. Please try again.');
                    }
                });
            });

            // Pre-populate for edit / old input
            @php
                $initialContent = old('content', isset($announcement) ? $announcement->content : '');
            @endphp
            @if(!empty($initialContent))
                quill.root.innerHTML = {!! json_encode($initialContent) !!};
            @endif

            // On submit: write Quill HTML into hidden input
            document.getElementById('announcement-form').addEventListener('submit', function () {
                document.getElementById('content-input').value = quill.root.innerHTML;
            });
        </script>
    @endpush
</x-app-layout>
