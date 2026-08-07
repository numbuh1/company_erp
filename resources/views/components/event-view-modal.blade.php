<div id="event-view-modal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    onclick="if(event.target===this) closeViewEventModal()">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100" id="view-event-title">Event</h3>
            <button type="button" onclick="closeViewEventModal()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl leading-none">&times;</button>
        </div>

        <!-- Body -->
        <div class="overflow-y-auto flex-1 px-6 py-4 space-y-4">

            <div>
                <x-input-label value="Event Type" />
                <p id="view-event-type" class="mt-1 text-sm text-gray-800 dark:text-gray-200"></p>
            </div>

            <div id="view-event-location-row">
                <x-input-label value="Địa điểm" />
                <p id="view-event-location" class="mt-1 text-sm text-gray-800 dark:text-gray-200"></p>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <x-input-label value="Date" />
                    <p id="view-event-date" class="mt-1 text-sm text-gray-800 dark:text-gray-200"></p>
                </div>
                <div>
                    <x-input-label value="Time" />
                    <p id="view-event-time" class="mt-1 text-sm text-gray-800 dark:text-gray-200"></p>
                </div>
                <div>
                    <x-input-label value="Duration" />
                    <p id="view-event-duration" class="mt-1 text-sm text-gray-800 dark:text-gray-200"></p>
                </div>
            </div>

            <div id="view-event-description-row">
                <x-input-label value="Mô tả" />
                <p id="view-event-description" class="mt-1 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap"></p>
            </div>

            <div id="view-event-attendants-row">
                <x-input-label value="Attendants" />
                <div id="view-event-attendants" class="flex flex-wrap gap-1.5 mt-1"></div>
            </div>

            <div id="view-event-applicant-row" class="hidden p-2.5 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                <x-input-label value="Applicant" />
                <a id="view-event-applicant-link" href="#" target="_blank"
                    class="block mt-1 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline truncate"></a>
            </div>

            <div id="view-event-file-row" class="hidden">
                <x-input-label value="Tệp đính kèm" />
                <a id="view-event-file-link" href="#" target="_blank"
                    class="block mt-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline truncate"></a>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <button type="button" onclick="closeViewEventModal()"
                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Đóng
            </button>

            <div class="flex-1"></div>

            <button type="button" id="view-event-email-btn" onclick="openEmailFromView()"
                class="hidden px-4 py-2 text-sm bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition">
                ✉ Send Email
            </button>

            <button type="button" id="view-event-edit-btn" onclick="editFromView()"
                class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                Edit
            </button>
        </div>
    </div>
</div>
