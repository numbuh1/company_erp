<div id="skill-modal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    onclick="if(event.target===this) closeSkillModal()">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">Select Skills</h3>
                <p class="text-xs text-gray-400 mt-0.5">Click to cycle: None → Beginner → Intermediate → Advanced</p>
            </div>
            <button type="button" onclick="closeSkillModal()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl leading-none">&times;</button>
        </div>

        <div class="overflow-y-auto flex-1 px-6 py-4" id="skill-modal-body">
            {{-- Rendered by JS --}}
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex gap-3 text-xs text-gray-400">
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-green-400"></span> Beginner
                </span>
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-blue-400"></span> Intermediate
                </span>
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-red-400"></span> Advanced
                </span>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeSkillModal()"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Cancel
                </button>
                <button type="button" onclick="applySkills()"
                    class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                    Apply
                </button>
            </div>
        </div>
    </div>
</div>
