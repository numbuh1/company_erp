@php
    $userOptions   = \App\Models\User::orderBy('name')->get(['id', 'name', 'position']);
    $skillOptions  = \App\Models\Skill::orderBy('category')->orderBy('name')->get();
    $tagOptions    = \App\Models\RecruitmentTag::where('type', 'applicant')->orderBy('name')->get();
    $statuses      = $position->allStatuses();
    $canViewSalary = auth()->user()->can('view recruitment salary');
    $canViewHrNote = auth()->user()->can('view recruitment hr note');
    $canDelete     = auth()->user()->can('edit recruitment');
    $skillsByCategory = $skillOptions
        ->groupBy('category')
        ->map(fn($g) => $g->values()->map(fn($s) => ['id' => $s->id, 'name' => $s->name]));
@endphp

<div id="recruitment-applicant-modal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    onclick="if(event.target===this) closeApplicantModal()">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h3 id="am-title" class="font-semibold text-lg text-gray-800 dark:text-gray-100">Chỉnh sửa Ứng viên</h3>
                <p id="am-subtitle" class="hidden text-xs text-gray-400 dark:text-gray-500 mt-0.5">CV đã được lưu. Kiểm tra và cập nhật thông tin ứng viên bên dưới.</p>
            </div>
            <button type="button" onclick="closeApplicantModal()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl leading-none">&times;</button>
        </div>

        <!-- Body -->
        <div class="overflow-y-auto flex-1 px-6 py-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Left: applicant fields -->
                <div class="space-y-4">

                    <!-- Status -->
                    <div>
                        <x-input-label for="am-status" value="Status *" />
                        <select id="am-status"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            @foreach($statuses as $s => $label)
                                <option value="{{ $s }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p id="am-error-status" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Name -->
                    <div>
                        <x-input-label for="am-name" value="Tên ứng viên *" />
                        <x-text-input id="am-name" type="text" class="mt-1 block w-full" />
                        <p id="am-error-name" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- CV Upload -->
                    <div>
                        <x-input-label for="am-cv" value="Tệp CV" />
                        <div id="am-current-cv" class="hidden mt-1 mb-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <span>📄 Current CV:</span>
                            <a id="am-current-cv-link" href="#" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">Tải xuống</a>
                        </div>
                        <input id="am-cv" type="file"
                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                   file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0
                                   file:text-sm file:bg-indigo-50 file:text-indigo-700
                                   hover:file:bg-indigo-100">
                        <p id="am-error-cv" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    @if($canViewHrNote)
                        <!-- HR Note (private) -->
                        <div>
                            <x-input-label for="am-hr-note" value="HR Note (riêng tư)" />
                            <textarea id="am-hr-note" rows="3"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm"
                                placeholder="Ghi chú nội bộ HR — chỉ người có quyền mới thấy được…"></textarea>
                            <p id="am-error-hr_note" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                        </div>
                    @endif

                    <!-- Notes -->
                    <div>
                        <x-input-label for="am-notes" value="Ghi chú" />
                        <textarea id="am-notes" rows="4"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm"
                            placeholder="Ghi chú, phản hồi, nhận xét phỏng vấn…"></textarea>
                        <p id="am-error-notes" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Evaluation -->
                    <div>
                        <x-input-label value="Đánh giá" />
                        <div class="flex items-center gap-1 mt-2" id="am-star-rating">
                            @for($i = 1; $i <= 3; $i++)
                                <button type="button" data-star="{{ $i }}"
                                    class="star-btn text-3xl focus:outline-none transition-transform hover:scale-110"
                                    onclick="setRating({{ $i }})">
                                    ☆
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" id="evaluation-input" value="0">
                        <p id="am-error-evaluation" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Contact -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="am-email" value="Email" />
                            <x-text-input id="am-email" type="email" class="mt-1 block w-full" />
                            <p id="am-error-email" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                        </div>
                        <div>
                            <x-input-label for="am-phone" value="Điện thoại" />
                            <x-text-input id="am-phone" type="text" class="mt-1 block w-full" />
                            <p id="am-error-phone" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                        </div>
                    </div>

                    <!-- Profile URL -->
                    <div>
                        <x-input-label for="am-profile-url" value="Profile URL (LinkedIn, etc.)" />
                        <x-text-input id="am-profile-url" type="url" class="mt-1 block w-full" placeholder="https://linkedin.com/in/…" />
                        <p id="am-error-profile_url" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Salary & Availability -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if($canViewSalary)
                            <div>
                                <x-input-label for="am-salary-expectation" value="Mức lương mong muốn" />
                                <x-text-input id="am-salary-expectation" type="number" min="0" step="100" class="mt-1 block w-full" />
                                <p id="am-error-salary_expectation" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                            </div>
                        @endif
                        <div>
                            <x-input-label for="am-available-date" value="Có hiệu lực từ" />
                            <x-text-input id="am-available-date" type="date" class="mt-1 block w-full" />
                            <p id="am-error-available_date" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                        </div>
                    </div>

                    <!-- Referer -->
                    <div>
                        <x-input-label for="am-referer-select" value="Được giới thiệu bởi" />
                        <select id="am-referer-select"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">— None —</option>
                            @foreach($userOptions as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}{{ $u->position ? ' · ' . $u->position : '' }}</option>
                            @endforeach
                        </select>
                        <p id="am-error-referer_user_id" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Skills -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <x-input-label value="Kỹ năng" />
                            @if($skillOptions->isNotEmpty())
                                <button type="button" onclick="openSkillModal()"
                                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Chỉnh sửa Kĩ năng
                                </button>
                            @endif
                        </div>
                        <div id="skills-summary"
                            class="flex flex-wrap gap-1.5 min-h-[2.5rem] p-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900">
                        </div>
                        <div id="skills-inputs"></div>
                        <p id="am-error-skills" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <!-- Tags -->
                    <div>
                        <x-input-label value="Thẻ" />
                        <select id="am-tags-select" multiple class="mt-1 block w-full">
                            @foreach($tagOptions as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                        <p id="am-error-tags" class="hidden text-xs text-red-600 dark:text-red-400 mt-1"></p>
                    </div>

                    <p id="am-error" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                </div>

                <!-- Right: CV preview -->
                <div>
                    <x-input-label value="Xem trước CV" />
                    <div id="am-cv-preview" class="mt-1"></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
            @if($canDelete)
                <button type="button" id="am-delete-btn" onclick="deleteApplicantModal()"
                    class="px-4 py-2 text-sm text-red-600 dark:text-red-400 border border-red-300 dark:border-red-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                    Xóa
                </button>
            @else
                <span></span>
            @endif
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeApplicantModal()"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Đóng
                </button>
                <button type="button" id="am-submit-btn" onclick="submitApplicantModal()"
                    class="px-4 py-2 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                    Lưu
                </button>
            </div>
        </div>
    </div>
</div>

<x-skill-picker-modal />

@push('scripts')
<script>
    window.recruitmentSkillsByCategory = @js($skillsByCategory);
</script>
@endpush
