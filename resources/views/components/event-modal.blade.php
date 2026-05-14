<div id="event-modal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    onclick="if(event.target===this) closeEventModal()">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100" id="event-modal-title">New Event</h3>
            <button type="button" onclick="closeEventModal()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl leading-none">&times;</button>
        </div>

        <!-- Body -->
        <div class="overflow-y-auto flex-1 px-6 py-4">
            <form id="event-modal-form" method="POST" action="{{ route('events.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="event-modal-method" value="POST">
                <input type="hidden" name="_source" id="event-modal-source" value="">
                <input type="hidden" name="applicant_ids[]" id="event-applicant-id" value="">

                {{-- Applicant link (shown when booking an interview for a specific applicant) --}}
                <div id="event-applicant-link-row" class="hidden mb-4 flex items-center gap-2 p-2.5 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                    <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <a id="event-applicant-link" href="#" target="_blank"
                        class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline truncate">
                    </a>
                </div>

                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <!-- Name -->
                <div class="mb-4">
                    <x-input-label for="event-name" value="Event Name *" />
                    <x-text-input id="event-name" name="name" type="text" class="mt-1 block w-full"
                        value="{{ old('name') }}" required />
                </div>

                <!-- Type + Location -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <x-input-label for="event-type" value="Event Type *" />
                        <select id="event-type" name="event_type"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            @foreach(\App\Models\Event::$types as $val => $label)
                                <option value="{{ $val }}" {{ old('event_type') === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="event-location" value="Location" />
                        <select id="event-location" name="location"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                        </select>
                    </div>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <x-input-label for="event-start" value="Start *" />
                        <x-text-input id="event-start" name="start_at" type="datetime-local" class="mt-1 block w-full"
                            value="{{ old('start_at') }}" required />
                    </div>
                    <div>
                        <x-input-label for="event-end" value="End *" />
                        <x-text-input id="event-end" name="end_at" type="datetime-local" class="mt-1 block w-full"
                            value="{{ old('end_at') }}" required />
                    </div>
                </div>

                <!-- Attendants -->
                <div class="mb-4">
                    <x-input-label for="event-attendants" value="Attendants" />
                    <select id="event-attendants" name="attendants[]" multiple
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                    </select>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <x-input-label for="event-description" value="Description" />
                    <textarea id="event-description" name="description" rows="3"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">{{ old('description') }}</textarea>
                </div>

                <!-- File -->
                <div class="mb-2" id="event-file-section">
                    <x-input-label for="event-file" value="Attachment" />
                    <input id="event-file" name="file" type="file"
                        class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                               file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0
                               file:text-sm file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">

            {{-- Delete form (shown only in edit mode via JS) --}}
            <form id="event-delete-form" method="POST" action="" class="hidden mr-auto">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit"
                    onclick="return confirm('Delete this event? All attendants will be notified of the cancellation.')"
                    class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition">
                    Delete Event
                </button>
            </form>

            <button type="button" onclick="closeEventModal()"
                class="ml-auto px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Cancel
            </button>
            <button type="button" onclick="document.getElementById('event-modal-form').submit()"
                class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                Save Event
            </button>
        </div>
    </div>
</div>
