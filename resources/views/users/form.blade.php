<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                {{ isset($user) ? __('Edit User') : __('Create User') }}
            </h2>
            <a href="javascript:history.back()"><x-secondary-button>{{ __('Back') }}</x-secondary-button></a>
        </div>
    </x-slot>

    @push('styles')
    <style>[x-cloak] { display: none !important; }</style>
    @endpush

    @php
        $authUser        = auth()->user();
        $isOwnProfile    = isset($user) && $authUser->id === $user->id;
        $canEditBasic    = !isset($user)
                        || $authUser->can('edit all user')
                        || $authUser->can('edit team user');
        $canEditPersonal = $authUser->can('edit all user');
        $canSeePersonal  = $authUser->can('edit all user')
                        || $authUser->can('view all user personal info')
                        || $isOwnProfile;
        $canSeeHR        = $authUser->can('edit all user')
                        || $authUser->can('view all user personal info')
                        || $authUser->canAny(['edit team leaves balance', 'edit all leaves balance']);
        $canEditLeaveBalance = $authUser->canAny(['edit team leaves balance', 'edit all leaves balance']);

        // Build tab list
        $tabs = [];
        if (isset($user))    $tabs[] = ['key' => 'general',  'label' => 'General Info'];
        if ($canSeePersonal) $tabs[] = ['key' => 'private',  'label' => 'Private Info'];
        if ($canSeePersonal) $tabs[] = ['key' => 'contact',  'label' => 'Contact Info'];
        if ($isOwnProfile)   $tabs[] = ['key' => 'settings', 'label' => 'Settings'];
        if ($canSeeHR)       $tabs[] = ['key' => 'hr',       'label' => 'HR Only'];

        $defaultTab = $tabs[0]['key'] ?? null;

        // Email notification preferences for this user
        $emailPrefs = isset($user) ? ($user->preferences?->email_notifications ?? []) : [];

        $sr = isset($user) ? $user->salaryRecord : null;
    @endphp

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <form method="POST"
                action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}"
                enctype="multipart/form-data">
                @csrf
                @if(isset($user)) @method('PUT') @endif

                {{-- Keep the originating recruitment applicant linked when
                     creating a user via "Begin Onboard" --}}
                @if(!isset($user) && (old('recruitment_applicant_id') || ($prefill['recruitment_applicant_id'] ?? null)))
                    <input type="hidden" name="recruitment_applicant_id"
                        value="{{ old('recruitment_applicant_id', $prefill['recruitment_applicant_id'] ?? '') }}">
                @endif

                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                {{-- ═══════════════════════════════════════════════════
                     BASIC INFO — always visible, always at the top
                ═══════════════════════════════════════════════════ --}}
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        {{ __('Basic Info') }}
                        @if(isset($user) && !$canEditBasic)
                            <span class="ml-2 normal-case font-normal text-gray-400">{{ __('(Read-only — except avatar)') }}</span>
                        @endif
                    </p>

                    {{-- Profile Picture --}}
                    <div class="mb-6">
                        <x-input-label value="{{ __('Avatar') }}" />
                        <div class="mb-3 mt-1">
                            @if(isset($user) && $user->profile_picture)
                                <img id="currentPicture"
                                    src="{{ asset('storage/profile_pictures/' . $user->profile_picture) }}"
                                    class="w-24 h-24 rounded-full object-cover border-2 border-gray-300 dark:border-gray-600">
                            @else
                                <div id="currentPicture" class="w-24 h-24 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-400 text-sm">
                                    {{ __('No photo') }}
                                </div>
                            @endif
                        </div>
                        @if(isset($user))
                            {{-- Only editable when updating an existing user --}}
                            <input type="file" id="profilePictureInput" accept="image/*"
                                class="text-sm text-gray-600 dark:text-gray-300">
                            <input type="hidden" name="profile_picture_cropped" id="profilePictureCropped">
                        @endif
                    </div>

                    {{-- Crop Modal --}}
                    @if(isset($user))
                    <div id="cropModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-4">{{ __('Crop Avatar') }}</h3>
                            <div id="cropElement"></div>
                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" id="cropCancelBtn"
                                    class="px-4 py-1.5 text-sm rounded border border-gray-300 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">{{ __('Cancel') }}</button>
                                <button type="button" id="cropConfirmBtn"
                                    class="px-4 py-1.5 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">{{ __('Confirm') }}</button>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Core fields --}}
                    @if($canEditBasic)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="{{ __('Name') }} *" />
                                <x-text-input name="name" class="w-full mt-1"
                                    value="{{ old('name', $prefill['name'] ?? $user->name ?? '') }}" required />
                                @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label for="full_name" value="{{ __('Full Name') }}" />
                                <x-text-input id="full_name" name="full_name" class="w-full mt-1"
                                    value="{{ old('full_name', $user->full_name ?? '') }}"
                                    placeholder="{{ __('Full name (optional)') }}" />
                            </div>
                            <div>
                                <x-input-label value="{{ __('Position') }}" />
                                <x-text-input name="position" class="w-full mt-1"
                                    value="{{ old('position', $prefill['position'] ?? $user->position ?? '') }}" />
                            </div>
                            <div>
                                <x-input-label value="{{ __('Grade') }}" />
                                <x-text-input name="grade" class="w-full mt-1"
                                    value="{{ old('grade', $user->grade ?? '') }}" />
                            </div>
                            <div>
                                <x-input-label value="{{ __('Email') }} *" />
                                <x-text-input name="email" type="email" class="w-full mt-1"
                                    value="{{ old('email', $user->email ?? '') }}" required />
                                @error('email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>

                            {{-- Password — only shown on create form here; edit uses Settings tab --}}
                            @if(!isset($user))
                            <div>
                                <x-input-label value="{{ __('Password') }} *" />
                                <x-text-input type="password" name="password" class="w-full mt-1" autocomplete="new-password" required />
                                @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-input-label value="{{ __('Confirm Password') }} *" />
                                <x-text-input type="password" name="password_confirmation" class="w-full mt-1" autocomplete="new-password" required />
                            </div>
                            @endif
                        </div>
                    @else
                        {{-- Read-only display --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <x-input-label value="{{ __('Name') }}" />
                                <p class="mt-1 text-gray-800 dark:text-gray-200 font-medium">{{ $user->name }}</p>
                            </div>
                            <div>
                                <x-input-label value="{{ __('Full Name') }}" />
                                <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $user->full_name ?: '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="{{ __('Position') }}" />
                                <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $user->position ?: '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="{{ __('Grade') }}" />
                                <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $user->grade ?: '—' }}</p>
                            </div>
                            <div>
                                <x-input-label value="{{ __('Email') }}" />
                                <p class="mt-1 text-gray-800 dark:text-gray-200">{{ $user->email }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ═══════════════════════════════════════════════════
                     TAB SYSTEM
                ═══════════════════════════════════════════════════ --}}
                @if(!empty($tabs))
                <div x-data="{ activeTab: '{{ $defaultTab }}' }" class="mt-4">

                    {{-- Tab navigation --}}
                    <div class="bg-white dark:bg-gray-800 rounded-t-lg shadow-sm border border-gray-200 dark:border-gray-700 border-b-0">
                        <nav class="flex overflow-x-auto">
                            @foreach($tabs as $tab)
                            <button type="button"
                                @click="activeTab = '{{ $tab['key'] }}'"
                                :class="activeTab === '{{ $tab['key'] }}'
                                    ? 'border-b-2 border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300'"
                                class="px-5 py-3 text-sm font-medium whitespace-nowrap transition-colors shrink-0">
                                {{ __($tab['label']) }}
                            </button>
                            @endforeach
                        </nav>
                    </div>

                    {{-- Tab panels --}}
                    <div class="bg-white dark:bg-gray-800 rounded-b-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">

                        {{-- ── General Info ───────────────────── --}}
                        @if(isset($user))
                        <div x-show="activeTab === 'general'" x-cloak>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <x-input-label value="{{ __('Remaining Leave Hours') }}" />
                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                                        {{ rtrim(rtrim(number_format($user->leave_balance ?? 0, 2), '0'), '.') }}h
                                        <a href="{{ route('users.leave-balance-history', $user) }}" class="text-xs text-blue-500 ml-1 hover:underline">{{ __('history') }}</a>
                                    </p>
                                </div>
                                <div>
                                    <x-input-label value="{{ __('Used Leave Hours') }}" />
                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                                        {{ rtrim(rtrim(number_format($spentBalance ?? 0, 2), '0'), '.') }}h
                                    </p>
                                </div>
                            </div>

                            {{-- Probation Time --}}
                            @if($user->probation_start_date || $user->probation_end_date)
                                <div class="mb-6">
                                    <x-input-label value="{{ __('Probation Period') }}" />
                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                                        {{ $user->probation_start_date ? $user->probation_start_date->format('d/m/Y') : '—' }}
                                        –
                                        {{ $user->probation_end_date ? $user->probation_end_date->format('d/m/Y') : '—' }}
                                    </p>
                                </div>
                            @endif

                            {{-- Onboarded from recruitment applicant --}}
                            @if($canViewOriginalApplicant ?? false)
                                <div class="mb-6">
                                    <x-input-label value="{{ __('Original Applicant') }}" />
                                    <p class="mt-1 text-sm">
                                        <a href="{{ route('recruitment.applicants.show', [$user->recruitmentApplicant->recruitment_position_id, $user->recruitmentApplicant->id]) }}"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ $user->recruitmentApplicant->name }}
                                        </a>
                                    </p>
                                </div>
                            @endif

                            {{-- Supervisors --}}
                            <div class="mb-6">
                                <x-input-label value="{{ __('Supervisors') }}" />
                                @if($user->supervisors->isEmpty())
                                    <p class="mt-1 text-sm text-gray-400">{{ __('No supervisors yet.') }}</p>
                                @else
                                    <div class="space-y-2 mt-1">
                                        @foreach($user->supervisors as $supervisor)
                                            <div class="flex items-center gap-3 border rounded px-4 py-3 dark:border-gray-600">
                                                @if($supervisor->profile_picture)
                                                    <img src="{{ asset('storage/profile_pictures/' . $supervisor->profile_picture) }}"
                                                        class="w-8 h-8 rounded-full object-cover border border-gray-300">
                                                @else
                                                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-300 text-sm font-bold">
                                                        {{ strtoupper(substr($supervisor->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <div>
                                                    <a href="{{ route('users.show', $supervisor) }}"
                                                        class="text-sm font-medium text-gray-800 dark:text-gray-200 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                        {{ $supervisor->name }}
                                                    </a>
                                                    @if($supervisor->position)
                                                        <p class="text-xs text-gray-400">{{ $supervisor->position }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Teams --}}
                            <div>
                                <x-input-label value="{{ __('Team') }}" />
                                @if($user->teams->isEmpty())
                                    <p class="mt-1 text-sm text-gray-400">{{ __('Not a member of any team yet.') }}</p>
                                @else
                                    <div class="space-y-2 mt-1">
                                        @foreach($user->teams as $team)
                                            <div class="flex items-center justify-between border rounded px-4 py-3 dark:border-gray-600">
                                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                                    {{ $team->name }}
                                                </span>
                                                @if($team->pivot->is_leader)
                                                    <span class="bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 text-xs px-2 py-0.5 rounded">
                                                        {{ __('Team Leader') }}
                                                    </span>
                                                @else
                                                    <span class="bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 text-xs px-2 py-0.5 rounded">
                                                        {{ __('Member') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- ── Private Info ───────────────────── --}}
                        @if($canSeePersonal)
                        <div x-show="activeTab === 'private'" x-cloak>
                            @if($canEditPersonal)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label value="{{ __('Citizen ID') }}" />
                                        <x-text-input name="citizen_id" class="w-full mt-1"
                                            value="{{ old('citizen_id', $user->citizen_id ?? '') }}" />
                                    </div>
                                    <div>
                                        <x-input-label value="{{ __('Birthday') }}" />
                                        <x-text-input type="date" name="birthday" class="w-full mt-1"
                                            value="{{ old('birthday', isset($user) && $user->birthday ? $user->birthday->format('Y-m-d') : '') }}" />
                                        @error('birthday')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <x-input-label value="{{ __('Tax Code') }}" />
                                        <x-text-input name="tax_code" class="w-full mt-1"
                                            value="{{ old('tax_code', $user->tax_code ?? '') }}" />
                                    </div>
                                    <div>
                                        <x-input-label value="{{ __('Social Insurance ID') }}" />
                                        <x-text-input name="social_insurance_id" class="w-full mt-1"
                                            value="{{ old('social_insurance_id', $user->social_insurance_id ?? '') }}" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-input-label value="{{ __('Contract Expiry') }}" />
                                        <x-text-input type="date" name="contract_expiry" class="w-full mt-1"
                                            value="{{ old('contract_expiry', isset($user) && $user->contract_expiry ? $user->contract_expiry->format('Y-m-d') : '') }}" />
                                        @error('contract_expiry')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div><x-input-label value="{{ __('Citizen ID') }}" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->citizen_id ?: '—') : '—' }}</p></div>
                                    <div><x-input-label value="{{ __('Birthday') }}" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) && $user->birthday ? $user->birthday->format('d/m/Y') : '—' }}</p></div>
                                    <div><x-input-label value="{{ __('Tax Code') }}" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->tax_code ?: '—') : '—' }}</p></div>
                                    <div><x-input-label value="{{ __('Social Insurance ID') }}" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->social_insurance_id ?: '—') : '—' }}</p></div>
                                    <div class="sm:col-span-2"><x-input-label value="{{ __('Contract Expiry') }}" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) && $user->contract_expiry ? $user->contract_expiry->format('d/m/Y') : '—' }}</p></div>
                                </div>
                            @endif
                        </div>

                        {{-- ── Contact Info ────────────────────── --}}
                        <div x-show="activeTab === 'contact'" x-cloak>
                            @if($canEditPersonal)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label value="{{ __('Contact Email') }}" />
                                        <x-text-input name="contact_email" type="email" class="w-full mt-1"
                                            value="{{ old('contact_email', $prefill['contact_email'] ?? $user->contact_email ?? '') }}" />
                                        @error('contact_email')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <x-input-label value="{{ __('Phone') }}" />
                                        <x-text-input name="phone_number" type="tel" class="w-full mt-1"
                                            value="{{ old('phone_number', $prefill['phone_number'] ?? $user->phone_number ?? '') }}" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-input-label value="{{ __('Home Address') }}" />
                                        <textarea name="home_address" rows="2"
                                            class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('home_address', $user->home_address ?? '') }}</textarea>
                                    </div>
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div><x-input-label value="{{ __('Contact Email') }}" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->contact_email ?: '—') : '—' }}</p></div>
                                    <div><x-input-label value="{{ __('Phone') }}" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->phone_number ?: '—') : '—' }}</p></div>
                                    <div class="sm:col-span-2"><x-input-label value="{{ __('Home Address') }}" /><p class="mt-1 text-gray-800 dark:text-gray-200">{{ isset($user) ? ($user->home_address ?: '—') : '—' }}</p></div>
                                </div>
                            @endif
                        </div>
                        @endif

                        {{-- ── Settings (own profile only) ─────── --}}
                        @if($isOwnProfile)
                        <div x-show="activeTab === 'settings'" x-cloak>

                            {{-- Sentinel: lets the controller know settings were submitted --}}
                            <input type="hidden" name="_email_prefs" value="1">

                            {{-- Password change --}}
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                                {{ __('Change Password') }}
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <x-input-label value="{{ __('New Password') }}" />
                                    <x-text-input type="password" name="password" class="w-full mt-1" autocomplete="new-password" />
                                    <p class="text-xs text-gray-400 mt-1">{{ __('Leave blank to keep current password.') }}</p>
                                    @error('password')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-input-label value="{{ __('Confirm Password') }}" />
                                    <x-text-input type="password" name="password_confirmation" class="w-full mt-1" autocomplete="new-password" />
                                </div>
                            </div>

                            {{-- Email notification preferences --}}
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                                {{ __('Email Notifications') }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
                                {{ __('Disable a type to stop receiving emails for that category, even when you are assigned as a recipient or CC.') }}
                            </p>

                            <div class="space-y-0 divide-y divide-gray-100 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">

                                @php
                                    $notifSettings = [
                                        ['key' => 'leave',        'label' => 'Leave Request Emails',    'desc' => 'Receive emails for leave requests requiring approval and when your request is updated'],
                                        ['key' => 'ot',           'label' => 'OT Request Emails',       'desc' => 'Receive emails for OT requests requiring approval and when your request is updated'],
                                        ['key' => 'project',      'label' => 'Project & Task Emails',   'desc' => 'Receive emails for project and task updates assigned to you'],
                                        ['key' => 'announcement', 'label' => 'Announcement Emails',     'desc' => 'Receive emails when a new announcement is posted'],
                                    ];
                                @endphp

                                @foreach($notifSettings as $ns)
                                <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800">
                                    <div class="mr-4">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ __($ns['label']) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __($ns['desc']) }}</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                        <input type="checkbox" name="email_notify_{{ $ns['key'] }}" value="1"
                                            class="sr-only peer"
                                            {{ ($emailPrefs[$ns['key']] ?? true) ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer
                                            peer-checked:bg-indigo-600
                                            after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                            after:bg-white after:border-gray-300 after:border after:rounded-full
                                            after:h-5 after:w-5 after:transition-all
                                            peer-checked:after:translate-x-full peer-checked:after:border-white">
                                        </div>
                                    </label>
                                </div>
                                @endforeach

                            </div>
                        </div>
                        @endif

                        {{-- ── HR Only ────────────────────────── --}}
                        @if($canSeeHR)
                        <div x-show="activeTab === 'hr'" x-cloak>

                            @if($canEditPersonal)
                            {{-- Employment / Active Status --}}
                            <div class="mb-5">
                                <x-input-label value="{{ __('Employment Status') }}" />
                                <select name="employment_status"
                                    class="mt-1 block border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                    @foreach(\App\Models\User::$employmentStatuses as $value => $label)
                                        <option value="{{ $value }}" {{ old('employment_status', $user->employment_status ?? 'active') === $value ? 'selected' : '' }}>
                                            {{ __($label) }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-400 mt-1">"{{ __('Inactive') }}" {{ __('will block the user from logging in.') }}</p>
                            </div>

                            {{-- Roles --}}
                            <div class="mb-5">
                                <x-input-label value="{{ __('Role') }}" />
                                <select name="roles[]" id="roles-select" data-multi-select
                                        data-placeholder="{{ __('Select roles…') }}" class="mt-1">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}"
                                            {{ isset($user) && $user->hasRole($role->name) ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Supervisors --}}
                            <div class="mb-5">
                                <x-input-label value="{{ __('Supervisors') }}" />
                                <p class="text-xs text-gray-400 mb-1">{{ __('Users who supervise this person') }}</p>
                                @if(empty($supervisorOptions) || $supervisorOptions->isEmpty())
                                    <p class="text-xs text-gray-400 px-1">{{ __('No other users yet.') }}</p>
                                @else
                                    <select name="supervisors[]" id="supervisors-select" data-multi-select
                                            data-placeholder="{{ __('Select supervisors…') }}" class="mt-1 block w-full" multiple>
                                        @foreach($supervisorOptions ?? [] as $opt)
                                            <option value="{{ $opt->id }}"
                                                {{ isset($user) && $user->supervisors->contains($opt->id) ? 'selected' : '' }}>
                                                {{ $opt->name }}{{ $opt->position ? ' · ' . $opt->position : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            {{-- WFH --}}
                            <div class="mb-5">
                                <x-input-label value="{{ __('Work from Home Policy') }}" />
                                <label class="inline-flex items-center gap-2 mt-2 cursor-pointer">
                                    <input type="hidden" name="wfh_without_approval" value="0">
                                    <input type="checkbox" name="wfh_without_approval" value="1"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        {{ old('wfh_without_approval', $user->wfh_without_approval ?? false) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('WFH without approval') }}</span>
                                </label>
                            </div>

                            @endif {{-- canEditPersonal --}}

                            {{-- Leave Balance — visible to leave balance managers --}}
                            @if($canEditLeaveBalance)
                            <div class="mb-5">
                                <div class="pt-4 {{ $canEditPersonal ? 'border-t border-gray-200 dark:border-gray-700' : '' }}">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">{{ __('Leave Hours') }}</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label value="{{ __('Remaining Leave Hours') }}" />
                                            <x-text-input type="number" step="0.5" name="leave_balance" class="w-full mt-1"
                                                value="{{ old('leave_balance', $user->leave_balance ?? 112) }}" />
                                        </div>
                                        <div>
                                            <x-input-label value="{{ __('Reason for Change') }}" />
                                            <x-text-input name="balance_reason" class="w-full mt-1"
                                                value="{{ old('balance_reason') }}" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($canEditPersonal)
                            {{-- Probation Time --}}
                            <div class="mb-5">
                                <div class="pt-4 {{ $canEditPersonal ? 'border-t border-gray-200 dark:border-gray-700' : '' }}">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">{{ __('Probation Period') }}</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label value="{{ __('Start Date') }}" />
                                            <x-text-input type="date" name="probation_start_date" class="w-full"
                                                value="{{ old('probation_start_date', isset($user) && $user->probation_start_date ? $user->probation_start_date->format('Y-m-d') : '') }}" />
                                            @error('probation_start_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <x-input-label value="{{ __('End Date') }}" />
                                            <x-text-input type="date" name="probation_end_date" class="w-full"
                                                value="{{ old('probation_end_date', isset($user) && $user->probation_end_date ? $user->probation_end_date->format('Y-m-d') : '') }}" />
                                            @error('probation_end_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Salary --}}
                            <div class="mb-5 pt-4 border-t border-gray-200 dark:border-gray-700"
                                x-data="{
                                    salary:       '{{ old('salary', $user->salary ?? '') }}',
                                    salaryType:   '{{ old('salary_type', $user->salary_type ?? 'monthly') }}',
                                    allowAdj:     '{{ old('allowance_adjustment', $sr?->allowance_adjustment ?? '') }}',
                                    allowBonus:   '{{ old('allowance_bonus', $sr?->allowance_bonus ?? '') }}',
                                    allowExclTax: '{{ old('allowance_excl_tax', $sr?->allowance_excl_tax ?? '') }}',
                                    parking:      '{{ old('parking_fee', $sr?->parking_fee ?? '') }}',
                                    insurance:    '{{ old('insurance', $sr?->insurance ?? '') }}',
                                    pit:          '{{ old('personal_income_tax', $sr?->personal_income_tax ?? '') }}',
                                    otherDed:     '{{ old('other_deduction', $sr?->other_deduction ?? '') }}',
                                    get h() { const s = parseFloat(this.salary); if (!s) return null;
                                        return { monthly: s/160, weekly: s/40, daily: s/8, hourly: s }[this.salaryType] ?? null; },
                                    get d() { const s = parseFloat(this.salary); if (!s) return null;
                                        return { monthly: s/20, weekly: s/5, daily: s, hourly: s*8 }[this.salaryType] ?? null; },
                                    get w() { const s = parseFloat(this.salary); if (!s) return null;
                                        return { monthly: s/4, weekly: s, daily: s*5, hourly: s*40 }[this.salaryType] ?? null; },
                                    get m() { const s = parseFloat(this.salary); if (!s) return null;
                                        return { monthly: s, weekly: s*4, daily: s*20, hourly: s*160 }[this.salaryType] ?? null; },
                                    get totalAllowance() {
                                        return (parseFloat(this.allowAdj)||0) + (parseFloat(this.allowBonus)||0) + (parseFloat(this.allowExclTax)||0) + (parseFloat(this.parking)||0);
                                    },
                                    get totalDeduction() {
                                        return (parseFloat(this.insurance)||0) + (parseFloat(this.pit)||0) + (parseFloat(this.otherDed)||0);
                                    },
                                    get grossPay() { const s = parseFloat(this.salary); if (!s) return null; return s + this.totalAllowance; },
                                    get netPay()   { const g = this.grossPay; if (g === null) return null; return g - this.totalDeduction; },
                                    fmt(n) { if (n === null) return '—';
                                        return new Intl.NumberFormat('vi-VN').format(Math.round(n)); }
                                }">

                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">{{ __('Salary') }}</p>

                                <x-input-label value="{{ __('Basic Salary') }}" />
                                <div class="flex gap-2 mt-1">
                                    <input type="number" name="salary" min="0" step="1" x-model="salary"
                                        placeholder="{{ __('Enter salary…') }}"
                                        class="flex-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                    <select name="salary_type" x-model="salaryType"
                                        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm">
                                        <option value="monthly">{{ __('Monthly') }}</option>
                                        <option value="weekly">{{ __('Weekly') }}</option>
                                        <option value="daily">{{ __('Daily') }}</option>
                                        <option value="hourly">{{ __('Hourly') }}</option>
                                    </select>
                                </div>
                                <div class="mt-2 grid grid-cols-4 gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded px-2 py-1 text-center"><div class="font-medium mb-0.5">{{ __('/ Hour') }}</div><div><span x-text="fmt(h)"></span> ₫</div></div>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded px-2 py-1 text-center"><div class="font-medium mb-0.5">{{ __('/ Day') }}</div><div><span x-text="fmt(d)"></span> ₫</div></div>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded px-2 py-1 text-center"><div class="font-medium mb-0.5">{{ __('/ Week') }}</div><div><span x-text="fmt(w)"></span> ₫</div></div>
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded px-2 py-1 text-center"><div class="font-medium mb-0.5">{{ __('/ Month') }}</div><div><span x-text="fmt(m)"></span> ₫</div></div>
                                </div>

                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-5 mb-2">
                                    {{ __('Allowances') }}
                                    <span class="ml-2 normal-case font-normal text-indigo-600 dark:text-indigo-400" x-show="totalAllowance !== 0">
                                        {{ __('Total:') }} <span x-text="fmt(totalAllowance)"></span> ₫
                                    </span>
                                </p>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div><x-input-label value="{{ __('Adjustment (±)') }}" /><input type="number" name="allowance_adjustment" step="1" x-model="allowAdj" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                    <div><x-input-label value="{{ __('Bonus') }}" /><input type="number" name="allowance_bonus" min="0" step="1" x-model="allowBonus" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                    <div><x-input-label value="{{ __('Tax-exempt Allowance') }}" /><input type="number" name="allowance_excl_tax" min="0" step="1" x-model="allowExclTax" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                    <div><x-input-label value="{{ __('Parking Fee') }}" /><input type="number" name="parking_fee" min="0" step="1" x-model="parking" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                </div>

                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-5 mb-2">
                                    {{ __('Deductions') }}
                                    <span class="ml-2 normal-case font-normal text-red-500 dark:text-red-400" x-show="totalDeduction > 0">
                                        {{ __('Total:') }} <span x-text="fmt(totalDeduction)"></span> ₫
                                    </span>
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div><x-input-label value="{{ __('Insurance') }}" /><input type="number" name="insurance" min="0" step="1" x-model="insurance" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                    <div><x-input-label value="{{ __('Personal Income Tax') }}" /><input type="number" name="personal_income_tax" min="0" step="1" x-model="pit" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                    <div><x-input-label value="{{ __('Other Deductions') }}" /><input type="number" name="other_deduction" min="0" step="1" x-model="otherDed" placeholder="0" class="mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" /></div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3" x-show="grossPay !== null">
                                    <div class="bg-indigo-50 dark:bg-indigo-900/30 rounded-lg px-4 py-3">
                                        <p class="text-xs text-indigo-500 dark:text-indigo-400 mb-1">{{ __('Gross Pay') }}</p>
                                        <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300"><span x-text="fmt(grossPay)"></span> ₫</p>
                                    </div>
                                    <div class="bg-green-50 dark:bg-green-900/30 rounded-lg px-4 py-3">
                                        <p class="text-xs text-green-500 dark:text-green-400 mb-1">{{ __('Net Pay') }}</p>
                                        <p class="text-sm font-semibold text-green-700 dark:text-green-300"><span x-text="fmt(netPay)"></span> ₫</p>
                                    </div>
                                </div>
                            </div>
                            @endif {{-- canEditPersonal --}}

                        </div>
                        @endif

                    </div>{{-- /tab panels --}}
                </div>{{-- /x-data tabs --}}
                @endif{{-- /!empty($tabs) --}}

                {{-- Save / Cancel --}}
                <div class="flex justify-end mt-6 gap-2">
                    <a href="javascript:history.back()"><x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button></a>
                    <x-primary-button>{{ isset($user) ? __('Save') : __('Create') }}</x-primary-button>
                </div>

            </form>
        </div>
    </div>

    @push('scripts')
        @if(isset($user))
            @vite('resources/js/users/form.js')
        @endif
    @endpush
</x-app-layout>
