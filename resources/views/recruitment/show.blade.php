@push('styles')
    <style>
        /* Always show the horizontal scrollbar on the Kanban board so it's
           clear the columns can be scrolled, even when content fits. */
        .kanban-scroll {
            overflow-x: scroll;
            scrollbar-width: thin;
        }
        .kanban-scroll::-webkit-scrollbar {
            height: 10px;
        }
        .kanban-scroll::-webkit-scrollbar-track {
            background: rgb(243 244 246);
            border-radius: 9999px;
        }
        .kanban-scroll::-webkit-scrollbar-thumb {
            background-color: rgb(203 213 225);
            border-radius: 9999px;
        }
        .kanban-scroll::-webkit-scrollbar-thumb:hover {
            background-color: rgb(148 163 184);
        }
        .dark .kanban-scroll::-webkit-scrollbar-track {
            background: rgb(31 41 55);
        }
        .dark .kanban-scroll::-webkit-scrollbar-thumb {
            background-color: rgb(75 85 99);
        }
        .dark .kanban-scroll::-webkit-scrollbar-thumb:hover {
            background-color: rgb(107 114 128);
        }
    </style>
@endpush

<x-app-layout>
    @php
        $posStatusColor = fn($s) => match($s) {
            'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
            'done'        => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
            default       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        };
    @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $recruitmentPosition->name }}
                    <span class="ml-1 text-xs font-medium px-1.5 py-0.5 rounded {{ $posStatusColor($recruitmentPosition->status) }}">
                        {{ ['upcoming' => 'Sắp tuyển', 'in_progress' => 'Đang tuyển', 'done' => 'Đã tuyển'][$recruitmentPosition->status] ?? ucfirst(str_replace('_', ' ', $recruitmentPosition->status)) }}
                    </span>
                </h2>
                @if($recruitmentPosition->team)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $recruitmentPosition->team->name }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if($canEdit)
                    <a href="{{ route('recruitment.edit', $recruitmentPosition) }}"
                        class="inline-flex items-center gap-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:text-yellow-600 hover:border-yellow-400 text-sm font-medium rounded-lg bg-white dark:bg-gray-700 transition">
                        Chỉnh sửa Vị trí
                    </a>
                @endif
                <a href="{{ route('recruitment.index') }}"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:underline">← Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ recruitView: localStorage.getItem('recruitView_{{ $recruitmentPosition->id }}') || 'list' }"
         x-init="$watch('recruitView', v => localStorage.setItem('recruitView_{{ $recruitmentPosition->id }}', v))">
        <div class="max-w-9xl mx-auto space-y-6">

            @if(session('success'))
                <div class="p-3 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded text-sm">{{ session('success') }}</div>
            @endif

            {{-- Position Details --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Left: Job Description + Skills --}}
                @php
                    $levelColor = fn($l) => match($l) {
                        'intermediate' => 'bg-blue-300/10 text-blue-500 ring-1 ring-inset ring-blue-500/20',
                        'advanced'     => 'bg-red-300/10 text-red-500 ring-1 ring-inset ring-red-500/20',
                        default        => 'bg-green-300/10 text-green-500 ring-1 ring-inset ring-green-500/20',
                    };
                @endphp
                <div class="lg:col-span-2 space-y-4">

                    @if($recruitmentPosition->description || $recruitmentPosition->file_path)
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Mô tả công việc</h3>
                            @if($recruitmentPosition->description)
                                <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">
                                    {{ $recruitmentPosition->description }}
                                </div>
                            @endif
                            @if($recruitmentPosition->file_path)
                                <div class="mt-3">
                                    <a href="{{ route('recruitment.jd.download', $recruitmentPosition) }}"
                                        class="inline-flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                        📄 Download Job Description File
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($recruitmentPosition->skills->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Kỹ năng yêu cầu</h3>
                            @foreach($recruitmentPosition->skills->groupBy('category') as $cat => $group)
                                <div class="mb-3">
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">{{ ucfirst($cat) }}</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($group as $skill)
                                            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $levelColor($skill->pivot->level) }}">
                                                {{ $skill->name }}
                                                <span class="opacity-60">· {{ ['beginner' => 'Sơ cấp', 'intermediate' => 'Trung cấp', 'advanced' => 'Nâng cao'][$skill->pivot->level] ?? '' }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Right: Position Metadata --}}
                <div class="space-y-4">

                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5 space-y-4">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Thông tin chức vụ</h3>

                        @if($recruitmentPosition->search_start_date || $recruitmentPosition->search_end_date)
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Search Period</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $recruitmentPosition->search_start_date?->format('d/m/Y') ?? '—' }}
                                    → {{ $recruitmentPosition->search_end_date?->format('d/m/Y') ?? '—' }}
                                </p>
                            </div>
                        @endif

                        @can('view recruitment salary')
                            @if($recruitmentPosition->salary_min || $recruitmentPosition->salary_max)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">Khoảng lương</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $recruitmentPosition->salary_min ? number_format($recruitmentPosition->salary_min) : '—' }}
                                        –
                                        {{ $recruitmentPosition->salary_max ? number_format($recruitmentPosition->salary_max) : '—' }}
                                    </p>
                                </div>
                            @endif
                        @endcan

                        @if($recruitmentPosition->tags->isNotEmpty())
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Thẻ</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($recruitmentPosition->tags as $tag)
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Người dùng được phân công</h3>
                        <div class="space-y-2">
                            @forelse($recruitmentPosition->assignedUsers as $u)
                                <x-user-status :user="$u" />
                            @empty
                                <p class="text-sm text-gray-400">Chưa có người dùng.</p>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>

            {{-- Applicants Section --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">

                {{-- Section Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 gap-3 flex-wrap">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-200">
                        Ứng viên
                        <span class="ml-2 text-xs font-normal text-gray-400">({{ $recruitmentPosition->applicants->count() }})</span>
                    </h3>
                    <div class="flex items-center gap-2">
                        {{-- View Toggle --}}
                        <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-xs font-medium">
                            <button type="button"
                                @click="recruitView = 'list'"
                                :class="recruitView === 'list' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="px-3 py-1.5 transition flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                                Danh sách
                            </button>
                            <button type="button"
                                @click="recruitView = 'kanban'"
                                :class="recruitView === 'kanban' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="px-3 py-1.5 transition border-l border-gray-300 dark:border-gray-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                </svg>
                                Bảng Kanban
                            </button>
                        </div>
                        @if($canEdit)
                            <button type="button" onclick="openApplicantCreateModal()"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                + Tạo Ứng Viên
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ── LIST VIEW ─────────────────────────────────────────────── --}}
                <div x-show="recruitView === 'list'">
                    @forelse($recruitmentPosition->applicants as $applicant)
                        @php
                            $statusColor = \App\Models\RecruitmentApplicant::statusColor($applicant->status);
                            $assignedIds   = $recruitmentPosition->assignedUsers->pluck('id')->push(auth()->id())->unique()->values()->toJson();
                            $cvUrl         = $applicant->cv_path ? route('recruitment.applicants.cv.download', [$recruitmentPosition, $applicant]) : '';
                            $interviewName = $recruitmentPosition->name . ' Interview - ' . $applicant->name;
                            $interviewDesc = $cvUrl ? 'CV: ' . $cvUrl : '';
                            $applicantUrl  = route('recruitment.applicants.show', [$recruitmentPosition, $applicant]);
                        @endphp
                        <div class="border-b border-gray-100 dark:border-gray-700 last:border-0 px-5 py-4">
                            <div class="flex items-start justify-between gap-4">

                                <div class="flex-1 min-w-0">

                                    {{-- Line 1: Name + Status + Stars --}}
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <a href="{{ $applicantUrl }}"
                                            class="font-semibold text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline">
                                            {{ $applicant->name }}
                                        </a>
                                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ $statusColor }}">{{ \App\Models\RecruitmentApplicant::statusLabel($applicant->status) }}</span>
                                        @if($applicant->evaluation > 0)
                                            <span class="text-amber-400 tracking-tight text-sm">
                                                @for($i = 1; $i <= 3; $i++){{ $i <= $applicant->evaluation ? '★' : '☆' }}@endfor
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Tags --}}
                                    @if($applicant->tags->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 mb-2">
                                            @foreach($applicant->tags as $tag)
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600 dark:bg-indigo-900 dark:text-indigo-300">{{ $tag->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="flex flex-col sm:flex-row sm:gap-8 text-xs text-gray-500 dark:text-gray-400 mb-2">
                                        <div class="flex-1 space-y-1">
                                            @if($applicant->email)
                                                <div>✉ <a href="mailto:{{ $applicant->email }}" class="hover:text-indigo-400">{{ $applicant->email }}</a></div>
                                            @endif
                                            @if($applicant->phone)
                                                <div>📞 {{ $applicant->phone }}</div>
                                            @endif
                                            @if($applicant->profile_url)
                                                <div class="truncate">🔗 <a href="{{ $applicant->profile_url }}" target="_blank" class="hover:text-indigo-400">{{ $applicant->profile_url }}</a></div>
                                            @endif
                                        </div>
                                        <div class="flex-1 space-y-1 mt-1 sm:mt-0">
                                            @can('view recruitment salary')
                                                @if($applicant->salary_expectation)
                                                    <div>💰 {{ number_format($applicant->salary_expectation) }}</div>
                                                @endif
                                            @endcan
                                            @if($applicant->available_date)
                                                <div>📅 Available {{ $applicant->available_date->format('d/m/Y') }}</div>
                                            @endif
                                            @if($applicant->referer)
                                                <div>👤 Referred by {{ $applicant->referer->name }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Skills --}}
                                    @if($applicant->skills->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 mb-2">
                                            @foreach($applicant->skills as $skill)
                                                <span class="text-xs px-1.5 py-0.5 rounded-full {{ $levelColor($skill->pivot->level) }}">
                                                    {{ $skill->name }} <span class="opacity-60">· {{ ['beginner' => 'Sơ cấp', 'intermediate' => 'Trung cấp', 'advanced' => 'Nâng cao'][$skill->pivot->level] ?? '' }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Linked Interview Events --}}
                                    @if($applicant->events->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5 mt-1">
                                            @foreach($applicant->events as $event)
                                                <button type="button" data-event-id="{{ $event->id }}"
                                                    class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                                                           bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                                                           hover:bg-blue-100 dark:hover:bg-blue-900/50 transition cursor-pointer">
                                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    {{ $event->start_at->format('d/m/Y H:i') }} · {{ $event->name }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif

                                </div>

                                {{-- Right: Actions --}}
                                <div class="flex items-center gap-2 shrink-0 pt-0.5">

                                    <a href="{{ $applicantUrl }}" title="Xem"
                                        class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xem</span>
                                    </a>

                                    @if($applicant->cv_path)
                                        <a href="{{ $cvUrl }}" title="Download CV"
                                            class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Tải xuống CV</span>
                                        </a>
                                    @endif

                                    <button type="button"
                                        onclick='openEventModal({
                                            name: @json($interviewName),
                                            event_type: "interview",
                                            attendants: {{ $assignedIds }},
                                            description: @json($interviewDesc),
                                            hideFile: true,
                                            title: "Book Interview",
                                            applicantId: {{ $applicant->id }},
                                            applicantUrl: @json($applicantUrl),
                                            applicantName: @json($applicant->name)
                                        })'
                                        title="Đặt lịch phỏng vấn"
                                        class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Đặt lịch phỏng vấn</span>
                                    </button>

                                    <button type="button" onclick="openApplicantEditModal({{ $applicant->id }})" title="Chỉnh sửa"
                                        class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Chỉnh sửa</span>
                                    </button>

                                    @if($canEdit)
                                        <form method="POST"
                                            action="{{ route('recruitment.applicants.destroy', [$recruitmentPosition, $applicant]) }}"
                                            onsubmit="return confirm('Remove this applicant?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Xóa"
                                                class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Xóa</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-gray-400">Chưa có ứng viên.</div>
                    @endforelse
                </div>

                {{-- ── KANBAN VIEW ───────────────────────────────────────────── --}}
                <div x-show="recruitView === 'kanban'" x-cloak class="p-4">
                    <div class="kanban-scroll pb-2">
                        <div class="flex gap-3" style="min-width: max-content;">
                            @php
                                $kanbanCols = $recruitmentPosition->allStatuses();
                            @endphp

                            @foreach($kanbanCols as $status => $label)
                                @php
                                    $colApplicants = $recruitmentPosition->applicants->where('status', $status)->values();
                                    $col = \App\Models\RecruitmentApplicant::kanbanColConfig($status);
                                @endphp
                                <div class="kanban-col w-60 shrink-0 flex flex-col rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700"
                                     data-status="{{ $status }}"
                                     ondragover="kanbanDragOver(event)"
                                     ondragleave="kanbanDragLeave(event)"
                                     ondrop="kanbanDrop(event)">

                                    {{-- Column Header --}}
                                    <div class="px-3 py-2.5 rounded-t-xl {{ $col['header'] }} flex items-center justify-between shrink-0 {{ $canEdit ? 'cursor-move' : '' }}"
                                         @if($canEdit)
                                            draggable="true"
                                            ondragstart="kanbanColDragStart(event)"
                                            ondragend="kanbanColDragEnd(event)"
                                         @endif>
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full {{ $col['dot'] }} shrink-0"></span>
                                            <span class="text-xs font-semibold">{{ $label }}</span>
                                        </div>
                                        <span class="kanban-col-count text-xs font-medium opacity-60 tabular-nums">{{ $colApplicants->count() }}</span>
                                    </div>

                                    {{-- Cards --}}
                                    <div class="kanban-cards flex-1 min-h-20 p-2 space-y-2 overflow-y-auto"
                                         style="max-height: calc(100vh - 18rem);">

                                        @foreach($colApplicants as $applicant)
                                            @php $applicantUrl = route('recruitment.applicants.show', [$recruitmentPosition, $applicant]); @endphp
                                            <div class="kanban-card bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-sm transition select-none"
                                                 draggable="true"
                                                 data-applicant-id="{{ $applicant->id }}"
                                                 data-applicant-url="{{ $applicantUrl }}"
                                                 ondragstart="kanbanDragStart(event)">

                                                <div class="flex items-start justify-between gap-1 mb-1.5">
                                                    <span class="font-medium text-sm text-gray-800 dark:text-gray-100 leading-tight">{{ $applicant->name }}</span>
                                                    <div class="flex items-center gap-1 shrink-0">
                                                        @if($applicant->evaluation > 0)
                                                            <span class="text-amber-400 text-xs leading-none pt-0.5">
                                                                @for($i = 1; $i <= 3; $i++){{ $i <= $applicant->evaluation ? '★' : '☆' }}@endfor
                                                            </span>
                                                        @endif
                                                        @if($canEdit)
                                                            <div class="relative" x-data="{
                                                                    open: false,
                                                                    menuStyle: '',
                                                                    toggleMenu() {
                                                                        if (this.open) { this.open = false; return; }
                                                                        const rect = this.$refs.kebabBtn.getBoundingClientRect();
                                                                        this.menuStyle = `top: ${rect.bottom + 4}px; left: ${Math.max(rect.right - 112, 4)}px;`;
                                                                        this.open = true;
                                                                    }
                                                                }"
                                                                @scroll.window.capture="open = false">
                                                                <button type="button" x-ref="kebabBtn" @click.stop="toggleMenu()"
                                                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 -m-1 p-1 rounded">
                                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                                        <path d="M12 6a2 2 0 100-4 2 2 0 000 4zM12 14a2 2 0 100-4 2 2 0 000 4zM12 22a2 2 0 100-4 2 2 0 000 4z"/>
                                                                    </svg>
                                                                </button>
                                                                <template x-teleport="body">
                                                                    <div x-show="open" x-cloak :style="menuStyle" @click.stop @click.outside="open = false"
                                                                        class="fixed w-28 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 overflow-hidden text-xs">
                                                                        <a href="{{ $applicantUrl }}"
                                                                            class="block px-3 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                                                                            Xem
                                                                        </a>
                                                                        <button type="button" onclick="deleteKanbanApplicant(event, {{ $applicant->id }})"
                                                                            class="block w-full text-left px-3 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                                                            Xóa
                                                                        </button>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if($applicant->tags->isNotEmpty())
                                                    <div class="flex flex-wrap gap-1 mb-1.5">
                                                        @foreach($applicant->tags->take(3) as $tag)
                                                            <span class="text-xs px-1.5 py-0 rounded bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $tag->name }}</span>
                                                        @endforeach
                                                        @if($applicant->tags->count() > 3)
                                                            <span class="text-xs text-gray-400">+{{ $applicant->tags->count() - 3 }}</span>
                                                        @endif
                                                    </div>
                                                @endif

                                                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                                                    @if($applicant->email)
                                                        <div class="truncate">✉ {{ $applicant->email }}</div>
                                                    @endif
                                                    @if($applicant->phone)
                                                        <div class="truncate">📞 {{ $applicant->phone }}</div>
                                                    @endif
                                                    @can('view recruitment salary')
                                                        @if($applicant->salary_expectation)
                                                            <div>💰 {{ number_format($applicant->salary_expectation) }}</div>
                                                        @endif
                                                    @endcan
                                                </div>

                                                @if($applicant->events->isNotEmpty())
                                                    <div class="mt-1.5 flex items-center gap-1 text-xs text-blue-500 dark:text-blue-400">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                        {{ $applicant->events->count() }} interview{{ $applicant->events->count() > 1 ? 's' : '' }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach

                                    </div>
                                </div>
                            @endforeach

                            @if($canEdit)
                                <div class="kanban-col w-60 shrink-0 flex flex-col">
                                    <div id="add-status-display" class="rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-3 flex items-center justify-center h-12">
                                        <button type="button" onclick="openAddStatusForm()"
                                            class="text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Thêm trạng thái
                                        </button>
                                    </div>
                                    <div id="add-status-form" class="hidden rounded-xl border border-gray-300 dark:border-gray-600 p-3 space-y-2 bg-gray-50 dark:bg-gray-900/50">
                                        <input type="text" id="add-status-input" maxlength="100" placeholder="Tên trạng thái"
                                            class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                        <p id="add-status-error" class="hidden text-xs text-red-600 dark:text-red-400"></p>
                                        <div class="flex items-center gap-2">
                                            <button type="button" onclick="submitAddStatus()"
                                                class="px-3 py-1.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                                                Thêm
                                            </button>
                                            <button type="button" onclick="cancelAddStatus()"
                                                class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                Hủy
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <x-event-modal />
    <x-recruitment-applicant-modal :position="$recruitmentPosition" />

    @push('scripts')
    <script>
        window.recruitmentBaseUrl          = @js(route('recruitment.show', $recruitmentPosition));
        window.recruitmentStoreUrl         = @js(route('recruitment.applicants.store', $recruitmentPosition));
        window.recruitmentAddStatusUrl     = @js(route('recruitment.applicants.statuses.add', $recruitmentPosition));
        window.recruitmentReorderStatusesUrl = @js(route('recruitment.applicants.statuses.reorder', $recruitmentPosition));
        window.recruitmentNotifyBulkUrl    = @js(route('recruitment.applicants.notify-bulk', $recruitmentPosition));
        window.recruitmentStatusLabels     = @js($kanbanCols);
        window.recruitmentCanEdit          = @js($canEdit);
    </script>
    @endpush

</x-app-layout>
