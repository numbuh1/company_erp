<x-app-layout>
    @php
        $levelColor = fn($l) => match($l) {
            'intermediate' => 'bg-blue-300/10 text-blue-500 ring-1 ring-inset ring-blue-500/20',
            'advanced'     => 'bg-red-300/10 text-red-500 ring-1 ring-inset ring-red-500/20',
            default        => 'bg-green-300/10 text-green-500 ring-1 ring-inset ring-green-500/20',
        };
        $statusColor = match($recruitmentApplicant->status) {
            'CV Screening'           => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
            'Approved for Interview' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
            'Approved'               => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
            'Rejected'               => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
            'Offered'                => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
            'Hired'                  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
            default                  => 'bg-gray-100 text-gray-600',
        };
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $recruitmentApplicant->name }}
                    <span class="ml-1 text-xs font-medium px-1.5 py-0.5 rounded {{ $statusColor }}">
                        {{ $recruitmentApplicant->status }}
                    </span>
                    @if($recruitmentApplicant->evaluation > 0)
                        <span class="ml-1 text-amber-400 tracking-tight text-base">
                            @for($i = 1; $i <= 3; $i++){{ $i <= $recruitmentApplicant->evaluation ? '★' : '☆' }}@endfor
                        </span>
                    @endif
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    <a href="{{ route('recruitment.show', $recruitmentPosition) }}"
                        class="hover:text-indigo-500 hover:underline">
                        {{ $recruitmentPosition->name }}
                    </a>
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if($recruitmentApplicant->cv_path)
                    <a href="{{ route('recruitment.applicants.cv.download', [$recruitmentPosition, $recruitmentApplicant]) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:text-indigo-600 hover:border-indigo-400 text-sm font-medium rounded-lg bg-white dark:bg-gray-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        {{ __('Download CV') }}
                    </a>
                @endif
                <a href="{{ route('recruitment.applicants.edit', [$recruitmentPosition, $recruitmentApplicant]) }}"
                    class="inline-flex items-center gap-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:text-yellow-600 hover:border-yellow-400 text-sm font-medium rounded-lg bg-white dark:bg-gray-700 transition">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('recruitment.show', $recruitmentPosition) }}"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:underline">← Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded text-sm">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Left: Main Info --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Contact & Details --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">{{ __('Details') }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            @if($recruitmentApplicant->email)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">{{ __('Email') }}</p>
                                    <a href="mailto:{{ $recruitmentApplicant->email }}"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ $recruitmentApplicant->email }}
                                    </a>
                                </div>
                            @endif
                            @if($recruitmentApplicant->phone)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">{{ __('Phone') }}</p>
                                    <p class="text-gray-700 dark:text-gray-300">{{ $recruitmentApplicant->phone }}</p>
                                </div>
                            @endif
                            @if($recruitmentApplicant->profile_url)
                                <div class="sm:col-span-2">
                                    <p class="text-xs text-gray-400 mb-0.5">{{ __('Profile URL') }}</p>
                                    <a href="{{ $recruitmentApplicant->profile_url }}" target="_blank"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline truncate block">
                                        {{ $recruitmentApplicant->profile_url }}
                                    </a>
                                </div>
                            @endif
                            @if($recruitmentApplicant->salary_expectation)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">{{ __('Salary Expectation') }}</p>
                                    <p class="text-gray-700 dark:text-gray-300">{{ number_format($recruitmentApplicant->salary_expectation) }}</p>
                                </div>
                            @endif
                            @if($recruitmentApplicant->available_date)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">{{ __('Available From') }}</p>
                                    <p class="text-gray-700 dark:text-gray-300">{{ $recruitmentApplicant->available_date->format('d/m/Y') }}</p>
                                </div>
                            @endif
                            @if($recruitmentApplicant->referer)
                                <div>
                                    <p class="text-xs text-gray-400 mb-0.5">{{ __('Referred By') }}</p>
                                    <p class="text-gray-700 dark:text-gray-300">{{ $recruitmentApplicant->referer->name }}</p>
                                </div>
                            @endif
                        </div>

                        @if($recruitmentApplicant->notes)
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-xs text-gray-400 mb-1">{{ __('Notes') }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">
                                    {{ $recruitmentApplicant->notes }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Tags --}}
                    @if($recruitmentApplicant->tags->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">{{ __('Tags') }}</h3>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($recruitmentApplicant->tags as $tag)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-900 dark:text-indigo-300">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Interview Events --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-200">
                                Interview Events
                                <span class="ml-1 text-xs font-normal text-gray-400">({{ $recruitmentApplicant->events->count() }})</span>
                            </h3>
                            @php
                                $assignedIds  = $recruitmentPosition->assignedUsers->pluck('id')->push(auth()->id())->unique()->values()->toJson();
                                $cvUrl        = $recruitmentApplicant->cv_path ? route('recruitment.applicants.cv.download', [$recruitmentPosition, $recruitmentApplicant]) : '';
                                $interviewName = $recruitmentPosition->name . ' Interview - ' . $recruitmentApplicant->name;
                                $applicantUrl  = route('recruitment.applicants.show', [$recruitmentPosition, $recruitmentApplicant]);
                                $interviewDesc = implode(PHP_EOL, [$cvUrl ? 'CV: ' . $cvUrl : '', 'Applicant Details: ' . $applicantUrl]);
                            @endphp
                            <button type="button"
                                onclick='openEventModal({
                                    name: @json($interviewName),
                                    event_type: "interview",
                                    attendants: {{ $assignedIds }},
                                    description: @json($interviewDesc),
                                    hideFile: true,
                                    title: "Book Interview",
                                    applicantId: {{ $recruitmentApplicant->id }},
                                    applicantUrl: @json($applicantUrl),
                                    applicantName: @json($recruitmentApplicant->name)
                                })'
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ __('Book Interview') }}
                            </button>
                        </div>

                        @forelse($recruitmentApplicant->events as $event)
                            <div class="flex gap-4 px-5 py-4 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="w-28 text-xs text-gray-400 shrink-0 pt-0.5">
                                    {{ $event->start_at->format('d/m/Y') }}<br>
                                    {{ $event->start_at->format('H:i') }} – {{ $event->end_at->format('H:i') }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <button type="button" data-event-id="{{ $event->id }}"
                                        class="font-medium text-sm text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 text-left cursor-pointer">
                                        {{ $event->name }}
                                    </button>
                                    @if($event->location)
                                        <p class="text-xs text-gray-400 mt-0.5">📍 {{ $event->location }}</p>
                                    @endif
                                    @if($event->attendants->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 mt-1.5">
                                            @foreach($event->attendants as $att)
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                    {{ $att->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-center text-sm text-gray-400">{{ __('No interviews booked yet.') }}</div>
                        @endforelse
                    </div>

                </div>

                {{-- Right: Skills --}}
                <div class="space-y-4">
                    @if($recruitmentApplicant->skills->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">{{ __('Skills') }}</h3>
                            @foreach($recruitmentApplicant->skills->groupBy('category') as $cat => $group)
                                <div class="mb-3">
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">{{ __( ucfirst($cat) ) }}</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($group as $skill)
                                            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $levelColor($skill->pivot->level) }}">
                                                {{ $skill->name }}
                                                <span class="opacity-60">· {{ __( ucfirst($skill->pivot->level) ) }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Position info --}}
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-5">
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">{{ __('Position') }}</h3>
                        <a href="{{ route('recruitment.show', $recruitmentPosition) }}"
                            class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ $recruitmentPosition->name }}
                        </a>
                        @if($recruitmentPosition->team)
                            <p class="text-xs text-gray-400 mt-1">{{ $recruitmentPosition->team->name }}</p>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    <x-event-modal />
</x-app-layout>
