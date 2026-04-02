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
                        {{ ucfirst(str_replace('_', ' ', $recruitmentPosition->status)) }}
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
                        Edit Position
                    </a>
                @endif
                <a href="{{ route('recruitment.index') }}"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:underline">← Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
                            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Job Description</h3>
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
                            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Required Skills</h3>
                            @foreach($recruitmentPosition->skills->groupBy('category') as $cat => $group)
                                <div class="mb-3">
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">{{ ucfirst($cat) }}</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($group as $skill)
                                            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $levelColor($skill->pivot->level) }}">
                                                {{ $skill->name }}
                                                <span class="opacity-60">· {{ ucfirst($skill->pivot->level) }}</span>
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
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Position Info</h3>

                        @if($recruitmentPosition->search_start_date || $recruitmentPosition->search_end_date)
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Search Period</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $recruitmentPosition->search_start_date?->format('d/m/Y') ?? '—' }}
                                    → {{ $recruitmentPosition->search_end_date?->format('d/m/Y') ?? '—' }}
                                </p>
                            </div>
                        @endif

                        @if($recruitmentPosition->salary_min || $recruitmentPosition->salary_max)
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Salary Range</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $recruitmentPosition->salary_min ? number_format($recruitmentPosition->salary_min) : '—' }}
                                    –
                                    {{ $recruitmentPosition->salary_max ? number_format($recruitmentPosition->salary_max) : '—' }}
                                </p>
                            </div>
                        @endif

                        @if($recruitmentPosition->tags->isNotEmpty())
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Tags</p>
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
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Assigned Users</h3>
                        <div class="space-y-2">
                            @forelse($recruitmentPosition->assignedUsers as $u)
                                <x-user-status :user="$u" />
                            @empty
                                <p class="text-sm text-gray-400">No users assigned.</p>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>

            {{-- Applicants --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-700 dark:text-gray-200">
                        Applicants
                        <span class="ml-2 text-xs font-normal text-gray-400">({{ $recruitmentPosition->applicants->count() }})</span>
                    </h3>
                    @if($canEdit)
                        <a href="{{ route('recruitment.applicants.create', $recruitmentPosition) }}"
                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            + Add Applicant
                        </a>
                    @endif
                </div>

                @forelse($recruitmentPosition->applicants as $applicant)
                    @php
                        $statusColor = match($applicant->status) {
                            'CV Screening'           => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                            'Approved for Interview' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                            'Approved'               => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                            'Rejected'               => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                            'Offered'                => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                            'Hired'                  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
                            default                  => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <div class="border-b border-gray-100 dark:border-gray-700 last:border-0 px-5 py-4">
                        <div class="flex items-start justify-between gap-4">

                            <div class="flex-1 min-w-0">

                                {{-- Line 1: Name + Status + Stars --}}
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $applicant->name }}</span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded {{ $statusColor }}">{{ $applicant->status }}</span>
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

                                {{-- 2-column info grid --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1 text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    @if($applicant->email)
                                        <span>✉ <a href="mailto:{{ $applicant->email }}" class="hover:text-indigo-400">{{ $applicant->email }}</a></span>
                                    @endif
                                    @if($applicant->available_date)
                                        <span>📅 Available {{ $applicant->available_date->format('d/m/Y') }}</span>
                                    @endif
                                    @if($applicant->phone)
                                        <span>📞 {{ $applicant->phone }}</span>
                                    @endif
                                    @if($applicant->salary_expectation)
                                        <span>💰 {{ number_format($applicant->salary_expectation) }}</span>
                                    @endif
                                    @if($applicant->profile_url)
                                        <span class="sm:col-span-2 truncate">🔗 <a href="{{ $applicant->profile_url }}" target="_blank" class="hover:text-indigo-400">{{ $applicant->profile_url }}</a></span>
                                    @endif
                                    @if($applicant->referer)
                                        <span class="sm:col-span-2">👤 Referred by {{ $applicant->referer->name }}</span>
                                    @endif
                                </div>

                                {{-- Skills --}}
                                @if($applicant->skills->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($applicant->skills as $skill)
                                            <span class="text-xs px-1.5 py-0.5 rounded-full {{ $levelColor($skill->pivot->level) }}">
                                                {{ $skill->name }} <span class="opacity-60">· {{ ucfirst($skill->pivot->level) }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                            </div>

                            {{-- Right: CV + Actions --}}
                            <div class="flex items-center gap-2 shrink-0 pt-0.5">
                                @if($applicant->cv_path)
                                    <a href="{{ route('recruitment.applicants.cv.download', [$recruitmentPosition, $applicant]) }}"
                                        class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline whitespace-nowrap">
                                        📄 CV
                                    </a>
                                @endif

                                @php
                                    $assignedIds = $recruitmentPosition->assignedUsers->pluck('id')->push(auth()->id())->unique()->values()->toJson();
                                    $cvUrl = $applicant->cv_path ? route('recruitment.applicants.cv.download', [$recruitmentPosition, $applicant]) : '';
                                    $interviewName = $recruitmentPosition->name . ' Interview - ' . $applicant->name;
                                    $interviewDesc = $cvUrl ? 'CV: ' . $cvUrl : '';
                                @endphp
                                <button type="button"
                                    onclick='openEventModal({
                                        name: @json($interviewName),
                                        event_type: "interview",
                                        attendants: {{ $assignedIds }},
                                        description: @json($interviewDesc),
                                        hideFile: true,
                                        title: "Book Interview",
                                        applicantId: {{ $applicant->id }}
                                    })'
                                    title="Book Interview"
                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-indigo-600 hover:border-indigo-400 bg-white dark:bg-gray-700 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Book Interview</span>
                                </button>

                                <a href="{{ route('recruitment.applicants.edit', [$recruitmentPosition, $applicant]) }}" title="Edit"
                                    class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-yellow-600 hover:border-yellow-400 bg-white dark:bg-gray-700 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Edit</span>
                                </a>

                                @if($canEdit)
                                    <form method="POST"
                                        action="{{ route('recruitment.applicants.destroy', [$recruitmentPosition, $applicant]) }}"
                                        onsubmit="return confirm('Remove this applicant?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete"
                                            class="relative group inline-flex items-center justify-center w-8 h-8 rounded border border-gray-300 dark:border-gray-600 text-gray-500 hover:text-red-600 hover:border-red-400 bg-white dark:bg-gray-700 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-1 text-xs bg-gray-800 text-white rounded opacity-0 group-hover:opacity-100 whitespace-nowrap pointer-events-none">Delete</span>
                                        </button>
                                    </form>
                                @endif
                            </div>

                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-400">No applicants yet.</div>
                @endforelse
            </div>

        </div>
    </div>
    <x-event-modal />
</x-app-layout>
