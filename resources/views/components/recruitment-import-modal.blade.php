<div id="recruitment-import-modal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    onclick="if(event.target===this) closeImportModal()">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">Thêm ứng viên từ CV</h3>
            <button type="button" onclick="closeImportModal()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl leading-none">&times;</button>
        </div>

        <!-- Body -->
        <div class="overflow-y-auto flex-1 px-6 py-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Left: applicant fields -->
                <div class="space-y-4">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Trạng thái: <span id="import-status-label" class="font-medium text-gray-600 dark:text-gray-300"></span>
                    </p>

                    <div>
                        <x-input-label for="import-name" value="Tên ứng viên *" />
                        <x-text-input id="import-name" type="text" class="mt-1 block w-full" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="import-email" value="Email" />
                            <x-text-input id="import-email" type="email" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="import-phone" value="Điện thoại" />
                            <x-text-input id="import-phone" type="text" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <p id="import-error" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                </div>

                <!-- Right: CV preview -->
                <div>
                    <x-input-label value="Xem trước CV" />
                    <div id="import-cv-preview" class="mt-1"></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
            <button type="button" onclick="closeImportModal()"
                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Hủy
            </button>
            <button type="button" id="import-submit-btn" onclick="submitImportModal()"
                class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                Thêm ứng viên
            </button>
        </div>
    </div>
</div>
